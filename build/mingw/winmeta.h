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

#ifndef ZABBIX_WINMETA_H
#define ZABBIX_WINMETA_H

#define WINEVENT_LEVEL_LOG_ALWAYS	0x0
#define WINEVENT_LEVEL_CRITICAL		0x1
#define WINEVENT_LEVEL_ERROR		0x2
#define WINEVENT_LEVEL_WARNING		0x3
#define WINEVENT_LEVEL_INFO		0x4
#define WINEVENT_LEVEL_VERBOSE		0x5

#define WINEVENT_KEYWORD_AUDIT_FAILURE	0x10000000000000
#define WINEVENT_KEYWORD_AUDIT_SUCCESS	0x20000000000000

#endif
