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
	"errors"
	"net/url"
	"strconv"
	"strings"
)

type URI struct {
	scheme   string
	host     string
	port     string
	socket   string
	user     string
	password string
}

func (u *URI) Scheme() string {
	return u.scheme
}

func (u *URI) Addr() string {
	if u.socket != "" {
		return u.socket
	}

	return u.host + ":" + u.port
}

func (u *URI) Password() string {
	return u.password
}

func (u *URI) User() string {
	return u.user
}

func (u *URI) URI() string {
	if len(u.user) == 0 || len(u.password) == 0 {
		return u.scheme + "://" + u.Addr()
	}

	return u.scheme + "://" + u.user + ":" + u.password + "@" + u.Addr()
}

func newURIWithCreds(uri, user, password string) (res *URI, err error) {
	res, err = parseURI(uri)

	if err == nil {
		res.password = password
		res.user = user
	}

	return res, err
}

const DefaultPort = "6379"

// parseURI splits a given URI to scheme, host:port/socket and returns a URI structure.
// It uses DefaultPort if a URI does not consist of port. The only allowed schemes are: tcp and unix.
// If an error occurs it returns error and an empty structure.
// It ignores embedded credentials according to https://www.ietf.org/rfc/rfc3986.txt.
func parseURI(uri string) (res *URI, err error) {
	res = &URI{}

	if u, err := url.Parse(uri); err == nil {
		switch strings.ToLower(u.Scheme) {
		case "tcp":
			res.host = u.Hostname()
			if len(res.host) == 0 {
				return nil, errors.New("host is required")
			}

			port := u.Port()

			if portInt, err := strconv.Atoi(port); err == nil {
				if portInt < 1 || portInt > 65535 {
					return nil, errors.New("port must be integer and must be between 1 and 65535")
				}
			}

			if len(port) == 0 {
				port = DefaultPort
			}

			res.port = port

		case "unix":
			if len(u.Path) == 0 {
				return nil, errors.New("socket is required")
			}

			res.socket = u.Path

		default:
			return nil, errors.New("the only supported schemes are: tcp and unix")
		}

		res.scheme = u.Scheme
	} else {
		return nil, errors.New("failed to parse connection string")
	}

	return res, err
}

// validateURI wraps parseURI in order to return a comprehensible error when validating a URI.
func validateURI(uri string) (err error) {
	_, err = parseURI(uri)

	return
}

// isLooksLikeURI returns true if s is URI or false if not
func isLooksLikeURI(s string) bool {
	return strings.Contains(s, "tcp://") || strings.Contains(s, "unix:/")
}
