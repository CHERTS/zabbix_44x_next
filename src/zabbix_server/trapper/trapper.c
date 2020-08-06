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
#include "log.h"
#include "zbxjson.h"
#include "zbxserver.h"
#include "dbcache.h"
#include "proxy.h"
#include "zbxself.h"

#include "trapper.h"
#include "active.h"
#include "nodecommand.h"
#include "proxyconfig.h"
#include "proxydata.h"
#include "../alerter/alerter_protocol.h"
#include "trapper_preproc.h"

#include "daemon.h"
#include "zbxcrypto.h"
#include "../../libs/zbxserver/zabbix_stats.h"
#include "zbxipcservice.h"

#define ZBX_MAX_SECTION_ENTRIES		4
#define ZBX_MAX_ENTRY_ATTRIBUTES	3

extern unsigned char	process_type, program_type;
extern int		server_num, process_num;
extern size_t		(*find_psk_in_cache)(const unsigned char *, unsigned char *, unsigned int *);

extern int	CONFIG_CONFSYNCER_FORKS;

typedef struct
{
	zbx_counter_value_t	online;
	zbx_counter_value_t	offline;
}
zbx_user_stats_t;

typedef union
{
	zbx_counter_value_t	counter;	/* single global counter */
	zbx_vector_ptr_t	counters;	/* array of per proxy counters */
}
zbx_entry_info_t;

typedef struct
{
	const char	*name;
	zbx_uint64_t	value;
}
zbx_entry_attribute_t;

typedef struct
{
	zbx_entry_info_t	*info;
	zbx_counter_type_t	counter_type;
	zbx_entry_attribute_t	attributes[ZBX_MAX_ENTRY_ATTRIBUTES];
}
zbx_section_entry_t;

typedef enum
{
	ZBX_SECTION_ENTRY_THE_ONLY,
	ZBX_SECTION_ENTRY_PER_PROXY
}
zbx_entry_type_t;

typedef struct
{
	const char		*name;
	zbx_entry_type_t	entry_type;
	zbx_user_type_t		access_level;
	int			*res;
	zbx_section_entry_t	entries[ZBX_MAX_SECTION_ENTRIES];
}
zbx_status_section_t;

/******************************************************************************
 *                                                                            *
 * Function: recv_agenthistory                                                *
 *                                                                            *
 * Purpose: processes the received values from active agents                  *
 *                                                                            *
 ******************************************************************************/
static void	recv_agenthistory(zbx_socket_t *sock, struct zbx_json_parse *jp, zbx_timespec_t *ts)
{
	char	*info = NULL;
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (SUCCEED != (ret = process_agent_history_data(sock, jp, ts, &info)))
	{
		zabbix_log(LOG_LEVEL_WARNING, "received invalid agent history data from \"%s\": %s", sock->peer, info);
	}
	else if (!ZBX_IS_RUNNING())
	{
		info = zbx_strdup(info, "Zabbix server shutdown in progress");
		zabbix_log(LOG_LEVEL_WARNING, "cannot receive agent history data from \"%s\": %s", sock->peer, info);
		ret = FAIL;
	}

	zbx_send_response_same(sock, ret, info, CONFIG_TIMEOUT);

	zbx_free(info);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

/******************************************************************************
 *                                                                            *
 * Function: recv_senderhistory                                               *
 *                                                                            *
 * Purpose: processes the received values from senders                        *
 *                                                                            *
 ******************************************************************************/
static void	recv_senderhistory(zbx_socket_t *sock, struct zbx_json_parse *jp, zbx_timespec_t *ts)
{
	char	*info = NULL;
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (SUCCEED != (ret = process_sender_history_data(sock, jp, ts, &info)))
	{
		zabbix_log(LOG_LEVEL_WARNING, "received invalid sender data from \"%s\": %s", sock->peer, info);
	}
	else if (!ZBX_IS_RUNNING())
	{
		info = zbx_strdup(info, "Zabbix server shutdown in progress");
		zabbix_log(LOG_LEVEL_WARNING, "cannot process sender data from \"%s\": %s", sock->peer, info);
		ret = FAIL;
	}

	zbx_send_response_same(sock, ret, info, CONFIG_TIMEOUT);

	zbx_free(info);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

/******************************************************************************
 *                                                                            *
 * Function: recv_proxy_heartbeat                                             *
 *                                                                            *
 * Purpose: process heartbeat sent by proxy servers                           *
 *                                                                            *
 * Return value:  SUCCEED - processed successfully                            *
 *                FAIL - an error occurred                                    *
 *                                                                            *
 * Author: Alexander Vladishev                                                *
 *                                                                            *
 ******************************************************************************/
static void	recv_proxy_heartbeat(zbx_socket_t *sock, struct zbx_json_parse *jp)
{
	char		*error = NULL;
	int		ret, flags = ZBX_TCP_PROTOCOL;
	DC_PROXY	proxy;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (SUCCEED != (ret = get_active_proxy_from_request(jp, &proxy, &error)))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot parse heartbeat from active proxy at \"%s\": %s",
				sock->peer, error);
		goto out;
	}

	if (SUCCEED != (ret = zbx_proxy_check_permissions(&proxy, sock, &error)))
	{
		zabbix_log(LOG_LEVEL_WARNING, "cannot accept connection from proxy \"%s\" at \"%s\", allowed address:"
				" \"%s\": %s", proxy.host, sock->peer, proxy.proxy_address, error);
		goto out;
	}

	zbx_update_proxy_data(&proxy, zbx_get_proxy_protocol_version(jp), time(NULL),
			(0 != (sock->protocol & ZBX_TCP_COMPRESS) ? 1 : 0));

	if (0 != proxy.auto_compress)
		flags |= ZBX_TCP_COMPRESS;
out:
	if (FAIL == ret && 0 != (sock->protocol & ZBX_TCP_COMPRESS))
		flags |= ZBX_TCP_COMPRESS;

	zbx_send_response_ext(sock, ret, error, NULL, flags, CONFIG_TIMEOUT);

	zbx_free(error);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);
}

#define ZBX_GET_QUEUE_UNKNOWN		-1
#define ZBX_GET_QUEUE_OVERVIEW		0
#define ZBX_GET_QUEUE_PROXY		1
#define ZBX_GET_QUEUE_DETAILS		2

/* queue stats split by delay times */
typedef struct
{
	zbx_uint64_t	id;
	int		delay5;
	int		delay10;
	int		delay30;
	int		delay60;
	int		delay300;
	int		delay600;
}
zbx_queue_stats_t;

/******************************************************************************
 *                                                                            *
 * Function: queue_stats_update                                               *
 *                                                                            *
 * Purpose: update queue stats with a new item delay                          *
 *                                                                            *
 * Parameters: stats   - [IN] the queue stats                                 *
 *             delay   - [IN] the delay time of an delayed item               *
 *                                                                            *
 ******************************************************************************/
static void	queue_stats_update(zbx_queue_stats_t *stats, int delay)
{
	if (10 >= delay)
		stats->delay5++;
	else if (30 >= delay)
		stats->delay10++;
	else if (60 >= delay)
		stats->delay30++;
	else if (300 >= delay)
		stats->delay60++;
	else if (600 >= delay)
		stats->delay300++;
	else
		stats->delay600++;
}

/******************************************************************************
 *                                                                            *
 * Function: queue_stats_export                                               *
 *                                                                            *
 * Purpose: export queue stats to JSON format                                 *
 *                                                                            *
 * Parameters: queue_stats - [IN] a hashset containing item stats             *
 *             id_name     - [IN] the name of stats id field                  *
 *             json        - [OUT] the output JSON                            *
 *                                                                            *
 ******************************************************************************/
static void	queue_stats_export(zbx_hashset_t *queue_stats, const char *id_name, struct zbx_json *json)
{
	zbx_hashset_iter_t	iter;
	zbx_queue_stats_t	*stats;

	zbx_json_addarray(json, ZBX_PROTO_TAG_DATA);

	zbx_hashset_iter_reset(queue_stats, &iter);

	while (NULL != (stats = (zbx_queue_stats_t *)zbx_hashset_iter_next(&iter)))
	{
		zbx_json_addobject(json, NULL);
		zbx_json_adduint64(json, id_name, stats->id);
		zbx_json_adduint64(json, "delay5", stats->delay5);
		zbx_json_adduint64(json, "delay10", stats->delay10);
		zbx_json_adduint64(json, "delay30", stats->delay30);
		zbx_json_adduint64(json, "delay60", stats->delay60);
		zbx_json_adduint64(json, "delay300", stats->delay300);
		zbx_json_adduint64(json, "delay600", stats->delay600);
		zbx_json_close(json);
	}

	zbx_json_close(json);
}

/* queue item comparison function used to sort queue by nextcheck */
static int	queue_compare_by_nextcheck_asc(zbx_queue_item_t **d1, zbx_queue_item_t **d2)
{
	zbx_queue_item_t	*i1 = *d1, *i2 = *d2;

	return i1->nextcheck - i2->nextcheck;
}

/******************************************************************************
 *                                                                            *
 * Function: recv_getqueue                                                    *
 *                                                                            *
 * Purpose: process queue request                                             *
 *                                                                            *
 * Parameters:  sock  - [IN] the request socket                               *
 *              jp    - [IN] the request data                                 *
 *                                                                            *
 * Return value:  SUCCEED - processed successfully                            *
 *                FAIL - an error occurred                                    *
 *                                                                            *
 ******************************************************************************/
static int	recv_getqueue(zbx_socket_t *sock, struct zbx_json_parse *jp)
{
	int			ret = FAIL, request_type = ZBX_GET_QUEUE_UNKNOWN, now, i, limit;
	char			type[MAX_STRING_LEN], sessionid[MAX_STRING_LEN], limit_str[MAX_STRING_LEN];
	zbx_user_t		user;
	zbx_vector_ptr_t	queue;
	struct zbx_json		json;
	zbx_hashset_t		queue_stats;
	zbx_queue_stats_t	*stats;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (FAIL == zbx_json_value_by_name(jp, ZBX_PROTO_TAG_SID, sessionid, sizeof(sessionid), NULL) ||
			SUCCEED != DBget_user_by_active_session(sessionid, &user) || USER_TYPE_SUPER_ADMIN > user.type)
	{
		zbx_send_response(sock, ret, "Permission denied.", CONFIG_TIMEOUT);
		goto out;
	}

	if (FAIL != zbx_json_value_by_name(jp, ZBX_PROTO_TAG_TYPE, type, sizeof(type), NULL))
	{
		if (0 == strcmp(type, ZBX_PROTO_VALUE_GET_QUEUE_OVERVIEW))
		{
			request_type = ZBX_GET_QUEUE_OVERVIEW;
		}
		else if (0 == strcmp(type, ZBX_PROTO_VALUE_GET_QUEUE_PROXY))
		{
			request_type = ZBX_GET_QUEUE_PROXY;
		}
		else if (0 == strcmp(type, ZBX_PROTO_VALUE_GET_QUEUE_DETAILS))
		{
			request_type = ZBX_GET_QUEUE_DETAILS;

			if (FAIL == zbx_json_value_by_name(jp, ZBX_PROTO_TAG_LIMIT, limit_str, sizeof(limit_str), NULL) ||
					FAIL == is_uint31(limit_str, &limit))
			{
				zbx_send_response(sock, ret, "Unsupported limit value.", CONFIG_TIMEOUT);
				goto out;
			}
		}
	}

	if (ZBX_GET_QUEUE_UNKNOWN == request_type)
	{
		zbx_send_response(sock, ret, "Unsupported request type.", CONFIG_TIMEOUT);
		goto out;
	}

	now = time(NULL);
	zbx_vector_ptr_create(&queue);
	DCget_item_queue(&queue, ZBX_QUEUE_FROM_DEFAULT, ZBX_QUEUE_TO_INFINITY);

	zbx_json_init(&json, ZBX_JSON_STAT_BUF_LEN);

	switch (request_type)
	{
		case ZBX_GET_QUEUE_OVERVIEW:
			zbx_hashset_create(&queue_stats, 32, ZBX_DEFAULT_UINT64_HASH_FUNC,
					ZBX_DEFAULT_UINT64_COMPARE_FUNC);

			/* gather queue stats by item type */
			for (i = 0; i < queue.values_num; i++)
			{
				zbx_queue_item_t	*item = (zbx_queue_item_t *)queue.values[i];
				zbx_uint64_t		id = item->type;

				if (NULL == (stats = (zbx_queue_stats_t *)zbx_hashset_search(&queue_stats, &id)))
				{
					zbx_queue_stats_t	data = {.id = id};

					stats = (zbx_queue_stats_t *)zbx_hashset_insert(&queue_stats, &data, sizeof(data));
				}
				queue_stats_update(stats, now - item->nextcheck);
			}

			zbx_json_addstring(&json, ZBX_PROTO_TAG_RESPONSE, ZBX_PROTO_VALUE_SUCCESS,
					ZBX_JSON_TYPE_STRING);
			queue_stats_export(&queue_stats, "itemtype", &json);
			zbx_hashset_destroy(&queue_stats);

			break;
		case ZBX_GET_QUEUE_PROXY:
			zbx_hashset_create(&queue_stats, 32, ZBX_DEFAULT_UINT64_HASH_FUNC,
					ZBX_DEFAULT_UINT64_COMPARE_FUNC);

			/* gather queue stats by proxy hostid */
			for (i = 0; i < queue.values_num; i++)
			{
				zbx_queue_item_t	*item = (zbx_queue_item_t *)queue.values[i];
				zbx_uint64_t		id = item->proxy_hostid;

				if (NULL == (stats = (zbx_queue_stats_t *)zbx_hashset_search(&queue_stats, &id)))
				{
					zbx_queue_stats_t	data = {.id = id};

					stats = (zbx_queue_stats_t *)zbx_hashset_insert(&queue_stats, &data, sizeof(data));
				}
				queue_stats_update(stats, now - item->nextcheck);
			}

			zbx_json_addstring(&json, ZBX_PROTO_TAG_RESPONSE, ZBX_PROTO_VALUE_SUCCESS,
					ZBX_JSON_TYPE_STRING);
			queue_stats_export(&queue_stats, "proxyid", &json);
			zbx_hashset_destroy(&queue_stats);

			break;
		case ZBX_GET_QUEUE_DETAILS:
			zbx_vector_ptr_sort(&queue, (zbx_compare_func_t)queue_compare_by_nextcheck_asc);
			zbx_json_addstring(&json, ZBX_PROTO_TAG_RESPONSE, ZBX_PROTO_VALUE_SUCCESS,
					ZBX_JSON_TYPE_STRING);
			zbx_json_addarray(&json, ZBX_PROTO_TAG_DATA);

			for (i = 0; i < queue.values_num && i < limit; i++)
			{
				zbx_queue_item_t	*item = (zbx_queue_item_t *)queue.values[i];

				zbx_json_addobject(&json, NULL);
				zbx_json_adduint64(&json, "itemid", item->itemid);
				zbx_json_adduint64(&json, "nextcheck", item->nextcheck);
				zbx_json_close(&json);
			}

			zbx_json_close(&json);
			zbx_json_adduint64(&json, "total", queue.values_num);

			break;
	}

	zabbix_log(LOG_LEVEL_DEBUG, "%s() json.buffer:'%s'", __func__, json.buffer);

	(void)zbx_tcp_send(sock, json.buffer);

	DCfree_item_queue(&queue);
	zbx_vector_ptr_destroy(&queue);

	zbx_json_free(&json);

	ret = SUCCEED;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: recv_alert_send                                                  *
 *                                                                            *
 * Purpose: process alert send request that is used to test media types       *
 *                                                                            *
 * Parameters:  sock  - [IN] the request socket                               *
 *              jp    - [IN] the request data                                 *
 *                                                                            *
 * Return value:  SUCCEED - processed successfully                            *
 *                FAIL - an error occurred                                    *
 *                                                                            *
 ******************************************************************************/
static void	recv_alert_send(zbx_socket_t *sock, const struct zbx_json_parse *jp)
{
	DB_RESULT		result;
	DB_ROW			row;
	int			ret = FAIL, errcode;
	char			tmp[ZBX_MAX_UINT64_LEN + 1], sessionid[MAX_STRING_LEN], *sendto = NULL, *subject = NULL,
				*message = NULL, *error = NULL, *params = NULL, *value = NULL;
	zbx_uint64_t		mediatypeid;
	size_t			string_alloc;
	struct zbx_json		json;
	struct zbx_json_parse	jp_data, jp_params;
	unsigned char		*data = NULL,smtp_security, smtp_verify_peer, smtp_verify_host,
				smtp_authentication, content_type, *response = NULL;
	zbx_uint32_t		size;
	zbx_user_t		user;
	unsigned short		smtp_port;


	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (SUCCEED != zbx_json_value_by_name(jp, ZBX_PROTO_TAG_SID, sessionid, sizeof(sessionid), NULL) ||
			SUCCEED != DBget_user_by_active_session(sessionid, &user) || USER_TYPE_SUPER_ADMIN > user.type)
	{
		error = zbx_strdup(NULL, "Permission denied.");
		goto fail;
	}

	if (SUCCEED != zbx_json_brackets_by_name(jp, ZBX_PROTO_TAG_DATA, &jp_data))
	{
		error = zbx_dsprintf(NULL, "Cannot parse request tag: %s.", ZBX_PROTO_TAG_DATA);
		goto fail;
	}

	if (SUCCEED != zbx_json_value_by_name(&jp_data, ZBX_PROTO_TAG_MEDIATYPEID, tmp, sizeof(tmp), NULL) ||
			SUCCEED != is_uint64(tmp, &mediatypeid))
	{
		error = zbx_dsprintf(NULL, "Cannot parse request tag: %s.", ZBX_PROTO_TAG_MEDIATYPEID);
		goto fail;
	}

	string_alloc = 0;
	if (SUCCEED == zbx_json_value_by_name_dyn(&jp_data, ZBX_PROTO_TAG_SENDTO, &sendto, &string_alloc, NULL))
		string_alloc = 0;
	if (SUCCEED == zbx_json_value_by_name_dyn(&jp_data, ZBX_PROTO_TAG_SUBJECT, &subject, &string_alloc, NULL))
		string_alloc = 0;
	if (SUCCEED == zbx_json_value_by_name_dyn(&jp_data, ZBX_PROTO_TAG_MESSAGE, &message, &string_alloc, NULL))
		string_alloc = 0;

	if (SUCCEED == zbx_json_brackets_by_name(&jp_data, ZBX_PROTO_TAG_PARAMETERS, &jp_params))
	{
		size_t	string_offset = 0;

		zbx_strncpy_alloc(&params, &string_alloc, &string_offset, jp_params.start,
				jp_params.end - jp_params.start + 1);
	}

	result = DBselect("select type,smtp_server,smtp_helo,smtp_email,exec_path,gsm_modem,username,"
				"passwd,smtp_port,smtp_security,smtp_verify_peer,smtp_verify_host,smtp_authentication,"
				"exec_params,maxsessions,maxattempts,attempt_interval,content_type,script,timeout"
			" from media_type"
			" where mediatypeid=" ZBX_FS_UI64, mediatypeid);

	if (NULL == (row = DBfetch(result)))
	{
		DBfree_result(result);
		error = zbx_dsprintf(NULL, "Cannot find the specified media type.");
		goto fail;
	}

	if (FAIL == is_ushort(row[8], &smtp_port))
	{
		DBfree_result(result);
		error = zbx_dsprintf(NULL, "Invalid port value.");
		goto fail;
	}

	ZBX_STR2UCHAR(smtp_security, row[9]);
	ZBX_STR2UCHAR(smtp_verify_peer, row[10]);
	ZBX_STR2UCHAR(smtp_verify_host, row[11]);
	ZBX_STR2UCHAR(smtp_authentication, row[12]);
	ZBX_STR2UCHAR(content_type, row[17]);

	size = zbx_alerter_serialize_alert_send(&data, mediatypeid, atoi(row[0]), row[1], row[2], row[3], row[4],
			row[5], row[6], row[7], smtp_port, smtp_security, smtp_verify_peer, smtp_verify_host,
			smtp_authentication, row[13], atoi(row[14]), atoi(row[15]), row[16], content_type, row[18],
			row[19], sendto, subject, message, params);

	DBfree_result(result);

	if (SUCCEED != zbx_ipc_async_exchange(ZBX_IPC_SERVICE_ALERTER, ZBX_IPC_ALERTER_ALERT, SEC_PER_MIN, data, size,
			&response, &error))
	{
		goto fail;
	}

	zbx_alerter_deserialize_result(response, &value, &errcode, &error);
	zbx_free(response);

	if (SUCCEED != errcode)
		goto fail;

	zbx_json_init(&json, ZBX_JSON_STAT_BUF_LEN);
	zbx_json_addstring(&json, ZBX_PROTO_TAG_RESPONSE, ZBX_PROTO_VALUE_SUCCESS, ZBX_JSON_TYPE_STRING);
	if (NULL != value)
		zbx_json_addstring(&json, ZBX_PROTO_TAG_DATA, value, ZBX_JSON_TYPE_STRING);

	(void)zbx_tcp_send(sock, json.buffer);

	zbx_json_free(&json);

	ret = SUCCEED;
fail:
	if (SUCCEED != ret)
		zbx_send_response(sock, FAIL, error, CONFIG_TIMEOUT);

	zbx_free(params);
	zbx_free(message);
	zbx_free(subject);
	zbx_free(sendto);
	zbx_free(data);
	zbx_free(value);
	zbx_free(error);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));
}

static int	DBget_template_count(zbx_uint64_t *count)
{
	DB_RESULT	result;
	DB_ROW		row;
	int		ret = FAIL;

	if (NULL == (result = DBselect("select count(*) from hosts where status=%d", HOST_STATUS_TEMPLATE)))
		goto out;

	if (NULL == (row = DBfetch(result)) || SUCCEED != is_uint64(row[0], count))
		goto out;

	ret = SUCCEED;
out:
	DBfree_result(result);

	return ret;
}

static int	DBget_user_count(zbx_uint64_t *count_online, zbx_uint64_t *count_offline)
{
	DB_RESULT	result;
	DB_ROW		row;
	zbx_uint64_t	users_offline, users_online = 0;
	int		now, ret = FAIL;

	if (NULL == (result = DBselect("select count(*) from users")))
		goto out;

	if (NULL == (row = DBfetch(result)) || SUCCEED != is_uint64(row[0], &users_offline))
		goto out;

	DBfree_result(result);
	now = time(NULL);

	if (NULL == (result = DBselect("select max(lastaccess) from sessions where status=%d group by userid,status",
			ZBX_SESSION_ACTIVE)))
	{
		goto out;
	}

	while (NULL != (row = DBfetch(result)))
	{
		if (atoi(row[0]) + ZBX_USER_ONLINE_TIME < now)
			continue;

		users_online++;

		if (0 == users_offline)	/* new user can be created and log in between two selects */
			continue;

		users_offline--;
	}

	*count_online = users_online;
	*count_offline = users_offline;
	ret = SUCCEED;
out:
	DBfree_result(result);

	return ret;
}

/* auxiliary variables for status_stats_export() */

static zbx_entry_info_t	templates, hosts_monitored, hosts_not_monitored, items_active_normal, items_active_notsupported,
			items_disabled, triggers_enabled_ok, triggers_enabled_problem, triggers_disabled, users_online,
			users_offline, required_performance;
static int		templates_res, users_res;

static void	zbx_status_counters_init(void)
{
	zbx_vector_ptr_create(&hosts_monitored.counters);
	zbx_vector_ptr_create(&hosts_not_monitored.counters);
	zbx_vector_ptr_create(&items_active_normal.counters);
	zbx_vector_ptr_create(&items_active_notsupported.counters);
	zbx_vector_ptr_create(&items_disabled.counters);
	zbx_vector_ptr_create(&required_performance.counters);
}

static void	zbx_status_counters_free(void)
{
	zbx_vector_ptr_clear_ext(&hosts_monitored.counters, zbx_default_mem_free_func);
	zbx_vector_ptr_clear_ext(&hosts_not_monitored.counters, zbx_default_mem_free_func);
	zbx_vector_ptr_clear_ext(&items_active_normal.counters, zbx_default_mem_free_func);
	zbx_vector_ptr_clear_ext(&items_active_notsupported.counters, zbx_default_mem_free_func);
	zbx_vector_ptr_clear_ext(&items_disabled.counters, zbx_default_mem_free_func);
	zbx_vector_ptr_clear_ext(&required_performance.counters, zbx_default_mem_free_func);

	zbx_vector_ptr_destroy(&hosts_monitored.counters);
	zbx_vector_ptr_destroy(&hosts_not_monitored.counters);
	zbx_vector_ptr_destroy(&items_active_normal.counters);
	zbx_vector_ptr_destroy(&items_active_notsupported.counters);
	zbx_vector_ptr_destroy(&items_disabled.counters);
	zbx_vector_ptr_destroy(&required_performance.counters);
}

const zbx_status_section_t	status_sections[] = {
/*	{SECTION NAME,			NUMBER OF SECTION ENTRIES	SECTION ACCESS LEVEL	SECTION READYNESS, */
/*		{                                                                                                  */
/*			{ENTRY INFORMATION,		COUNTER TYPE,                                              */
/*				{                                                                                  */
/*					{ATTR. NAME,	ATTRIBUTE VALUE},                                          */
/*					... (up to ZBX_MAX_ENTRY_ATTRIBUTES)                                       */
/*				}                                                                                  */
/*			},                                                                                         */
/*			... (up to ZBX_MAX_SECTION_ENTRIES)                                                        */
/*		}                                                                                                  */
/*	},                                                                                                         */
/*	...                                                                                                        */
	{"template stats",		ZBX_SECTION_ENTRY_THE_ONLY,	USER_TYPE_ZABBIX_USER,	&templates_res,
		{
			{&templates,			ZBX_COUNTER_TYPE_UI64,
				{
					{NULL}
				}
			},
			{NULL}
		}
	},
	{"host stats",			ZBX_SECTION_ENTRY_PER_PROXY,	USER_TYPE_ZABBIX_USER,	NULL,
		{
			{&hosts_monitored,		ZBX_COUNTER_TYPE_UI64,
				{
					{"status",	HOST_STATUS_MONITORED},
					{NULL}
				}
			},
			{&hosts_not_monitored,		ZBX_COUNTER_TYPE_UI64,
				{
					{"status",	HOST_STATUS_NOT_MONITORED},
					{NULL}
				}
			},
			{NULL}
		}
	},
	{"item stats",			ZBX_SECTION_ENTRY_PER_PROXY,	USER_TYPE_ZABBIX_USER,	NULL,
		{
			{&items_active_normal,		ZBX_COUNTER_TYPE_UI64,
				{
					{"status",	ITEM_STATUS_ACTIVE},
					{"state",	ITEM_STATE_NORMAL},
					{NULL}
				}
			},
			{&items_active_notsupported,	ZBX_COUNTER_TYPE_UI64,
				{
					{"status",	ITEM_STATUS_ACTIVE},
					{"state",	ITEM_STATE_NOTSUPPORTED},
					{NULL}
				}
			},
			{&items_disabled,		ZBX_COUNTER_TYPE_UI64,
				{
					{"status",	ITEM_STATUS_DISABLED},
					{NULL}
				}
			},
			{NULL}
		}
	},
	{"trigger stats",		ZBX_SECTION_ENTRY_THE_ONLY,	USER_TYPE_ZABBIX_USER,	NULL,
		{
			{&triggers_enabled_ok,		ZBX_COUNTER_TYPE_UI64,
				{
					{"status",	TRIGGER_STATUS_ENABLED},
					{"value",	TRIGGER_VALUE_OK},
					{NULL}
				}
			},
			{&triggers_enabled_problem,	ZBX_COUNTER_TYPE_UI64,
				{
					{"status",	TRIGGER_STATUS_ENABLED},
					{"value",	TRIGGER_VALUE_PROBLEM},
					{NULL}
				}
			},
			{&triggers_disabled,		ZBX_COUNTER_TYPE_UI64,
				{
					{"status",	TRIGGER_STATUS_DISABLED},
					{NULL}
				}
			},
			{NULL}
		}
	},
	{"user stats",			ZBX_SECTION_ENTRY_THE_ONLY,	USER_TYPE_ZABBIX_USER,	&users_res,
		{
			{&users_online,			ZBX_COUNTER_TYPE_UI64,
				{
					{"status",	ZBX_SESSION_ACTIVE},
					{NULL}
				}
			},
			{&users_offline,		ZBX_COUNTER_TYPE_UI64,
				{
					{"status",	ZBX_SESSION_PASSIVE},
					{NULL}
				}
			},
			{NULL}
		}
	},
	{"required performance",	ZBX_SECTION_ENTRY_PER_PROXY,	USER_TYPE_SUPER_ADMIN,	NULL,
		{
			{&required_performance,		ZBX_COUNTER_TYPE_DBL,
				{
					{NULL}
				}
			},
			{NULL}
		}
	},
	{NULL}
};

static void	status_entry_export(struct zbx_json *json, const zbx_section_entry_t *entry,
		zbx_counter_value_t counter_value, const zbx_uint64_t *proxyid)
{
	const zbx_entry_attribute_t	*attribute;
	char				*tmp = NULL;

	zbx_json_addobject(json, NULL);

	if (NULL != entry->attributes[0].name || NULL != proxyid)
	{
		zbx_json_addobject(json, "attributes");

		if (NULL != proxyid)
			zbx_json_adduint64(json, "proxyid", *proxyid);

		for (attribute = entry->attributes; NULL != attribute->name; attribute++)
			zbx_json_adduint64(json, attribute->name, attribute->value);

		zbx_json_close(json);
	}

	switch (entry->counter_type)
	{
		case ZBX_COUNTER_TYPE_UI64:
			zbx_json_adduint64(json, "count", counter_value.ui64);
			break;
		case ZBX_COUNTER_TYPE_DBL:
			tmp = zbx_dsprintf(tmp, ZBX_FS_DBL, counter_value.dbl);
			zbx_json_addstring(json, "count", tmp, ZBX_JSON_TYPE_STRING);
			break;
		default:
			THIS_SHOULD_NEVER_HAPPEN;
	}

	zbx_json_close(json);

	zbx_free(tmp);
}

static void	status_stats_export(struct zbx_json *json, zbx_user_type_t access_level)
{
	const zbx_status_section_t	*section;
	const zbx_section_entry_t	*entry;
	int				i;

	zbx_status_counters_init();

	/* get status information */

	templates_res = DBget_template_count(&templates.counter.ui64);
	users_res = DBget_user_count(&users_online.counter.ui64, &users_offline.counter.ui64);
	DCget_status(&hosts_monitored.counters, &hosts_not_monitored.counters, &items_active_normal.counters,
			&items_active_notsupported.counters, &items_disabled.counters,
			&triggers_enabled_ok.counter.ui64, &triggers_enabled_problem.counter.ui64,
			&triggers_disabled.counter.ui64, &required_performance.counters);

	/* add status information to JSON */
	for (section = status_sections; NULL != section->name; section++)
	{
		if (access_level < section->access_level)	/* skip sections user has no rights to access */
			continue;

		if (NULL != section->res && SUCCEED != *section->res)	/* skip section we have no information for */
			continue;

		zbx_json_addarray(json, section->name);

		for (entry = section->entries; NULL != entry->info; entry++)
		{
			switch (section->entry_type)
			{
				case ZBX_SECTION_ENTRY_THE_ONLY:
					status_entry_export(json, entry, entry->info->counter, NULL);
					break;
				case ZBX_SECTION_ENTRY_PER_PROXY:
					for (i = 0; i < entry->info->counters.values_num; i++)
					{
						const zbx_proxy_counter_t	*proxy_counter;

						proxy_counter = (zbx_proxy_counter_t *)entry->info->counters.values[i];
						status_entry_export(json, entry, proxy_counter->counter_value,
								&proxy_counter->proxyid);
					}
					break;
				default:
					THIS_SHOULD_NEVER_HAPPEN;
			}
		}

		zbx_json_close(json);
	}

	zbx_status_counters_free();
}

/******************************************************************************
 *                                                                            *
 * Function: recv_getstatus                                                   *
 *                                                                            *
 * Purpose: process status request                                            *
 *                                                                            *
 * Parameters:  sock  - [IN] the request socket                               *
 *              jp    - [IN] the request data                                 *
 *                                                                            *
 * Return value:  SUCCEED - processed successfully                            *
 *                FAIL - an error occurred                                    *
 *                                                                            *
 ******************************************************************************/
static int	recv_getstatus(zbx_socket_t *sock, struct zbx_json_parse *jp)
{
#define ZBX_GET_STATUS_UNKNOWN	-1
#define ZBX_GET_STATUS_PING	0
#define ZBX_GET_STATUS_FULL	1

	zbx_user_t	user;
	int		ret = FAIL, request_type = ZBX_GET_STATUS_UNKNOWN;
	char		type[MAX_STRING_LEN], sessionid[MAX_STRING_LEN];
	struct zbx_json	json;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (SUCCEED != zbx_json_value_by_name(jp, ZBX_PROTO_TAG_SID, sessionid, sizeof(sessionid), NULL) ||
			SUCCEED != DBget_user_by_active_session(sessionid, &user))
	{
		zbx_send_response(sock, ret, "Permission denied.", CONFIG_TIMEOUT);
		goto out;
	}

	if (SUCCEED == zbx_json_value_by_name(jp, ZBX_PROTO_TAG_TYPE, type, sizeof(type), NULL))
	{
		if (0 == strcmp(type, ZBX_PROTO_VALUE_GET_STATUS_PING))
		{
			request_type = ZBX_GET_STATUS_PING;
		}
		else if (0 == strcmp(type, ZBX_PROTO_VALUE_GET_STATUS_FULL))
		{
			request_type = ZBX_GET_STATUS_FULL;
		}
	}

	if (ZBX_GET_STATUS_UNKNOWN == request_type)
	{
		zbx_send_response(sock, ret, "Unsupported request type.", CONFIG_TIMEOUT);
		goto out;
	}

	zbx_json_init(&json, ZBX_JSON_STAT_BUF_LEN);

	switch (request_type)
	{
		case ZBX_GET_STATUS_PING:
			zbx_json_addstring(&json, ZBX_PROTO_TAG_RESPONSE, ZBX_PROTO_VALUE_SUCCESS, ZBX_JSON_TYPE_STRING);
			zbx_json_addobject(&json, ZBX_PROTO_TAG_DATA);
			zbx_json_close(&json);
			break;
		case ZBX_GET_STATUS_FULL:
			zbx_json_addstring(&json, ZBX_PROTO_TAG_RESPONSE, ZBX_PROTO_VALUE_SUCCESS, ZBX_JSON_TYPE_STRING);
			zbx_json_addobject(&json, ZBX_PROTO_TAG_DATA);
			status_stats_export(&json, user.type);
			zbx_json_close(&json);
			break;
		default:
			THIS_SHOULD_NEVER_HAPPEN;
	}

	zabbix_log(LOG_LEVEL_DEBUG, "%s() json.buffer:'%s'", __func__, json.buffer);

	(void)zbx_tcp_send(sock, json.buffer);

	zbx_json_free(&json);

	ret = SUCCEED;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;

#undef ZBX_GET_STATUS_UNKNOWN
#undef ZBX_GET_STATUS_PING
#undef ZBX_GET_STATUS_FULL
}

/******************************************************************************
 *                                                                            *
 * Function: send_internal_stats_json                                         *
 *                                                                            *
 * Purpose: process Zabbix stats request                                      *
 *                                                                            *
 * Parameters: sock  - [IN] the request socket                                *
 *             jp    - [IN] the request data                                  *
 *                                                                            *
 * Return value:  SUCCEED - processed successfully                            *
 *                FAIL - an error occurred                                    *
 *                                                                            *
 ******************************************************************************/
static int	send_internal_stats_json(zbx_socket_t *sock, const struct zbx_json_parse *jp)
{
	struct zbx_json	json;
	char		type[MAX_STRING_LEN], error[MAX_STRING_LEN];
	int		ret = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (NULL == CONFIG_STATS_ALLOWED_IP ||
			SUCCEED != zbx_tcp_check_allowed_peers(sock, CONFIG_STATS_ALLOWED_IP))
	{
		zabbix_log(LOG_LEVEL_WARNING, "failed to accept an incoming stats request: %s",
				NULL == CONFIG_STATS_ALLOWED_IP ? "StatsAllowedIP not set" : zbx_socket_strerror());
		strscpy(error, "Permission denied.");
		goto out;
	}

	zbx_json_init(&json, ZBX_JSON_STAT_BUF_LEN);

	if (SUCCEED == zbx_json_value_by_name(jp, ZBX_PROTO_TAG_TYPE, type, sizeof(type), NULL) &&
			0 == strcmp(type, ZBX_PROTO_VALUE_ZABBIX_STATS_QUEUE))
	{
		char			from_str[ZBX_MAX_UINT64_LEN + 1], to_str[ZBX_MAX_UINT64_LEN + 1];
		int			from = ZBX_QUEUE_FROM_DEFAULT, to = ZBX_QUEUE_TO_INFINITY;
		struct zbx_json_parse	jp_data;

		if (SUCCEED != zbx_json_brackets_by_name(jp, ZBX_PROTO_TAG_PARAMS, &jp_data))
		{
			zbx_snprintf(error, sizeof(error), "cannot find tag: %s", ZBX_PROTO_TAG_PARAMS);
			goto param_error;
		}

		if (SUCCEED == zbx_json_value_by_name(&jp_data, ZBX_PROTO_TAG_FROM, from_str, sizeof(from_str), NULL)
				&& FAIL == is_time_suffix(from_str, &from, ZBX_LENGTH_UNLIMITED))
		{
			strscpy(error, "invalid 'from' parameter");
			goto param_error;
		}

		if (SUCCEED == zbx_json_value_by_name(&jp_data, ZBX_PROTO_TAG_TO, to_str, sizeof(to_str), NULL) &&
				FAIL == is_time_suffix(to_str, &to, ZBX_LENGTH_UNLIMITED))
		{
			strscpy(error, "invalid 'to' parameter");
			goto param_error;
		}

		if (ZBX_QUEUE_TO_INFINITY != to && from > to)
		{
			strscpy(error, "parameters represent an invalid interval");
			goto param_error;
		}

		zbx_json_addstring(&json, ZBX_PROTO_TAG_RESPONSE, ZBX_PROTO_VALUE_SUCCESS, ZBX_JSON_TYPE_STRING);
		zbx_json_adduint64(&json, ZBX_PROTO_VALUE_ZABBIX_STATS_QUEUE, DCget_item_queue(NULL, from, to));
	}
	else
	{
		zbx_json_addstring(&json, ZBX_PROTO_TAG_RESPONSE, ZBX_PROTO_VALUE_SUCCESS, ZBX_JSON_TYPE_STRING);
		zbx_json_addobject(&json, ZBX_PROTO_TAG_DATA);

		zbx_get_zabbix_stats(&json);

		zbx_json_close(&json);
	}

	(void)zbx_tcp_send(sock, json.buffer);
	ret = SUCCEED;
param_error:
	zbx_json_free(&json);
out:
	if (SUCCEED != ret)
		zbx_send_response(sock, ret, error, CONFIG_TIMEOUT);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

static void	active_passive_misconfig(zbx_socket_t *sock)
{
	char	*msg = NULL;

	msg = zbx_dsprintf(msg, "misconfiguration error: the proxy is running in the active mode but server at \"%s\""
			" sends requests to it as to proxy in passive mode", sock->peer);

	zabbix_log(LOG_LEVEL_WARNING, "%s", msg);
	zbx_send_proxy_response(sock, FAIL, msg, CONFIG_TIMEOUT);
	zbx_free(msg);
}

static int	process_trap(zbx_socket_t *sock, char *s, zbx_timespec_t *ts)
{
	int	ret = SUCCEED;

	zbx_rtrim(s, " \r\n");

	zabbix_log(LOG_LEVEL_DEBUG, "trapper got '%s'", s);

	if ('{' == *s)	/* JSON protocol */
	{
		struct zbx_json_parse	jp;
		char			value[MAX_STRING_LEN];

		if (SUCCEED != zbx_json_open(s, &jp))
		{
			zbx_send_response(sock, FAIL, zbx_json_strerror(), CONFIG_TIMEOUT);
			zabbix_log(LOG_LEVEL_WARNING, "received invalid JSON object from %s: %s",
					sock->peer, zbx_json_strerror());
			return FAIL;
		}

		if (SUCCEED == zbx_json_value_by_name(&jp, ZBX_PROTO_TAG_REQUEST, value, sizeof(value), NULL))
		{
			if (0 == strcmp(value, ZBX_PROTO_VALUE_PROXY_CONFIG))
			{
				if (0 != (program_type & ZBX_PROGRAM_TYPE_SERVER))
				{
					send_proxyconfig(sock, &jp);
				}
				else if (0 != (program_type & ZBX_PROGRAM_TYPE_PROXY_PASSIVE))
				{
					zabbix_log(LOG_LEVEL_WARNING, "received configuration data from server"
							" at \"%s\", datalen " ZBX_FS_SIZE_T,
							sock->peer, (zbx_fs_size_t)(jp.end - jp.start + 1));
					recv_proxyconfig(sock, &jp);
				}
				else if (0 != (program_type & ZBX_PROGRAM_TYPE_PROXY_ACTIVE))
				{
					/* This is a misconfiguration: the proxy is configured in active mode */
					/* but server sends requests to it as to a proxy in passive mode. To  */
					/* prevent logging of this problem for every request we report it     */
					/* only when the server sends configuration to the proxy and ignore   */
					/* it for other requests.                                             */
					active_passive_misconfig(sock);
				}
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_AGENT_DATA))
			{
				recv_agenthistory(sock, &jp, ts);
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_SENDER_DATA))
			{
				recv_senderhistory(sock, &jp, ts);
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_PROXY_TASKS))
			{
				if (0 != (program_type & ZBX_PROGRAM_TYPE_PROXY_PASSIVE))
					zbx_send_task_data(sock, ts);
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_PROXY_DATA))
			{
				if (0 != (program_type & ZBX_PROGRAM_TYPE_SERVER))
					zbx_recv_proxy_data(sock, &jp, ts);
				else if (0 != (program_type & ZBX_PROGRAM_TYPE_PROXY_PASSIVE))
					zbx_send_proxy_data(sock, ts);
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_PROXY_HEARTBEAT))
			{
				if (0 != (program_type & ZBX_PROGRAM_TYPE_SERVER))
					recv_proxy_heartbeat(sock, &jp);
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_GET_ACTIVE_CHECKS))
			{
				ret = send_list_of_active_checks_json(sock, &jp);
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_COMMAND))
			{
				if (0 != (program_type & ZBX_PROGRAM_TYPE_SERVER))
					ret = node_process_command(sock, s, &jp);
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_GET_QUEUE))
			{
				if (0 != (program_type & ZBX_PROGRAM_TYPE_SERVER))
					ret = recv_getqueue(sock, &jp);
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_GET_STATUS))
			{
				if (0 != (program_type & ZBX_PROGRAM_TYPE_SERVER))
					ret = recv_getstatus(sock, &jp);
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_ZABBIX_STATS))
			{
				ret = send_internal_stats_json(sock, &jp);
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_ZABBIX_ALERT_SEND))
			{
				if (0 != (program_type & ZBX_PROGRAM_TYPE_SERVER))
					recv_alert_send(sock, &jp);
			}
			else if (0 == strcmp(value, ZBX_PROTO_VALUE_PREPROCESSING_TEST))
			{
				if (0 != (program_type & ZBX_PROGRAM_TYPE_SERVER))
					ret = zbx_trapper_preproc_test(sock, &jp);
			}
			else
				zabbix_log(LOG_LEVEL_WARNING, "unknown request received from \"%s\": [%s]", sock->peer, value);
		}
	}
	else if (0 == strncmp(s, "ZBX_GET_ACTIVE_CHECKS", 21))	/* request for list of active checks */
	{
		ret = send_list_of_active_checks(sock, s);
	}
	else
	{
		char			value_dec[MAX_BUFFER_LEN], lastlogsize[ZBX_MAX_UINT64_LEN], timestamp[11],
					source[HISTORY_LOG_SOURCE_LEN_MAX], severity[11],
					host[HOST_HOST_LEN * ZBX_MAX_BYTES_IN_UTF8_CHAR + 1],
					key[ITEM_KEY_LEN * ZBX_MAX_BYTES_IN_UTF8_CHAR + 1];
		zbx_agent_value_t	av;
		zbx_host_key_t		hk = {host, key};
		DC_ITEM			item;
		int			errcode;

		memset(&av, 0, sizeof(zbx_agent_value_t));

		if ('<' == *s)	/* XML protocol */
		{
			comms_parse_response(s, host, sizeof(host), key, sizeof(key), value_dec,
					sizeof(value_dec), lastlogsize, sizeof(lastlogsize), timestamp,
					sizeof(timestamp), source, sizeof(source), severity, sizeof(severity));

			av.value = value_dec;
			if (SUCCEED != is_uint64(lastlogsize, &av.lastlogsize))
				av.lastlogsize = 0;
			av.timestamp = atoi(timestamp);
			av.source = source;
			av.severity = atoi(severity);
		}
		else
		{
			char	*pl, *pr;

			pl = s;
			if (NULL == (pr = strchr(pl, ':')))
				return FAIL;

			*pr = '\0';
			zbx_strlcpy(host, pl, sizeof(host));
			*pr = ':';

			pl = pr + 1;
			if (NULL == (pr = strchr(pl, ':')))
				return FAIL;

			*pr = '\0';
			zbx_strlcpy(key, pl, sizeof(key));
			*pr = ':';

			av.value = pr + 1;
			av.severity = 0;
		}

		zbx_timespec(&av.ts);

		if (0 == strcmp(av.value, ZBX_NOTSUPPORTED))
			av.state = ITEM_STATE_NOTSUPPORTED;

		DCconfig_get_items_by_keys(&item, &hk, &errcode, 1);
		process_history_data(&item, &av, &errcode, 1);
		DCconfig_clean_items(&item, &errcode, 1);

		zbx_alarm_on(CONFIG_TIMEOUT);
		if (SUCCEED != zbx_tcp_send_raw(sock, "OK"))
			zabbix_log(LOG_LEVEL_WARNING, "Error sending result back");
		zbx_alarm_off();
	}

	return ret;
}

static void	process_trapper_child(zbx_socket_t *sock, zbx_timespec_t *ts)
{
	if (SUCCEED != zbx_tcp_recv_to(sock, CONFIG_TRAPPER_TIMEOUT))
		return;

	process_trap(sock, sock->buffer, ts);
}

ZBX_THREAD_ENTRY(trapper_thread, args)
{
	double		sec = 0.0;
	zbx_socket_t	s;
	int		ret;

	process_type = ((zbx_thread_args_t *)args)->process_type;
	server_num = ((zbx_thread_args_t *)args)->server_num;
	process_num = ((zbx_thread_args_t *)args)->process_num;

	zabbix_log(LOG_LEVEL_INFORMATION, "%s #%d started [%s #%d]", get_program_type_string(program_type),
			server_num, get_process_type_string(process_type), process_num);

	update_selfmon_counter(ZBX_PROCESS_STATE_BUSY);

	memcpy(&s, (zbx_socket_t *)((zbx_thread_args_t *)args)->args, sizeof(zbx_socket_t));

#if defined(HAVE_POLARSSL) || defined(HAVE_GNUTLS) || defined(HAVE_OPENSSL)
	zbx_tls_init_child();
	find_psk_in_cache = DCget_psk_by_identity;
#endif
	zbx_setproctitle("%s #%d [connecting to the database]", get_process_type_string(process_type), process_num);

	DBconnect(ZBX_DB_CONNECT_NORMAL);

	/* configuration sync is performed by trappers on passive Zabbix proxy */
	if (1 == process_num && 0 == CONFIG_CONFSYNCER_FORKS)
	{
		zbx_setproctitle("%s [syncing configuration]", get_process_type_string(process_type));
		DCsync_configuration(ZBX_DBSYNC_INIT);
	}

	while (ZBX_IS_RUNNING())
	{
		zbx_setproctitle("%s #%d [processed data in " ZBX_FS_DBL " sec, waiting for connection]",
				get_process_type_string(process_type), process_num, sec);

		update_selfmon_counter(ZBX_PROCESS_STATE_IDLE);

		/* Trapper has to accept all types of connections it can accept with the specified configuration. */
		/* Only after receiving data it is known who has sent them and one can decide to accept or discard */
		/* the data. */
		ret = zbx_tcp_accept(&s, ZBX_TCP_SEC_TLS_CERT | ZBX_TCP_SEC_TLS_PSK | ZBX_TCP_SEC_UNENCRYPTED);
		zbx_update_env(zbx_time());

		if (SUCCEED == ret)
		{
			zbx_timespec_t	ts;

			/* get connection timestamp */
			zbx_timespec(&ts);

			update_selfmon_counter(ZBX_PROCESS_STATE_BUSY);

			zbx_setproctitle("%s #%d [processing data]", get_process_type_string(process_type),
					process_num);

			sec = zbx_time();
			process_trapper_child(&s, &ts);
			sec = zbx_time() - sec;

			zbx_tcp_unaccept(&s);
		}
		else if (EINTR != zbx_socket_last_error())
		{
			zabbix_log(LOG_LEVEL_WARNING, "failed to accept an incoming connection: %s",
					zbx_socket_strerror());
		}
	}

	zbx_setproctitle("%s #%d [terminated]", get_process_type_string(process_type), process_num);

	while (1)
		zbx_sleep(SEC_PER_MIN);
}
