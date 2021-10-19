/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
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

package vfsfs

import (
	"fmt"
	"sync"
	"time"
)

const timeout = 1

var stuckMounts map[string]bool
var stuckMux sync.Mutex

type fsCaller struct {
	fsFunc  func(path string) (stats *FsStats, err error)
	paths   []string
	errChan chan error
	outChan chan *FsStats
	p       *Plugin
}

func (f *fsCaller) run(path string) (stat *FsStats, err error) {
	if isStuck(path) {
		return nil, fmt.Errorf("mount '%s' is unavailable", path)
	}

	go f.execute(path)
	select {
	case stat := <-f.outChan:
		return stat, nil
	case err := <-f.errChan:
		return nil, err
	case <-time.After(timeout * time.Second):
		stuckMux.Lock()
		stuckMounts[path] = true
		stuckMux.Unlock()

		return nil, fmt.Errorf("operation on mount '%s' timed out", path)
	}
}

func (f *fsCaller) execute(path string) {
	stats, err := f.fsFunc(path)

	if isStuck(path) {
		f.p.Debugf("mount '%s' has become available", path)
		stuckMux.Lock()
		stuckMounts[path] = false
		stuckMux.Unlock()
		return
	}

	if err != nil {
		f.errChan <- err
		return
	}

	f.outChan <- stats
}

func isStuck(path string) bool {
	stuckMux.Lock()
	defer stuckMux.Unlock()
	if stuckMounts[path] {
		return true
	}

	return false
}

func (fc *fsCaller) close() {
	close(fc.errChan)
	close(fc.outChan)
}

func (p *Plugin) newFSCaller(fsFunc func(path string) (stats *FsStats, err error), fsLen int) *fsCaller {
	fc := fsCaller{}
	fc.fsFunc = fsFunc
	fc.errChan = make(chan error, fsLen)
	fc.outChan = make(chan *FsStats, fsLen)
	fc.p = p

	return &fc
}

func init() {
	stuckMounts = make(map[string]bool)
}
