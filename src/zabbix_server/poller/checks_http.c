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

#include "checks_http.h"
#include "zbxhttp.h"
#include "zbxjson.h"
#include "log.h"
#ifdef HAVE_LIBCURL

#define HTTP_REQUEST_GET	0
#define HTTP_REQUEST_POST	1
#define HTTP_REQUEST_PUT	2
#define HTTP_REQUEST_HEAD	3

#define HTTP_STORE_RAW		0
#define HTTP_STORE_JSON		1

typedef struct
{
	char	*data;
	size_t	allocated;
	size_t	offset;
}
zbx_http_response_t;

static const char	*zbx_request_string(int result)
{
	switch (result)
	{
		case HTTP_REQUEST_GET:
			return "GET";
		case HTTP_REQUEST_POST:
			return "POST";
		case HTTP_REQUEST_PUT:
			return "PUT";
		case HTTP_REQUEST_HEAD:
			return "HEAD";
		default:
			return "unknown";
	}
}

static size_t	curl_write_cb(void *ptr, size_t size, size_t nmemb, void *userdata)
{
	size_t			r_size = size * nmemb;
	zbx_http_response_t	*response;

	response = (zbx_http_response_t*)userdata;
	zbx_str_memcpy_alloc(&response->data, &response->allocated, &response->offset, (const char *)ptr, r_size);

	return r_size;
}

static size_t	curl_ignore_cb(void *ptr, size_t size, size_t nmemb, void *userdata)
{
	ZBX_UNUSED(ptr);
	ZBX_UNUSED(userdata);

	return size * nmemb;
}

static int	http_prepare_request(CURL *easyhandle, const char *posts, unsigned char request_method, char **error)
{
	CURLcode	err;

	switch (request_method)
	{
		case HTTP_REQUEST_POST:
			if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_POSTFIELDS, posts)))
			{
				*error = zbx_dsprintf(*error, "Cannot specify data to POST: %s", curl_easy_strerror(err));
				return FAIL;
			}
			break;
		case HTTP_REQUEST_GET:
			if ('\0' == *posts)
				return SUCCEED;

			if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_POSTFIELDS, posts)))
			{
				*error = zbx_dsprintf(*error, "Cannot specify data to POST: %s", curl_easy_strerror(err));
				return FAIL;
			}

			if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_CUSTOMREQUEST, "GET")))
			{
				*error = zbx_dsprintf(*error, "Cannot specify custom GET request: %s",
						curl_easy_strerror(err));
				return FAIL;
			}
			break;
		case HTTP_REQUEST_HEAD:
			if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_NOBODY, 1L)))
			{
				*error = zbx_dsprintf(*error, "Cannot specify HEAD request: %s", curl_easy_strerror(err));
				return FAIL;
			}
			break;
		case HTTP_REQUEST_PUT:
			if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_POSTFIELDS, posts)))
			{
				*error = zbx_dsprintf(*error, "Cannot specify data to POST: %s", curl_easy_strerror(err));
				return FAIL;
			}

			if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_CUSTOMREQUEST, "PUT")))
			{
				*error = zbx_dsprintf(*error, "Cannot specify custom GET request: %s",
						curl_easy_strerror(err));
				return FAIL;
			}
			break;
		default:
			THIS_SHOULD_NEVER_HAPPEN;
			*error = zbx_strdup(*error, "Unsupported request method");
			return FAIL;
	}

	return SUCCEED;
}

static void	http_add_json_header(struct zbx_json *json, char *line)
{
	char	*colon;

	if (NULL != (colon = strchr(line, ':')))
	{
		zbx_ltrim(colon + 1, " \t");

		*colon = '\0';
		zbx_json_addstring(json, line, colon + 1, ZBX_JSON_TYPE_STRING);
		*colon = ':';
	}
	else
		zbx_json_addstring(json, line, "", ZBX_JSON_TYPE_STRING);
}

static void	http_output_json(unsigned char retrieve_mode, char **buffer, zbx_http_response_t *header,
		zbx_http_response_t *body)
{
	struct zbx_json		json;
	struct zbx_json_parse	jp;
	char			*headers, *line;
	unsigned char		json_content = 0;

	zbx_json_init(&json, ZBX_JSON_STAT_BUF_LEN);

	headers = header->data;

	if (retrieve_mode != ZBX_RETRIEVE_MODE_CONTENT)
		zbx_json_addobject(&json, "header");

	while (NULL != (line = zbx_http_get_header(&headers)))
	{
		if (0 == json_content && 0 == strncmp(line, "Content-Type:",
				ZBX_CONST_STRLEN("Content-Type:")) &&
				NULL != strstr(line, "application/json"))
		{
			json_content = 1;
		}

		if (retrieve_mode != ZBX_RETRIEVE_MODE_CONTENT)
			http_add_json_header(&json, line);

		zbx_free(line);
	}

	if (retrieve_mode != ZBX_RETRIEVE_MODE_CONTENT)
		zbx_json_close(&json);

	if (NULL != body->data)
	{
		if (0 == json_content)
		{
			zbx_json_addstring(&json, "body", body->data, ZBX_JSON_TYPE_STRING);
		}
		else if (FAIL == zbx_json_open(body->data, &jp))
		{
			zbx_json_addstring(&json, "body", body->data, ZBX_JSON_TYPE_STRING);
			zabbix_log(LOG_LEVEL_DEBUG, "received invalid JSON object %s", zbx_json_strerror());
		}
		else
		{
			zbx_lrtrim(body->data, ZBX_WHITESPACE);
			zbx_json_addraw(&json, "body", body->data);
		}
	}

	*buffer = zbx_strdup(NULL, json.buffer);
	zbx_json_free(&json);
}

int	get_value_http(const DC_ITEM *item, AGENT_RESULT *result)
{
	CURL			*easyhandle;
	CURLcode		err;
	char			url[ITEM_URL_LEN_MAX], errbuf[CURL_ERROR_SIZE], *error = NULL, *headers, *line, *buffer;
	int			ret = NOTSUPPORTED, timeout_seconds, found = FAIL;
	long			response_code;
	struct curl_slist	*headers_slist = NULL;
	struct zbx_json		json;
	zbx_http_response_t	body = {0}, header = {0};
	size_t			(*curl_body_cb)(void *ptr, size_t size, size_t nmemb, void *userdata);
	char			application_json[] = {"Content-Type: application/json"};
	char			application_xml[] = {"Content-Type: application/xml"};

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() request method '%s' URL '%s%s' headers '%s' message body '%s'",
			__func__, zbx_request_string(item->request_method), item->url, item->query_fields,
			item->headers, item->posts);

	if (NULL == (easyhandle = curl_easy_init()))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot initialize cURL library"));
		goto clean;
	}

	switch (item->retrieve_mode)
	{
		case ZBX_RETRIEVE_MODE_CONTENT:
		case ZBX_RETRIEVE_MODE_BOTH:
			curl_body_cb = curl_write_cb;
			break;
		case ZBX_RETRIEVE_MODE_HEADERS:
			curl_body_cb = curl_ignore_cb;
			break;
		default:
			THIS_SHOULD_NEVER_HAPPEN;
			SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Invalid retrieve mode"));
			goto clean;
	}

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_HEADERFUNCTION, curl_write_cb)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot set header function: %s",
				curl_easy_strerror(err)));
		goto clean;
	}

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_HEADERDATA, &header)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot set header callback: %s",
				curl_easy_strerror(err)));
		goto clean;
	}

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_WRITEFUNCTION, curl_body_cb)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot set write function: %s", curl_easy_strerror(err)));
		goto clean;
	}

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_WRITEDATA, &body)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot set write callback: %s", curl_easy_strerror(err)));
		goto clean;
	}

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_ERRORBUFFER, errbuf)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot set error buffer: %s", curl_easy_strerror(err)));
		goto clean;
	}

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_PROXY, item->http_proxy)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot set proxy: %s", curl_easy_strerror(err)));
		goto clean;
	}

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_FOLLOWLOCATION,
			0 == item->follow_redirects ? 0L : 1L)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot set follow redirects: %s", curl_easy_strerror(err)));
		goto clean;
	}

	if (0 != item->follow_redirects &&
			CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_MAXREDIRS, ZBX_CURLOPT_MAXREDIRS)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot set number of redirects allowed: %s",
				curl_easy_strerror(err)));
		goto clean;
	}

	if (FAIL == is_time_suffix(item->timeout, &timeout_seconds, strlen(item->timeout)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Invalid timeout: %s", item->timeout));
		goto clean;
	}

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_TIMEOUT, (long)timeout_seconds)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot specify timeout: %s", curl_easy_strerror(err)));
		goto clean;
	}

	if (SUCCEED != zbx_http_prepare_ssl(easyhandle, item->ssl_cert_file, item->ssl_key_file, item->ssl_key_password,
			item->verify_peer, item->verify_host, &error))
	{
		SET_MSG_RESULT(result, error);
		goto clean;
	}

	if (SUCCEED != zbx_http_prepare_auth(easyhandle, item->authtype, item->username, item->password, &error))
	{
		SET_MSG_RESULT(result, error);
		goto clean;
	}

	if (SUCCEED != http_prepare_request(easyhandle, item->posts, item->request_method, &error))
	{
		SET_MSG_RESULT(result, error);
		goto clean;
	}

	headers = item->headers;
	while (NULL != (line = zbx_http_get_header(&headers)))
	{
		headers_slist = curl_slist_append(headers_slist, line);

		if (FAIL == found && 0 == strncmp(line, "Content-Type:", ZBX_CONST_STRLEN("Content-Type:")))
			found = SUCCEED;

		zbx_free(line);
	}

	if (FAIL == found)
	{
		if (ZBX_POSTTYPE_JSON == item->post_type)
			headers_slist = curl_slist_append(headers_slist, application_json);
		else if (ZBX_POSTTYPE_XML == item->post_type)
			headers_slist = curl_slist_append(headers_slist, application_xml);
	}

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_HTTPHEADER, headers_slist)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot specify headers: %s", curl_easy_strerror(err)));
		goto clean;
	}

#if LIBCURL_VERSION_NUM >= 0x071304
	/* CURLOPT_PROTOCOLS is supported starting with version 7.19.4 (0x071304) */
	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot set allowed protocols: %s", curl_easy_strerror(err)));
		goto clean;
	}
#endif

	zbx_snprintf(url, sizeof(url),"%s%s", item->url, item->query_fields);
	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, CURLOPT_URL, url)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot specify URL: %s", curl_easy_strerror(err)));
		goto clean;
	}

	*errbuf = '\0';

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot perform request: %s",
				'\0' == *errbuf ? curl_easy_strerror(err) : errbuf));
		goto clean;
	}

	if (CURLE_OK != (err = curl_easy_getinfo(easyhandle, CURLINFO_RESPONSE_CODE, &response_code)))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot get the response code: %s", curl_easy_strerror(err)));
		goto clean;
	}

	if ('\0' != *item->status_codes && FAIL == int_in_list(item->status_codes, response_code))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Response code \"%ld\" did not match any of the"
				" required status codes \"%s\"", response_code, item->status_codes));
		goto clean;
	}

	if (NULL == header.data)
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Server returned empty header"));
		goto clean;
	}

	switch (item->retrieve_mode)
	{
		case ZBX_RETRIEVE_MODE_CONTENT:
			if (NULL == body.data)
			{
				SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Server returned empty content"));
				goto clean;
			}

			if (FAIL == zbx_is_utf8(body.data))
			{
				SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Server returned invalid UTF-8 sequence"));
				goto clean;
			}

			if (HTTP_STORE_JSON == item->output_format)
			{
				http_output_json(item->retrieve_mode, &buffer, &header, &body);
				SET_TEXT_RESULT(result, buffer);
			}
			else
			{
				SET_TEXT_RESULT(result, body.data);
				body.data = NULL;
			}
			break;
		case ZBX_RETRIEVE_MODE_HEADERS:
			if (FAIL == zbx_is_utf8(header.data))
			{
				SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Server returned invalid UTF-8 sequence"));
				goto clean;
			}

			if (HTTP_STORE_JSON == item->output_format)
			{
				zbx_json_init(&json, ZBX_JSON_STAT_BUF_LEN);
				zbx_json_addobject(&json, "header");
				headers = header.data;
				while (NULL != (line = zbx_http_get_header(&headers)))
				{
					http_add_json_header(&json, line);
					zbx_free(line);
				}
				SET_TEXT_RESULT(result, zbx_strdup(NULL, json.buffer));
				zbx_json_free(&json);
			}
			else
			{
				SET_TEXT_RESULT(result, header.data);
				header.data = NULL;
			}
			break;
		case ZBX_RETRIEVE_MODE_BOTH:
			if (FAIL == zbx_is_utf8(header.data) || (NULL != body.data && FAIL == zbx_is_utf8(body.data)))
			{
				SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Server returned invalid UTF-8 sequence"));
				goto clean;
			}

			if (HTTP_STORE_JSON == item->output_format)
			{
				http_output_json(item->retrieve_mode, &buffer, &header, &body);
				SET_TEXT_RESULT(result, buffer);
			}
			else
			{
				zbx_strncpy_alloc(&header.data, &header.allocated, &header.offset,
						body.data, body.offset);
				SET_TEXT_RESULT(result, header.data);
				header.data = NULL;
			}
			break;
	}

	ret = SUCCEED;
clean:
	curl_slist_free_all(headers_slist);	/* must be called after curl_easy_perform() */
	curl_easy_cleanup(easyhandle);
	zbx_free(body.data);
	zbx_free(header.data);
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}
#endif
