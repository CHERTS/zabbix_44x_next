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
** but WITHOUT ANY WARRANTY; without even the envied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "common.h"
#include "log.h"
#include "zbxjson.h"
#include "zbxembed.h"
#include "httprequest.h"
#include "embed.h"
#include "duktape.h"

#ifdef HAVE_LIBCURL

typedef struct
{
	CURL			*handle;
	struct curl_slist	*headers;
	char			*data;
	size_t			data_alloc;
	size_t			data_offset;
	unsigned char		custom_header;
}
zbx_es_httprequest_t;

#define ZBX_CURL_SETOPT(ctx, handle, opt, value, err)							\
	if (CURLE_OK != (err = curl_easy_setopt(handle, opt, value)))					\
	{												\
		return duk_error(ctx, DUK_RET_TYPE_ERROR, "cannot set cURL option " #opt ": %s.",	\
				curl_easy_strerror(err));						\
	}

static size_t	curl_write_cb(void *ptr, size_t size, size_t nmemb, void *userdata)
{
	size_t				r_size = size * nmemb;
	zbx_es_httprequest_t	*request = (zbx_es_httprequest_t *)userdata;

	zbx_strncpy_alloc(&request->data, &request->data_alloc, &request->data_offset, (const char *)ptr, r_size);

	return r_size;
}

/******************************************************************************
 *                                                                            *
 * Function: es_httprequest                                                   *
 *                                                                            *
 * Purpose: return backing C structure embedded in CurlHttpRequest object     *
 *                                                                            *
 ******************************************************************************/
static zbx_es_httprequest_t *es_httprequest(duk_context *ctx)
{
	zbx_es_httprequest_t	*request;

	duk_push_this(ctx);
	duk_get_prop_string(ctx, -1, "\xff""\xff""d");
	request = (zbx_es_httprequest_t *)duk_to_pointer(ctx, -1);
	duk_pop(ctx);

	return request;
}

/******************************************************************************
 *                                                                            *
 * Function: es_httprequest_dtor                                              *
 *                                                                            *
 * Purpose: CurlHttpRequest destructor                                        *
 *                                                                            *
 ******************************************************************************/
static duk_ret_t	es_httprequest_dtor(duk_context *ctx)
{
	zbx_es_httprequest_t	*request;

	duk_get_prop_string(ctx, 0, "\xff""\xff""d");
	request = (zbx_es_httprequest_t *)duk_to_pointer(ctx, -1);
	if (NULL != request)
	{
		if (NULL != request->headers)
			curl_slist_free_all(request->headers);
		if (NULL != request->handle)
			curl_easy_cleanup(request->handle);
		zbx_free(request->data);
		zbx_free(request);

		duk_push_pointer(ctx, NULL);
		duk_put_prop_string(ctx, 0, "\xff""\xff""d");
	}

	return 0;
}

/******************************************************************************
 *                                                                            *
 * Function: es_httprequest_ctor                                              *
 *                                                                            *
 * Purpose: CurlHttpRequest constructor                                       *
 *                                                                            *
 ******************************************************************************/
static duk_ret_t	es_httprequest_ctor(duk_context *ctx)
{
	zbx_es_httprequest_t	*request;
	CURLcode		err;
	zbx_es_env_t		*env;

	if (!duk_is_constructor_call(ctx))
		return DUK_RET_TYPE_ERROR;

	duk_push_global_stash(ctx);
	if (1 != duk_get_prop_string(ctx, -1, "\xff""\xff""zbx_env"))
		return duk_error(ctx, DUK_RET_TYPE_ERROR, "cannot access internal environment");
	env = (zbx_es_env_t *)duk_to_pointer(ctx, -1);
	duk_pop(ctx);

	duk_push_this(ctx);

	request = (zbx_es_httprequest_t *)zbx_malloc(NULL, sizeof(zbx_es_httprequest_t));
	memset(request, 0, sizeof(zbx_es_httprequest_t));

	if (NULL == (request->handle = curl_easy_init()))
		return duk_error(ctx, DUK_RET_TYPE_ERROR, "cannot initialize cURL library");

	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_COOKIEFILE, "", err);
	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_FOLLOWLOCATION, 1L, err);
	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_WRITEFUNCTION, curl_write_cb, err);
	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_WRITEDATA, request, err);
	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_PRIVATE, request, err);
	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_SSL_VERIFYPEER, 0L, err);
	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_TIMEOUT, (long)env->timeout, err);
	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_SSL_VERIFYHOST, 0L, err);

	duk_push_pointer(ctx, request);
	duk_put_prop_string(ctx, -2, "\xff""\xff""d");

	duk_push_c_function(ctx, es_httprequest_dtor, 1);
	duk_set_finalizer(ctx, -2);

	return 0;
}

/******************************************************************************
 *                                                                            *
 * Function: es_httprequest_add_header                                        *
 *                                                                            *
 * Purpose: CurlHttpRequest.SetHeader method                                  *
 *                                                                            *
 ******************************************************************************/
static duk_ret_t	es_httprequest_add_header(duk_context *ctx)
{
	zbx_es_httprequest_t	*request;
	CURLcode		err;
	char			*utf8 = NULL;

	if (NULL == (request = es_httprequest(ctx)))
		return duk_error(ctx, DUK_RET_TYPE_ERROR, "internal scripting error: null object");

	if (SUCCEED != zbx_cesu8_to_utf8(duk_to_string(ctx, 0), &utf8))
		return duk_error(ctx, DUK_RET_TYPE_ERROR, "cannot convert header to utf8");

	request->headers = curl_slist_append(request->headers, utf8);
	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_HTTPHEADER, request->headers, err);
	request->custom_header = 1;
	zbx_free(utf8);

	return 0;
}

/******************************************************************************
 *                                                                            *
 * Function: es_httprequest_clear_header                                      *
 *                                                                            *
 * Purpose: CurlHttpRequest.ClearHeader method                                *
 *                                                                            *
 ******************************************************************************/
static duk_ret_t	es_httprequest_clear_header(duk_context *ctx)
{
	zbx_es_httprequest_t	*request;

	if (NULL == (request = es_httprequest(ctx)))
		return duk_error(ctx, DUK_RET_TYPE_ERROR, "internal scripting error: null object");

	curl_slist_free_all(request->headers);
	request->headers = NULL;
	request->custom_header = 0;

	return 0;
}

/******************************************************************************
 *                                                                            *
 * Function: es_httprequest_query                                             *
 *                                                                            *
 * Purpose: CurlHttpRequest HTTP request implementation                       *
 *                                                                            *
 * Parameters: ctx          - [IN] the scripting engine context               *
 *             http_request - [IN] the HTTP request (GET, PUT, POST, DELETE)  *
 *                                                                            *
 ******************************************************************************/
static duk_ret_t	es_httprequest_query(duk_context *ctx, const char *http_request)
{
	zbx_es_httprequest_t	*request;
	char			*url = NULL, *contents = NULL;
	CURLcode		err;
	duk_ret_t		ret;

	if (SUCCEED != zbx_cesu8_to_utf8(duk_to_string(ctx, 0), &url))
	{
		ret = duk_error(ctx, DUK_RET_TYPE_ERROR, "cannot convert URL to utf8");
		goto out;
	}

	if (0 == duk_is_null_or_undefined(ctx, 1))
	{
		if (SUCCEED != zbx_cesu8_to_utf8(duk_to_string(ctx, 1), &contents))
		{
			ret = duk_error(ctx, DUK_RET_TYPE_ERROR, "cannot convert request contents to utf8");
			goto out;
		}
	}

	if (NULL == (request = es_httprequest(ctx)))
	{
		ret = duk_error(ctx, DUK_RET_TYPE_ERROR, "internal scripting error: null object");
		goto out;
	}

	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_URL, url, err);

	if (0 == request->custom_header)
	{
		struct zbx_json_parse	jp;

		if (NULL != request->headers)
		{
			curl_slist_free_all(request->headers);
			request->headers = NULL;
		}

		if (NULL != contents)
		{
			if (SUCCEED == zbx_json_open(contents, &jp))
				request->headers = curl_slist_append(NULL, "Content-Type: application/json");
			else
				request->headers = curl_slist_append(NULL, "Content-Type: text/plain");
		}
	}

	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_HTTPHEADER, request->headers, err);
	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_CUSTOMREQUEST, http_request, err);
	ZBX_CURL_SETOPT(ctx, request->handle, CURLOPT_POSTFIELDS, ZBX_NULL2EMPTY_STR(contents), err);

	request->data_offset = 0;

	if (CURLE_OK != (err = curl_easy_perform(request->handle)))
	{
		ret = duk_error(ctx, DUK_RET_TYPE_ERROR, "cannot get URL: %s.", curl_easy_strerror(err));
		goto out;
	}

	duk_push_string(ctx, request->data);
	ret = 1;
out:
	zbx_free(url);
	zbx_free(contents);

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: es_httprequest_get                                               *
 *                                                                            *
 * Purpose: CurlHttpRequest.Get method                                        *
 *                                                                            *
 ******************************************************************************/
static duk_ret_t	es_httprequest_get(duk_context *ctx)
{
	return es_httprequest_query(ctx, "GET");
}

/******************************************************************************
 *                                                                            *
 * Function: es_httprequest_put                                               *
 *                                                                            *
 * Purpose: CurlHttpRequest.Put method                                        *
 *                                                                            *
 ******************************************************************************/
static duk_ret_t	es_httprequest_put(duk_context *ctx)
{
	return es_httprequest_query(ctx, "PUT");
}

/******************************************************************************
 *                                                                            *
 * Function: es_httprequest_post                                              *
 *                                                                            *
 * Purpose: CurlHttpRequest.Post method                                       *
 *                                                                            *
 ******************************************************************************/
static duk_ret_t	es_httprequest_post(duk_context *ctx)
{
	return es_httprequest_query(ctx, "POST");
}

/******************************************************************************
 *                                                                            *
 * Function: es_httprequest_delete                                            *
 *                                                                            *
 * Purpose: CurlHttpRequest.Delete method                                     *
 *                                                                            *
 ******************************************************************************/
static duk_ret_t	es_httprequest_delete(duk_context *ctx)
{
	return es_httprequest_query(ctx, "DELETE");
}

/******************************************************************************
 *                                                                            *
 * Function: es_httprequest_status                                            *
 *                                                                            *
 * Purpose: CurlHttpRequest.Status method                                     *
 *                                                                            *
 ******************************************************************************/
static duk_ret_t	es_httprequest_status(duk_context *ctx)
{
	zbx_es_httprequest_t	*request;
	long			response_code;
	CURLcode		err;

	if (NULL == (request = es_httprequest(ctx)))
		return duk_error(ctx, DUK_RET_TYPE_ERROR, "internal scripting error: null object");

	if (CURLE_OK != (err = curl_easy_getinfo(request->handle, CURLINFO_RESPONSE_CODE, &response_code)))
		return duk_error(ctx, DUK_RET_TYPE_ERROR, "cannot obtain request status: %s", curl_easy_strerror(err));

	duk_push_number(ctx, (duk_double_t)response_code);

	return 1;
}

static const duk_function_list_entry	httprequest_methods[] = {
	{"AddHeader", es_httprequest_add_header, 1},
	{"ClearHeader", es_httprequest_clear_header, 0},
	{"Get", es_httprequest_get, 2},
	{"Put", es_httprequest_put, 2},
	{"Post", es_httprequest_post, 2},
	{"Delete", es_httprequest_delete, 2},
	{"Status", es_httprequest_status, 0},
	{NULL, NULL, 0}
};

#else

static duk_ret_t	es_httprequest_ctor(duk_context *ctx)
{
	if (!duk_is_constructor_call(ctx))
		return DUK_RET_TYPE_ERROR;

	return duk_error(ctx, DUK_RET_TYPE_ERROR, "missing cURL library");
}

static const duk_function_list_entry	httprequest_methods[] = {
	{NULL, NULL, 0}
};
#endif

static int	es_httprequest_create_prototype(duk_context *ctx)
{
	duk_push_c_function(ctx, es_httprequest_ctor, 0);
	duk_push_object(ctx);

	duk_put_function_list(ctx, -1, httprequest_methods);

	if (1 != duk_put_prop_string(ctx, -2, "prototype"))
		return FAIL;

	if (1 != duk_put_global_string(ctx, "CurlHttpRequest"))
		return FAIL;

	return SUCCEED;
}

int	zbx_es_init_httprequest(zbx_es_t *es, char **error)
{
	if (0 != setjmp(es->env->loc))
	{
		*error = zbx_strdup(*error, es->env->error);
		return FAIL;
	}

	if (FAIL == es_httprequest_create_prototype(es->env->ctx))
	{
		*error = zbx_strdup(*error, duk_safe_to_string(es->env->ctx, -1));
		duk_pop(es->env->ctx);
		return FAIL;
	}
	return SUCCEED;
}
