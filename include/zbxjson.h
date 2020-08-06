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

#ifndef ZABBIX_ZJSON_H
#define ZABBIX_ZJSON_H

#define ZBX_PROTO_TAG_CLOCK			"clock"
#define ZBX_PROTO_TAG_NS			"ns"
#define ZBX_PROTO_TAG_DATA			"data"
#define ZBX_PROTO_TAG_REGEXP			"regexp"
#define ZBX_PROTO_TAG_DELAY			"delay"
#define ZBX_PROTO_TAG_REFRESH_UNSUPPORTED	"refresh_unsupported"
#define ZBX_PROTO_TAG_DRULE			"drule"
#define ZBX_PROTO_TAG_DCHECK			"dcheck"
#define ZBX_PROTO_TAG_HOST			"host"
#define ZBX_PROTO_TAG_HOST_METADATA		"host_metadata"
#define ZBX_PROTO_TAG_INFO			"info"
#define ZBX_PROTO_TAG_IP			"ip"
#define ZBX_PROTO_TAG_DNS			"dns"
#define ZBX_PROTO_TAG_CONN			"conn"
#define ZBX_PROTO_TAG_KEY			"key"
#define ZBX_PROTO_TAG_KEY_ORIG			"key_orig"
#define ZBX_PROTO_TAG_KEYS			"keys"
#define ZBX_PROTO_TAG_LASTLOGSIZE		"lastlogsize"
#define ZBX_PROTO_TAG_MTIME			"mtime"
#define ZBX_PROTO_TAG_LOGTIMESTAMP		"timestamp"
#define ZBX_PROTO_TAG_LOGSOURCE			"source"
#define ZBX_PROTO_TAG_LOGSEVERITY		"severity"
#define ZBX_PROTO_TAG_LOGEVENTID		"eventid"
#define ZBX_PROTO_TAG_PORT			"port"
#define ZBX_PROTO_TAG_TLS_ACCEPTED		"tls_accepted"
#define ZBX_PROTO_TAG_PROXY			"proxy"
#define ZBX_PROTO_TAG_REQUEST			"request"
#define ZBX_PROTO_TAG_RESPONSE			"response"
#define ZBX_PROTO_TAG_STATUS			"status"
#define ZBX_PROTO_TAG_STATE			"state"
#define ZBX_PROTO_TAG_TYPE			"type"
#define ZBX_PROTO_TAG_LIMIT			"limit"
#define ZBX_PROTO_TAG_VALUE			"value"
#define ZBX_PROTO_TAG_SCRIPTID			"scriptid"
#define ZBX_PROTO_TAG_HOSTID			"hostid"
#define ZBX_PROTO_TAG_AVAILABLE			"available"
#define ZBX_PROTO_TAG_SNMP_AVAILABLE		"snmp_available"
#define ZBX_PROTO_TAG_IPMI_AVAILABLE		"ipmi_available"
#define ZBX_PROTO_TAG_JMX_AVAILABLE		"jmx_available"
#define ZBX_PROTO_TAG_ERROR			"error"
#define ZBX_PROTO_TAG_SNMP_ERROR		"snmp_error"
#define ZBX_PROTO_TAG_IPMI_ERROR		"ipmi_error"
#define ZBX_PROTO_TAG_JMX_ERROR			"jmx_error"
#define ZBX_PROTO_TAG_USERNAME			"username"
#define ZBX_PROTO_TAG_PASSWORD			"password"
#define ZBX_PROTO_TAG_SID			"sid"
#define ZBX_PROTO_TAG_VERSION			"version"
#define ZBX_PROTO_TAG_HOST_AVAILABILITY		"host availability"
#define ZBX_PROTO_TAG_HISTORY_DATA		"history data"
#define ZBX_PROTO_TAG_DISCOVERY_DATA		"discovery data"
#define ZBX_PROTO_TAG_AUTO_REGISTRATION		"auto registration"
#define ZBX_PROTO_TAG_MORE			"more"
#define ZBX_PROTO_TAG_ITEMID			"itemid"
#define ZBX_PROTO_TAG_TTL			"ttl"
#define ZBX_PROTO_TAG_COMMANDTYPE		"commandtype"
#define ZBX_PROTO_TAG_COMMAND			"command"
#define ZBX_PROTO_TAG_EXECUTE_ON		"execute_on"
#define ZBX_PROTO_TAG_AUTHTYPE			"authtype"
#define ZBX_PROTO_TAG_PUBLICKEY			"publickey"
#define ZBX_PROTO_TAG_PRIVATEKEY		"privatekey"
#define ZBX_PROTO_TAG_PARENT_TASKID		"parent_taskid"
#define ZBX_PROTO_TAG_TASKS			"tasks"
#define ZBX_PROTO_TAG_ALERTID			"alertid"
#define ZBX_PROTO_TAG_JMX_ENDPOINT		"jmx_endpoint"
#define ZBX_PROTO_TAG_EVENTID			"eventid"
#define ZBX_PROTO_TAG_NAME			"name"
#define ZBX_PROTO_TAG_HOSTS			"hosts"
#define ZBX_PROTO_TAG_GROUPS			"groups"
#define ZBX_PROTO_TAG_APPLICATIONS		"applications"
#define ZBX_PROTO_TAG_TAGS			"tags"
#define ZBX_PROTO_TAG_TAG			"tag"
#define ZBX_PROTO_TAG_PROBLEM_EVENTID		"p_eventid"
#define ZBX_PROTO_TAG_ITEMID			"itemid"
#define ZBX_PROTO_TAG_COUNT			"count"
#define ZBX_PROTO_TAG_MIN			"min"
#define ZBX_PROTO_TAG_AVG			"avg"
#define ZBX_PROTO_TAG_MAX			"max"
#define ZBX_PROTO_TAG_SESSION			"session"
#define ZBX_PROTO_TAG_ID			"id"
#define ZBX_PROTO_TAG_PARAMS			"params"
#define ZBX_PROTO_TAG_FROM			"from"
#define ZBX_PROTO_TAG_TO			"to"
#define ZBX_PROTO_TAG_HISTORY			"history"
#define ZBX_PROTO_TAG_TIMESTAMP			"timestamp"
#define ZBX_PROTO_TAG_ERROR_HANDLER		"error_handler"
#define ZBX_PROTO_TAG_ERROR_HANDLER_PARAMS	"error_handler_params"
#define ZBX_PROTO_TAG_VALUE_TYPE		"value_type"
#define ZBX_PROTO_TAG_STEPS			"steps"
#define ZBX_PROTO_TAG_ACTION			"action"
#define ZBX_PROTO_TAG_FAILED			"failed"
#define ZBX_PROTO_TAG_RESULT			"result"
#define ZBX_PROTO_TAG_LINE_RAW			"line_raw"
#define ZBX_PROTO_TAG_LABELS			"labels"
#define ZBX_PROTO_TAG_HELP			"help"
#define ZBX_PROTO_TAG_MEDIATYPEID		"mediatypeid"
#define ZBX_PROTO_TAG_SENDTO			"sendto"
#define ZBX_PROTO_TAG_SUBJECT			"subject"
#define ZBX_PROTO_TAG_MESSAGE			"message"
#define ZBX_PROTO_TAG_PREVIOUS			"previous"
#define ZBX_PROTO_TAG_SINGLE			"single"
#define ZBX_PROTO_TAG_INTERFACE			"interface"
#define ZBX_PROTO_TAG_FLAGS			"flags"
#define ZBX_PROTO_TAG_PARAMETERS		"parameters"

#define ZBX_PROTO_VALUE_FAILED		"failed"
#define ZBX_PROTO_VALUE_SUCCESS		"success"

#define ZBX_PROTO_VALUE_GET_ACTIVE_CHECKS	"active checks"
#define ZBX_PROTO_VALUE_PROXY_CONFIG		"proxy config"
#define ZBX_PROTO_VALUE_PROXY_HEARTBEAT		"proxy heartbeat"
#define ZBX_PROTO_VALUE_SENDER_DATA		"sender data"
#define ZBX_PROTO_VALUE_AGENT_DATA		"agent data"
#define ZBX_PROTO_VALUE_COMMAND			"command"
#define ZBX_PROTO_VALUE_JAVA_GATEWAY_INTERNAL	"java gateway internal"
#define ZBX_PROTO_VALUE_JAVA_GATEWAY_JMX	"java gateway jmx"
#define ZBX_PROTO_VALUE_GET_QUEUE		"queue.get"
#define ZBX_PROTO_VALUE_GET_STATUS		"status.get"
#define ZBX_PROTO_VALUE_PROXY_DATA		"proxy data"
#define ZBX_PROTO_VALUE_PROXY_TASKS		"proxy tasks"

#define ZBX_PROTO_VALUE_GET_QUEUE_OVERVIEW	"overview"
#define ZBX_PROTO_VALUE_GET_QUEUE_PROXY		"overview by proxy"
#define ZBX_PROTO_VALUE_GET_QUEUE_DETAILS	"details"

#define ZBX_PROTO_VALUE_GET_STATUS_PING		"ping"
#define ZBX_PROTO_VALUE_GET_STATUS_FULL		"full"

#define ZBX_PROTO_VALUE_ZABBIX_STATS		"zabbix.stats"
#define ZBX_PROTO_VALUE_ZABBIX_STATS_QUEUE	"queue"

#define ZBX_PROTO_VALUE_ZABBIX_ALERT_SEND	"alert.send"
#define ZBX_PROTO_VALUE_PREPROCESSING_TEST	"preprocessing.test"

typedef enum
{
	ZBX_JSON_TYPE_UNKNOWN = 0,
	ZBX_JSON_TYPE_STRING,
	ZBX_JSON_TYPE_INT,
	ZBX_JSON_TYPE_ARRAY,
	ZBX_JSON_TYPE_OBJECT,
	ZBX_JSON_TYPE_NULL,
	ZBX_JSON_TYPE_TRUE,
	ZBX_JSON_TYPE_FALSE
}
zbx_json_type_t;

typedef enum
{
	ZBX_JSON_EMPTY = 0,
	ZBX_JSON_COMMA
}
zbx_json_status_t;

#define ZBX_JSON_STAT_BUF_LEN 4096

struct zbx_json
{
	char			*buffer;
	char			buf_stat[ZBX_JSON_STAT_BUF_LEN];
	size_t			buffer_allocated;
	size_t			buffer_offset;
	size_t			buffer_size;
	zbx_json_status_t	status;
	int			level;
};

struct zbx_json_parse
{
	const char		*start;
	const char		*end;
};

const char	*zbx_json_strerror(void);

void	zbx_json_init(struct zbx_json *j, size_t allocate);
void	zbx_json_initarray(struct zbx_json *j, size_t allocate);
void	zbx_json_clean(struct zbx_json *j);
void	zbx_json_free(struct zbx_json *j);
void	zbx_json_addobject(struct zbx_json *j, const char *name);
void	zbx_json_addarray(struct zbx_json *j, const char *name);
void	zbx_json_addstring(struct zbx_json *j, const char *name, const char *string, zbx_json_type_t type);
void	zbx_json_adduint64(struct zbx_json *j, const char *name, zbx_uint64_t value);
void	zbx_json_addint64(struct zbx_json *j, const char *name, zbx_int64_t value);
void	zbx_json_addraw(struct zbx_json *j, const char *name, const char *data);
void	zbx_json_addfloat(struct zbx_json *j, const char *name, double value);
int	zbx_json_close(struct zbx_json *j);

int		zbx_json_open(const char *buffer, struct zbx_json_parse *jp);
const char	*zbx_json_next(const struct zbx_json_parse *jp, const char *p);
const char	*zbx_json_next_value(const struct zbx_json_parse *jp, const char *p, char *string, size_t len,
		zbx_json_type_t *type);
const char	*zbx_json_next_value_dyn(const struct zbx_json_parse *jp, const char *p, char **string,
		size_t *string_alloc, zbx_json_type_t *type);
const char	*zbx_json_pair_next(const struct zbx_json_parse *jp, const char *p, char *name, size_t len);
const char	*zbx_json_pair_by_name(const struct zbx_json_parse *jp, const char *name);
int		zbx_json_value_by_name(const struct zbx_json_parse *jp, const char *name, char *string, size_t len,
		zbx_json_type_t *type);
int		zbx_json_value_by_name_dyn(const struct zbx_json_parse *jp, const char *name, char **string,
		size_t *string_alloc, zbx_json_type_t *type);
int		zbx_json_brackets_open(const char *p, struct zbx_json_parse *out);
int		zbx_json_brackets_by_name(const struct zbx_json_parse *jp, const char *name, struct zbx_json_parse *out);
int		zbx_json_object_is_empty(const struct zbx_json_parse *jp);
int		zbx_json_count(const struct zbx_json_parse *jp);
const char	*zbx_json_decodevalue(const char *p, char *string, size_t size, zbx_json_type_t *type);
const char	*zbx_json_decodevalue_dyn(const char *p, char **string, size_t *string_alloc, zbx_json_type_t *type);
void		zbx_json_escape(char **string);

/* jsonpath support */

typedef struct zbx_jsonpath_segment zbx_jsonpath_segment_t;

typedef struct
{
	zbx_jsonpath_segment_t	*segments;
	int			segments_num;
	int			segments_alloc;

	/* set to 1 when jsonpath points at single location */
	unsigned char		definite;
}
zbx_jsonpath_t;

void	zbx_jsonpath_clear(zbx_jsonpath_t *jsonpath);
int	zbx_jsonpath_compile(const char *path, zbx_jsonpath_t *jsonpath);
int	zbx_jsonpath_query(const struct zbx_json_parse *jp, const char *path, char **output);

#endif /* ZABBIX_ZJSON_H */
