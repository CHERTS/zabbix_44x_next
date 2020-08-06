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
	"fmt"
	"github.com/mediocregopher/radix/v3"
)

const slowlogMaxParams = 0

type slowlog []interface{}
type logItem = []interface{}

// getLastSlowlogID gets the last log item ID from slowlog.
func getLastSlowlogID(sl slowlog) (int64, error) {
	if len(sl) == 0 {
		return 0, nil
	}

	item, ok := sl[0].(logItem)
	if !ok {
		return 0, errorCannotParseData
	}

	if len(item) == 0 {
		return 0, errorCannotParseData
	}

	id, ok := item[0].(int64)
	if !ok {
		return 0, errorCannotParseData
	}

	return id + 1, nil
}

// slowlogHandler gets an output of 'SLOWLOG GET 1' command and returns the last slowlog Id.
func slowlogHandler(conn redisClient, params []string) (interface{}, error) {
	var res []interface{}

	if len(params) > slowlogMaxParams {
		return nil, errorInvalidParams
	}

	if err := conn.Query(radix.Cmd(&res, "SLOWLOG", "GET", "1")); err != nil {
		return nil, fmt.Errorf("%s (%w)", err.Error(), errorCannotFetchData)
	}

	lastID, err := getLastSlowlogID(slowlog(res))

	return lastID, err
}
