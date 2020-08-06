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

#include "common.h"
#include "comms.h"
#include "db.h"
#include "log.h"
#include "dbcache.h"

#include "operations.h"
#include "zbxserver.h"

typedef enum
{
	ZBX_DISCOVERY_UNSPEC = 0,
	ZBX_DISCOVERY_DNS,
	ZBX_DISCOVERY_IP,
	ZBX_DISCOVERY_VALUE
}
zbx_dcheck_source_t;

/******************************************************************************
 *                                                                            *
 * Function: select_discovered_host                                           *
 *                                                                            *
 * Purpose: select hostid of discovered host                                  *
 *                                                                            *
 * Parameters: dhostid - discovered host id                                   *
 *                                                                            *
 * Return value: hostid - existing hostid, 0 - if not found                   *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 ******************************************************************************/
static zbx_uint64_t	select_discovered_host(const DB_EVENT *event)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	hostid = 0, proxy_hostid;
	char		*sql = NULL, *ip_esc;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() eventid:" ZBX_FS_UI64, __func__, event->eventid);

	switch (event->object)
	{
		case EVENT_OBJECT_DHOST:
		case EVENT_OBJECT_DSERVICE:
			result = DBselect(
					"select dr.proxy_hostid,ds.ip"
					" from drules dr,dchecks dc,dservices ds"
					" where dc.druleid=dr.druleid"
						" and ds.dcheckid=dc.dcheckid"
						" and ds.%s=" ZBX_FS_UI64,
					EVENT_OBJECT_DSERVICE == event->object ? "dserviceid" : "dhostid",
					event->objectid);

			if (NULL == (row = DBfetch(result)))
			{
				DBfree_result(result);
				goto exit;
			}

			ZBX_DBROW2UINT64(proxy_hostid, row[0]);
			ip_esc = DBdyn_escape_string(row[1]);
			DBfree_result(result);

			sql = zbx_dsprintf(sql,
					"select h.hostid"
					" from hosts h,interface i"
					" where h.hostid=i.hostid"
						" and i.ip='%s'"
						" and i.useip=1"
						" and h.status in (%d,%d)"
						" and h.proxy_hostid%s"
					" order by i.hostid",
					ip_esc,
					HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED,
					DBsql_id_cmp(proxy_hostid));

			zbx_free(ip_esc);
			break;
		case EVENT_OBJECT_ZABBIX_ACTIVE:
			sql = zbx_dsprintf(sql,
					"select h.hostid"
					" from hosts h,autoreg_host a"
					" where h.host=a.host"
						" and a.autoreg_hostid=" ZBX_FS_UI64
						" and h.status in (%d,%d)",
					event->objectid,
					HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED);
			break;
		default:
			goto exit;
	}

	result = DBselectN(sql, 1);

	zbx_free(sql);

	if (NULL != (row = DBfetch(result)))
		ZBX_STR2UINT64(hostid, row[0]);
	DBfree_result(result);
exit:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():" ZBX_FS_UI64, __func__, hostid);

	return hostid;
}

/******************************************************************************
 *                                                                            *
 * Function: add_discovered_host_groups                                       *
 *                                                                            *
 * Purpose: add group to host if not added already                            *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 ******************************************************************************/
static void	add_discovered_host_groups(zbx_uint64_t hostid, zbx_vector_uint64_t *groupids)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	groupid;
	char		*sql = NULL;
	size_t		sql_alloc = 256, sql_offset = 0;
	int		i;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	sql = (char *)zbx_malloc(sql, sql_alloc);

	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
			"select groupid"
			" from hosts_groups"
			" where hostid=" ZBX_FS_UI64
				" and",
			hostid);
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "groupid", groupids->values, groupids->values_num);

	result = DBselect("%s", sql);

	zbx_free(sql);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(groupid, row[0]);

		if (FAIL == (i = zbx_vector_uint64_search(groupids, groupid, ZBX_DEFAULT_UINT64_COMPARE_FUNC)))
		{
			THIS_SHOULD_NEVER_HAPPEN;
			continue;
		}

		zbx_vector_uint64_remove_noorder(groupids, i);
	}
	DBfree_result(result);

	if (0 != groupids->values_num)
	{
		zbx_uint64_t	hostgroupid;
		zbx_db_insert_t	db_insert;

		hostgroupid = DBget_maxid_num("hosts_groups", groupids->values_num);

		zbx_db_insert_prepare(&db_insert, "hosts_groups", "hostgroupid", "hostid", "groupid", NULL);

		zbx_vector_uint64_sort(groupids, ZBX_DEFAULT_UINT64_COMPARE_FUNC);

		for (i = 0; i < groupids->values_num; i++)
		{
			zbx_db_insert_add_values(&db_insert, hostgroupid++, hostid, groupids->values[i]);
		}

		zbx_db_insert_execute(&db_insert);
		zbx_db_insert_clean(&db_insert);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

/******************************************************************************
 *                                                                            *
 * Function: add_discovered_host                                              *
 *                                                                            *
 * Purpose: add discovered host if it was not added already                   *
 *                                                                            *
 * Parameters:                                                                *
 *                                                                            *
 * Return value: hostid - new/existing hostid                                 *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 ******************************************************************************/
static zbx_uint64_t	add_discovered_host(const DB_EVENT *event)
{
	DB_RESULT		result;
	DB_RESULT		result2;
	DB_ROW			row;
	DB_ROW			row2;
	zbx_uint64_t		dhostid, hostid = 0, proxy_hostid, druleid;
	char			*host, *host_esc, *host_unique, *host_visible, *host_visible_unique;
	unsigned short		port;
	zbx_vector_uint64_t	groupids;
	unsigned char		svc_type, interface_type;
	zbx_config_t		cfg;
	zbx_db_insert_t		db_insert;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() eventid:" ZBX_FS_UI64, __func__, event->eventid);

	zbx_vector_uint64_create(&groupids);

	zbx_config_get(&cfg, ZBX_CONFIG_FLAGS_DISCOVERY_GROUPID | ZBX_CONFIG_FLAGS_DEFAULT_INVENTORY_MODE);

	if (ZBX_DISCOVERY_GROUPID_UNDEFINED == cfg.discovery_groupid)
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot add discovered host: group for discovered hosts is not defined");
		goto clean;
	}

	zbx_vector_uint64_append(&groupids, cfg.discovery_groupid);

	if (EVENT_OBJECT_DHOST == event->object || EVENT_OBJECT_DSERVICE == event->object)
	{
		if (EVENT_OBJECT_DHOST == event->object)
		{
			result = DBselect(
					"select ds.dhostid,dr.proxy_hostid,ds.ip,ds.dns,ds.port,dc.type,"
						"dc.host_source,dc.name_source,dr.druleid"
					" from drules dr,dchecks dc,dservices ds"
					" where dc.druleid=dr.druleid"
						" and ds.dcheckid=dc.dcheckid"
						" and ds.dhostid=" ZBX_FS_UI64
					" order by ds.dserviceid",
					event->objectid);
		}
		else
		{
			result = DBselect(
					"select ds.dhostid,dr.proxy_hostid,ds.ip,ds.dns,ds.port,dc.type,"
						"dc.host_source,dc.name_source,dr.druleid"
					" from drules dr,dchecks dc,dservices ds,dservices ds1"
					" where dc.druleid=dr.druleid"
						" and ds.dcheckid=dc.dcheckid"
						" and ds1.dhostid=ds.dhostid"
						" and ds1.dserviceid=" ZBX_FS_UI64
					" order by ds.dserviceid",
					event->objectid);
		}

		while (NULL != (row = DBfetch(result)))
		{
			ZBX_STR2UINT64(dhostid, row[0]);
			ZBX_STR2UINT64(druleid, row[8]);
			ZBX_DBROW2UINT64(proxy_hostid, row[1]);
			svc_type = (unsigned char)atoi(row[5]);

			switch (svc_type)
			{
				case SVC_AGENT:
					port = (unsigned short)atoi(row[4]);
					interface_type = INTERFACE_TYPE_AGENT;
					break;
				case SVC_SNMPv1:
				case SVC_SNMPv2c:
				case SVC_SNMPv3:
					port = (unsigned short)atoi(row[4]);
					interface_type = INTERFACE_TYPE_SNMP;
					break;
				default:
					port = ZBX_DEFAULT_AGENT_PORT;
					interface_type = INTERFACE_TYPE_AGENT;
			}

			if (0 == hostid)
			{
				result2 = DBselect(
						"select distinct h.hostid"
						" from hosts h,interface i,dservices ds"
						" where h.hostid=i.hostid"
							" and i.ip=ds.ip"
							" and h.status in (%d,%d)"
							" and h.proxy_hostid%s"
							" and ds.dhostid=" ZBX_FS_UI64
						" order by h.hostid",
						HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED,
						DBsql_id_cmp(proxy_hostid), dhostid);

				if (NULL != (row2 = DBfetch(result2)))
					ZBX_STR2UINT64(hostid, row2[0]);

				DBfree_result(result2);
			}

			if (0 == hostid)
			{
				DB_RESULT		result3;
				DB_ROW			row3;
				zbx_dcheck_source_t	host_source, name_source;
				char			*sql = NULL;
				size_t			sql_alloc, sql_offset;

				zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
						"select ds.value"
						" from dchecks dc"
							" left join dservices ds"
								" on ds.dcheckid=dc.dcheckid"
									" and ds.dhostid=" ZBX_FS_UI64
						" where dc.druleid=" ZBX_FS_UI64
							" and dc.host_source=%d"
						" order by ds.dserviceid",
							dhostid, druleid, ZBX_DISCOVERY_VALUE);

				result3 = DBselectN(sql, 1);

				if (NULL != (row3 = DBfetch(result3)))
				{
					if (SUCCEED == zbx_db_is_null(row3[0]) || '\0' == *row3[0])
					{
						zabbix_log(LOG_LEVEL_WARNING, "cannot retrieve service value for"
								" host name on \"%s\"", row[2]);
						host_source = ZBX_DISCOVERY_DNS;
					}
					else
						host_source = ZBX_DISCOVERY_VALUE;
				}
				else
				{
					if (ZBX_DISCOVERY_VALUE == (host_source = atoi(row[6])))
					{
						zabbix_log(LOG_LEVEL_WARNING, "cannot retrieve service value for"
								" host name on \"%s\"", row[2]);
						host_source = ZBX_DISCOVERY_DNS;
					}
				}

				if (ZBX_DISCOVERY_VALUE == host_source)
					host = zbx_strdup(NULL, row3[0]);
				else if (ZBX_DISCOVERY_IP == host_source || '\0' == *row[3])
					host = zbx_strdup(NULL, row[2]);
				else
					host = zbx_strdup(NULL, row[3]);

				DBfree_result(result3);

				/* for host uniqueness purposes */
				make_hostname(host);	/* replace not-allowed symbols */
				host_unique = DBget_unique_hostname_by_sample(host, "host");
				zbx_free(host);

				sql_offset = 0;
				zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
						"select ds.value"
						" from dchecks dc"
							" left join dservices ds"
								" on ds.dcheckid=dc.dcheckid"
									" and ds.dhostid=" ZBX_FS_UI64
						" where dc.druleid=" ZBX_FS_UI64
							" and dc.host_source in (%d,%d,%d,%d)"
							" and dc.name_source=%d"
						" order by ds.dserviceid",
							dhostid, druleid, ZBX_DISCOVERY_UNSPEC, ZBX_DISCOVERY_DNS,
							ZBX_DISCOVERY_IP, ZBX_DISCOVERY_VALUE, ZBX_DISCOVERY_VALUE);

				result3 = DBselectN(sql, 1);

				if (NULL != (row3 = DBfetch(result3)))
				{
					if (SUCCEED == zbx_db_is_null(row3[0]) || '\0' == *row3[0])
					{
						zabbix_log(LOG_LEVEL_WARNING, "cannot retrieve service value for"
								" host visible name on \"%s\"", row[2]);
						name_source = ZBX_DISCOVERY_UNSPEC;
					}
					else
						name_source = ZBX_DISCOVERY_VALUE;
				}
				else
				{
					if (ZBX_DISCOVERY_VALUE == (name_source = atoi(row[7])))
					{
						zabbix_log(LOG_LEVEL_WARNING, "cannot retrieve service value for"
								" host visible name on \"%s\"", row[2]);
						name_source = ZBX_DISCOVERY_UNSPEC;
					}
				}

				if (ZBX_DISCOVERY_VALUE == name_source)
					host_visible = zbx_strdup(NULL, row3[0]);
				else if (ZBX_DISCOVERY_IP == name_source ||
						(ZBX_DISCOVERY_DNS == name_source && '\0' == *row[3]))
					host_visible = zbx_strdup(NULL, row[2]);
				else if (ZBX_DISCOVERY_DNS == name_source)
					host_visible = zbx_strdup(NULL, row[3]);
				else
					host_visible = zbx_strdup(NULL, host_unique);

				DBfree_result(result3);
				zbx_free(sql);

				make_hostname(host_visible);	/* replace not-allowed symbols */
				host_visible_unique = DBget_unique_hostname_by_sample(host_visible, "name");
				zbx_free(host_visible);

				hostid = DBget_maxid("hosts");

				zbx_db_insert_prepare(&db_insert, "hosts", "hostid", "proxy_hostid", "host", "name",
						NULL);
				zbx_db_insert_add_values(&db_insert, hostid, proxy_hostid, host_unique,
						host_visible_unique);
				zbx_db_insert_execute(&db_insert);
				zbx_db_insert_clean(&db_insert);

				if (HOST_INVENTORY_DISABLED != cfg.default_inventory_mode)
					DBadd_host_inventory(hostid, cfg.default_inventory_mode);

				DBadd_interface(hostid, interface_type, 1, row[2], row[3], port, ZBX_CONN_DEFAULT);

				zbx_free(host_unique);
				zbx_free(host_visible_unique);

				add_discovered_host_groups(hostid, &groupids);
			}
			else
				DBadd_interface(hostid, interface_type, 1, row[2], row[3], port, ZBX_CONN_DEFAULT);
		}
		DBfree_result(result);
	}
	else if (EVENT_OBJECT_ZABBIX_ACTIVE == event->object)
	{
		result = DBselect(
				"select proxy_hostid,host,listen_ip,listen_dns,listen_port,flags,tls_accepted"
				" from autoreg_host"
				" where autoreg_hostid=" ZBX_FS_UI64,
				event->objectid);

		if (NULL != (row = DBfetch(result)))
		{
			char			*sql = NULL;
			zbx_uint64_t		host_proxy_hostid;
			zbx_conn_flags_t	flags;
			int			flags_int;
			unsigned char		useip = 1;
			int			tls_accepted;

			ZBX_DBROW2UINT64(proxy_hostid, row[0]);
			host_esc = DBdyn_escape_field("hosts", "host", row[1]);
			port = (unsigned short)atoi(row[4]);
			flags_int = atoi(row[5]);

			switch (flags_int)
			{
				case ZBX_CONN_DEFAULT:
				case ZBX_CONN_IP:
				case ZBX_CONN_DNS:
					flags = (zbx_conn_flags_t)flags_int;
					break;
				default:
					flags = ZBX_CONN_DEFAULT;
					zabbix_log(LOG_LEVEL_WARNING, "wrong flags value: %d for host \"%s\":",
							flags_int, row[1]);
			}

			if (ZBX_CONN_DNS == flags)
				useip = 0;

			tls_accepted = atoi(row[6]);

			result2 = DBselect(
					"select null"
					" from hosts"
					" where host='%s'"
						" and status=%d",
					host_esc, HOST_STATUS_TEMPLATE);

			if (NULL != (row2 = DBfetch(result2)))
			{
				zabbix_log(LOG_LEVEL_WARNING, "cannot add discovered host \"%s\":"
						" template with the same name already exists", row[1]);
				DBfree_result(result2);
				goto out;
			}
			DBfree_result(result2);

			sql = zbx_dsprintf(sql,
					"select hostid,proxy_hostid"
					" from hosts"
					" where host='%s'"
						" and flags<>%d"
						" and status in (%d,%d)"
					" order by hostid",
					host_esc, ZBX_FLAG_DISCOVERY_PROTOTYPE,
					HOST_STATUS_MONITORED, HOST_STATUS_NOT_MONITORED);

			result2 = DBselectN(sql, 1);

			zbx_free(sql);

			if (NULL == (row2 = DBfetch(result2)))
			{
				hostid = DBget_maxid("hosts");

				if (ZBX_TCP_SEC_TLS_PSK == tls_accepted)
				{
					char	psk_identity[HOST_TLS_PSK_IDENTITY_LEN_MAX];
					char	psk[HOST_TLS_PSK_LEN_MAX];

					DCget_autoregistration_psk(psk_identity, sizeof(psk_identity),
							(unsigned char *)psk, sizeof(psk));

					zbx_db_insert_prepare(&db_insert, "hosts", "hostid", "proxy_hostid",
							"host", "name", "tls_connect", "tls_accept",
							"tls_psk_identity", "tls_psk", NULL);
					zbx_db_insert_add_values(&db_insert, hostid, proxy_hostid, row[1], row[1],
						tls_accepted, tls_accepted, psk_identity, psk);
				}
				else
				{
					zbx_db_insert_prepare(&db_insert, "hosts", "hostid", "proxy_hostid", "host",
							"name", NULL);
					zbx_db_insert_add_values(&db_insert, hostid, proxy_hostid, row[1], row[1]);
				}

				zbx_db_insert_execute(&db_insert);
				zbx_db_insert_clean(&db_insert);

				if (HOST_INVENTORY_DISABLED != cfg.default_inventory_mode)
					DBadd_host_inventory(hostid, cfg.default_inventory_mode);

				DBadd_interface(hostid, INTERFACE_TYPE_AGENT, useip, row[2], row[3], port, flags);

				add_discovered_host_groups(hostid, &groupids);
			}
			else
			{
				ZBX_STR2UINT64(hostid, row2[0]);
				ZBX_DBROW2UINT64(host_proxy_hostid, row2[1]);

				if (host_proxy_hostid != proxy_hostid)
				{
					DBexecute("update hosts"
							" set proxy_hostid=%s"
							" where hostid=" ZBX_FS_UI64,
							DBsql_id_ins(proxy_hostid), hostid);
				}

				DBadd_interface(hostid, INTERFACE_TYPE_AGENT, useip, row[2], row[3], port, flags);
			}
			DBfree_result(result2);
out:
			zbx_free(host_esc);
		}
		DBfree_result(result);
	}
clean:
	zbx_config_clean(&cfg);

	zbx_vector_uint64_destroy(&groupids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);

	return hostid;
}

/******************************************************************************
 *                                                                            *
 * Function: is_discovery_or_auto_registration                                *
 *                                                                            *
 * Purpose: checks if the event is discovery or auto registration event       *
 *                                                                            *
 * Return value: SUCCEED - it's discovery or auto registration event          *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	is_discovery_or_auto_registration(const DB_EVENT *event)
{
	if (event->source == EVENT_SOURCE_DISCOVERY && (event->object == EVENT_OBJECT_DHOST ||
			event->object == EVENT_OBJECT_DSERVICE))
	{
		return SUCCEED;
	}

	if (event->source == EVENT_SOURCE_AUTO_REGISTRATION && event->object == EVENT_OBJECT_ZABBIX_ACTIVE)
		return SUCCEED;

	return FAIL;
}

/******************************************************************************
 *                                                                            *
 * Function: op_host_add                                                      *
 *                                                                            *
 * Purpose: add discovered host                                               *
 *                                                                            *
 * Parameters: trigger - trigger data                                         *
 *             action  - action data                                          *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 ******************************************************************************/
void	op_host_add(const DB_EVENT *event)
{
	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (FAIL == is_discovery_or_auto_registration(event))
		return;

	add_discovered_host(event);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

/******************************************************************************
 *                                                                            *
 * Function: op_host_del                                                      *
 *                                                                            *
 * Purpose: delete host                                                       *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 ******************************************************************************/
void	op_host_del(const DB_EVENT *event)
{
	zbx_vector_uint64_t	hostids;
	zbx_uint64_t		hostid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (FAIL == is_discovery_or_auto_registration(event))
		return;

	if (0 == (hostid = select_discovered_host(event)))
		return;

	zbx_vector_uint64_create(&hostids);

	zbx_vector_uint64_append(&hostids, hostid);

	DBdelete_hosts_with_prototypes(&hostids);

	zbx_vector_uint64_destroy(&hostids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

/******************************************************************************
 *                                                                            *
 * Function: op_host_enable                                                   *
 *                                                                            *
 * Purpose: enable discovered                                                 *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 ******************************************************************************/
void	op_host_enable(const DB_EVENT *event)
{
	zbx_uint64_t	hostid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (FAIL == is_discovery_or_auto_registration(event))
		return;

	if (0 == (hostid = add_discovered_host(event)))
		return;

	DBexecute(
			"update hosts"
			" set status=%d"
			" where hostid=" ZBX_FS_UI64,
			HOST_STATUS_MONITORED,
			hostid);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

/******************************************************************************
 *                                                                            *
 * Function: op_host_disable                                                  *
 *                                                                            *
 * Purpose: disable host                                                      *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 ******************************************************************************/
void	op_host_disable(const DB_EVENT *event)
{
	zbx_uint64_t	hostid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (FAIL == is_discovery_or_auto_registration(event))
		return;

	if (0 == (hostid = add_discovered_host(event)))
		return;

	DBexecute(
			"update hosts"
			" set status=%d"
			" where hostid=" ZBX_FS_UI64,
			HOST_STATUS_NOT_MONITORED,
			hostid);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

/******************************************************************************
 *                                                                            *
 * Function: op_host_inventory_mode                                           *
 *                                                                            *
 * Purpose: sets host inventory mode                                          *
 *                                                                            *
 * Parameters: event          - [IN] the source event                         *
 *             inventory_mode - [IN] the new inventory mode, see              *
 *                              HOST_INVENTORY_ defines                       *
 *                                                                            *
 * Comments: This function does not allow disabling host inventory - only     *
 *           setting manual or automatic host inventory mode is supported.    *
 *                                                                            *
 ******************************************************************************/
void	op_host_inventory_mode(const DB_EVENT *event, int inventory_mode)
{
	zbx_uint64_t	hostid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (FAIL == is_discovery_or_auto_registration(event))
		return;

	if (0 == (hostid = add_discovered_host(event)))
		return;

	DBset_host_inventory(hostid, inventory_mode);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

/******************************************************************************
 *                                                                            *
 * Function: op_groups_add                                                    *
 *                                                                            *
 * Purpose: add groups to discovered host                                     *
 *                                                                            *
 * Parameters: event    - [IN] event data                                     *
 *             groupids - [IN] IDs of groups to add                           *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 ******************************************************************************/
void	op_groups_add(const DB_EVENT *event, zbx_vector_uint64_t *groupids)
{
	zbx_uint64_t	hostid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (FAIL == is_discovery_or_auto_registration(event))
		return;

	if (0 == (hostid = add_discovered_host(event)))
		return;

	add_discovered_host_groups(hostid, groupids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

/******************************************************************************
 *                                                                            *
 * Function: op_groups_del                                                    *
 *                                                                            *
 * Purpose: delete groups from discovered host                                *
 *                                                                            *
 * Parameters: event    - [IN] event data                                     *
 *             groupids - [IN] IDs of groups to delete                        *
 *                                                                            *
 * Author: Alexei Vladishev                                                   *
 *                                                                            *
 ******************************************************************************/
void	op_groups_del(const DB_EVENT *event, zbx_vector_uint64_t *groupids)
{
	DB_RESULT	result;
	zbx_uint64_t	hostid;
	char		*sql = NULL;
	size_t		sql_alloc = 256, sql_offset = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (FAIL == is_discovery_or_auto_registration(event))
		return;

	if (0 == (hostid = select_discovered_host(event)))
		return;

	sql = (char *)zbx_malloc(sql, sql_alloc);

	/* make sure host belongs to at least one hostgroup */
	zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
			"select groupid"
			" from hosts_groups"
			" where hostid=" ZBX_FS_UI64
				" and not",
			hostid);
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "groupid", groupids->values, groupids->values_num);

	result = DBselectN(sql, 1);

	if (NULL == DBfetch(result))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot remove host \"%s\" from all host groups:"
				" it must belong to at least one", zbx_host_string(hostid));
	}
	else
	{
		sql_offset = 0;
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset,
				"delete from hosts_groups"
				" where hostid=" ZBX_FS_UI64
					" and",
				hostid);
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "groupid", groupids->values, groupids->values_num);

		DBexecute("%s", sql);
	}
	DBfree_result(result);

	zbx_free(sql);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

/******************************************************************************
 *                                                                            *
 * Function: op_template_add                                                  *
 *                                                                            *
 * Purpose: link host with template                                           *
 *                                                                            *
 * Parameters: event           - [IN] event data                              *
 *             lnk_templateids - [IN] array of template IDs                   *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 ******************************************************************************/
void	op_template_add(const DB_EVENT *event, zbx_vector_uint64_t *lnk_templateids)
{
	zbx_uint64_t	hostid;
	char		*error;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (FAIL == is_discovery_or_auto_registration(event))
		return;

	if (0 == (hostid = add_discovered_host(event)))
		return;

	if (SUCCEED != DBcopy_template_elements(hostid, lnk_templateids, &error))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot link template(s) %s", error);
		zbx_free(error);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

/******************************************************************************
 *                                                                            *
 * Function: op_template_del                                                  *
 *                                                                            *
 * Purpose: unlink and clear host from template                               *
 *                                                                            *
 * Parameters: event           - [IN] event data                              *
 *             lnk_templateids - [IN] array of template IDs                   *
 *                                                                            *
 * Author: Eugene Grigorjev                                                   *
 *                                                                            *
 ******************************************************************************/
void	op_template_del(const DB_EVENT *event, zbx_vector_uint64_t *del_templateids)
{
	zbx_uint64_t	hostid;
	char		*error;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (FAIL == is_discovery_or_auto_registration(event))
		return;

	if (0 == (hostid = select_discovered_host(event)))
		return;

	if (SUCCEED != DBdelete_template_elements(hostid, del_templateids, &error))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot unlink template: %s", error);
		zbx_free(error);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}
