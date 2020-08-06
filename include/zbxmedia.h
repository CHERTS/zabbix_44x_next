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

#ifndef ZABBIX_ZBXMEDIA_H
#define ZABBIX_ZBXMEDIA_H

#include "sysinc.h" /* using "config.h" would be better, but it causes warnings when compiled with Net-SNMP */

#define ZBX_MEDIA_CONTENT_TYPE_TEXT	0
#define ZBX_MEDIA_CONTENT_TYPE_HTML	1

extern char	*CONFIG_SOURCE_IP;

typedef struct
{
	char		*addr;
	char		*disp_name;
}
zbx_mailaddr_t;

int	send_email(const char *smtp_server, unsigned short smtp_port, const char *smtp_helo,
		const char *smtp_email, const char *mailto, const char *mailsubject, const char *mailbody,
		unsigned char smtp_security, unsigned char smtp_verify_peer, unsigned char smtp_verify_host,
		unsigned char smtp_authentication, const char *username, const char *password,
		unsigned char content_type, int timeout, char *error, size_t max_error_len);
int	send_sms(const char *device, const char *number, const char *message, char *error, int max_error_len);

#endif
