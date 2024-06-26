/*
** Zabbix
** Copyright (C) 2001-2022 Zabbix SIA
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

#ifndef ZABBIX_LLD_H
#define ZABBIX_LLD_H

#include "common.h"

void	zbx_lld_process_value(zbx_uint64_t itemid, zbx_uint64_t hostid, const char *value, const zbx_timespec_t *ts, unsigned char meta,
		zbx_uint64_t lastlogsize, int mtime, const char *error);

void	zbx_lld_process_agent_result(zbx_uint64_t itemid, zbx_uint64_t hostid, AGENT_RESULT *result, zbx_timespec_t *ts, char *error);

int	zbx_lld_get_queue_size(zbx_uint64_t *size, char **error);

#endif	/* ZABBIX_LLD_H */
