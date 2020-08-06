/*
** Zabbix
** Copyright (C) 2001-2020 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

package redis

import (
	"context"
	"github.com/mediocregopher/radix/v3"
	"sync"
	"time"
	"zabbix.com/pkg/log"
)

const hkInterval = 10

type redisClient interface {
	Query(cmd radix.CmdAction) error
}

type RedisConn struct {
	client         radix.Client
	lastTimeAccess time.Time
}

// Query wraps the radix.Client.Do function.
func (r *RedisConn) Query(cmd radix.CmdAction) error {
	return r.client.Do(cmd)
}

// updateAccessTime updates the last time a connection was accessed.
func (r *RedisConn) updateAccessTime() {
	r.lastTimeAccess = time.Now()
}

// Thread-safe structure for manage connections.
type ConnManager struct {
	sync.Mutex
	connMutex   sync.Mutex
	connections map[URI]*RedisConn
	keepAlive   time.Duration
	timeout     time.Duration
	Destroy     context.CancelFunc
}

// NewConnManager initializes ConnManager structure and runs Go Routine that watches for unused connections.
func NewConnManager(keepAlive, timeout, hkInterval time.Duration) *ConnManager {
	ctx, cancel := context.WithCancel(context.Background())

	connMgr := &ConnManager{
		connections: make(map[URI]*RedisConn),
		keepAlive:   keepAlive,
		timeout:     timeout,
		Destroy:     cancel, // Destroy stops originated goroutines and close connections.
	}

	go connMgr.housekeeper(ctx, hkInterval)

	return connMgr
}

// closeUnused closes each connection that has not been accessed at least within the keepalive interval.
func (c *ConnManager) closeUnused() {
	c.connMutex.Lock()
	defer c.connMutex.Unlock()

	for uri, conn := range c.connections {
		if time.Since(conn.lastTimeAccess) > c.keepAlive {
			if err := conn.client.Close(); err == nil {
				delete(c.connections, uri)
				log.Debugf("[%s] Closed unused connection: %s", pluginName, uri.Addr())
			} else {
				log.Errf("[%s] Error occurred while closing connection: %s", pluginName, uri.Addr())
			}
		}
	}
}

// closeAll closes all existed connections.
func (c *ConnManager) closeAll() {
	c.connMutex.Lock()
	for uri, conn := range c.connections {
		if err := conn.client.Close(); err == nil {
			delete(c.connections, uri)
		} else {
			log.Errf("[%s] Error occurred while closing connection: %s", pluginName, uri.Addr())
		}
	}
	c.connMutex.Unlock()
}

// housekeeper repeatedly checks for unused connections and close them.
func (c *ConnManager) housekeeper(ctx context.Context, interval time.Duration) {
	ticker := time.NewTicker(interval)

	for {
		select {
		case <-ctx.Done():
			ticker.Stop()
			c.closeAll()

			return
		case <-ticker.C:
			c.closeUnused()
		}
	}
}

// create creates a new connection with a given URI and password.
func (c *ConnManager) create(uri URI) (*RedisConn, error) {
	const clientName = "zbx_monitor"

	const poolSize = 1

	c.connMutex.Lock()
	defer c.connMutex.Unlock()

	if _, ok := c.connections[uri]; ok {
		// Should never happen.
		panic("connection already exists")
	}

	// AuthConnFunc is used as radix.ConnFunc to perform AUTH and set timeout
	AuthConnFunc := func(scheme, addr string) (conn radix.Conn, err error) {
		conn, err = radix.Dial(scheme, addr,
			radix.DialTimeout(c.timeout),
			radix.DialAuthPass(uri.Password()))

		// Set name for connection. It will be showed in "client list" output.
		if err == nil {
			err = conn.Do(radix.Cmd(nil, "CLIENT", "SETNAME", clientName))
		}

		return conn, err
	}

	client, err := radix.NewPool(uri.Scheme(), uri.Addr(), poolSize, radix.PoolConnFunc(AuthConnFunc))
	if err != nil {
		return nil, err
	}

	c.connections[uri] = &RedisConn{
		client:         client,
		lastTimeAccess: time.Now(),
	}

	log.Debugf("[%s] Created new connection: %s", pluginName, uri.Addr())

	return c.connections[uri], nil
}

// get returns a connection with given cid if it exists and also updates lastTimeAccess, otherwise returns nil.
func (c *ConnManager) get(uri URI) *RedisConn {
	c.connMutex.Lock()
	defer c.connMutex.Unlock()

	if conn, ok := c.connections[uri]; ok {
		conn.updateAccessTime()
		return conn
	}

	return nil
}

// GetConnection returns an existing connection or creates a new one.
func (c *ConnManager) GetConnection(uri URI) (conn *RedisConn, err error) {
	c.Lock()
	defer c.Unlock()

	conn = c.get(uri)

	if conn == nil {
		conn, err = c.create(uri)
	}

	return conn, err
}
