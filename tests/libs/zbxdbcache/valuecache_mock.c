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
#include "log.h"
#include "mutexs.h"
#include "memalloc.h"
#include "zbxalgo.h"
#include "zbxhistory.h"
#include "history.h"
#include "valuecache.h"
#include "dbcache.h"

#include <setjmp.h>
#include <cmocka.h>

#include "zbxmockassert.h"
#include "zbxmockdata.h"
#include "zbxmockutil.h"
#include "valuecache_test.h"
#include "valuecache_mock.h"

/*
 * data source
 */
static zbx_vcmock_ds_t	vc_ds;
static time_t	vcmock_time;

int	__wrap_zbx_mutex_create(zbx_mutex_t *mutex, zbx_mutex_name_t name, char **error);
void	__wrap_zbx_mutex_destroy(zbx_mutex_t *mutex);
int	__wrap_zbx_mem_create(zbx_mem_info_t **info, zbx_uint64_t size, const char *descr, const char *param,
		int allow_oom, char **error);
void	*__wrap___zbx_mem_malloc(const char *file, int line, zbx_mem_info_t *info, const void *old, size_t size);
void	*__wrap___zbx_mem_realloc(const char *file, int line, zbx_mem_info_t *info, void *old, size_t size);
void	__wrap___zbx_mem_free(const char *file, int line, zbx_mem_info_t *info, void *ptr);
void	__wrap_zbx_mem_dump_stats(int level, zbx_mem_info_t *info);
int	__wrap_zbx_history_get_values(zbx_uint64_t itemid, int value_type, int start, int count, int end,
		zbx_vector_history_record_t *values);
int	__wrap_zbx_history_add_values(const zbx_vector_ptr_t *history);
int	__wrap_zbx_history_sql_init(zbx_history_iface_t *hist, unsigned char value_type, char **error);
int	__wrap_zbx_history_elastic_init(zbx_history_iface_t *hist, unsigned char value_type, char **error);
time_t	__wrap_time(time_t *ptr);

/* comparison function to sort history record vector by timestamps in ascending order */
static int	history_compare(const void *d1, const void *d2)
{
	zbx_history_record_t	*h1 = (zbx_history_record_t *)d1;
	zbx_history_record_t	*h2 = (zbx_history_record_t *)d2;

	return zbx_timespec_compare(&h1->timestamp, &h2->timestamp);
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_read_history_value                                    *
 *                                                                            *
 * Purpose: reads history value and timestamp from input data                 *
 *                                                                            *
 * Parameters: hvalue     - [IN] handle to the history record mapping         *
 *             value_type - [IN] the value type of the history data           *
 *             value      - [OUT] the history value                           *
 *             ts         - [OUT] the history value timestamp                 *
 *                                                                            *
 ******************************************************************************/

static void	zbx_vcmock_read_history_value(zbx_mock_handle_t hvalue, unsigned char value_type,
		history_value_t *value, zbx_timespec_t *ts)
{
	const char		*data;
	zbx_mock_error_t	err;

	data = zbx_mock_get_object_member_string(hvalue, "value");

	if (ITEM_VALUE_TYPE_LOG != value_type)
	{
		switch (value_type)
		{
			case ITEM_VALUE_TYPE_STR:
			case ITEM_VALUE_TYPE_TEXT:
				value->str = zbx_strdup(NULL, data);
				break;
			case ITEM_VALUE_TYPE_UINT64:
				if (FAIL == is_uint64(data, &value->ui64))
					fail_msg("Invalid uint64 value \"%s\"", data);
				break;
			case ITEM_VALUE_TYPE_FLOAT:
				value->dbl = atof(data);
		}
	}
	else
	{
		zbx_log_value_t		*log;

		log = (zbx_log_value_t *)zbx_malloc(NULL, sizeof(zbx_log_value_t));

		log->value = zbx_strdup(NULL, data);
		log->source = zbx_strdup(NULL, zbx_mock_get_object_member_string(hvalue, "source"));

		data = zbx_mock_get_object_member_string(hvalue, "logeventid");
		if (FAIL == is_uint64(data, &log->logeventid))
			fail_msg("Invalid log logeventid value \"%s\"", data);

		data = zbx_mock_get_object_member_string(hvalue, "severity");
		if (FAIL == is_uint32(data, &log->severity))
			fail_msg("Invalid log severity value \"%s\"", data);

		data = zbx_mock_get_object_member_string(hvalue, "timestamp");
		if (FAIL == is_uint32(data, &log->timestamp))
			fail_msg("Invalid log timestamp value \"%s\"", data);

		value->log = log;
	}

	data = zbx_mock_get_object_member_string(hvalue, "ts");
	if (ZBX_MOCK_SUCCESS != (err = zbx_strtime_to_timespec(data, ts)))
		fail_msg("Invalid value timestamp \"%s\": %s", data, zbx_mock_error_string(err));
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_ds_read_item                                          *
 *                                                                            *
 * Purpose: reads value cache data store item                                 *
 *                                                                            *
 * Parameters: hvalue - [IN] handle to the history record mapping             *
 *             item   - [OUT] the data store item                             *
 *                                                                            *
 ******************************************************************************/
static void	zbx_vcmock_ds_read_item(zbx_mock_handle_t hitem, zbx_vcmock_ds_item_t *item)
{
	const char	*itemid;

	itemid = zbx_mock_get_object_member_string(hitem, "itemid");
	if (SUCCEED != is_uint64(itemid, &item->itemid))
		fail_msg("Invalid itemid \"%s\"", itemid);

	item->value_type = zbx_mock_str_to_value_type(zbx_mock_get_object_member_string(hitem, "value type"));

	zbx_vector_history_record_create(&item->data);
	zbx_vcmock_read_values(zbx_mock_get_object_member_handle(hitem, "data"), item->value_type, &item->data);
	zbx_vector_history_record_sort(&item->data, history_compare);
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_ds_clone_record                                       *
 *                                                                            *
 * Purpose: duplicates history record                                         *
 *                                                                            *
 * Parameters: src        - [IN] source history record                        *
 *             value_type - [IN] the history record value type                *
 *             dst        - [OUT] the output record with duplicated data      *
 *                                                                            *
 ******************************************************************************/
static void	zbx_vcmock_ds_clone_record(const zbx_history_record_t *src, unsigned char value_type,
		zbx_history_record_t *dst)
{
	zbx_log_value_t	*log;

	dst->timestamp = src->timestamp;

	switch (value_type)
	{
		case ITEM_VALUE_TYPE_FLOAT:
		case ITEM_VALUE_TYPE_UINT64:
			dst->value = src->value;
			break;
		case ITEM_VALUE_TYPE_STR:
		case ITEM_VALUE_TYPE_TEXT:
			dst->value.str = zbx_strdup(NULL, src->value.str);
			break;
		case ITEM_VALUE_TYPE_LOG:
			log = (zbx_log_value_t *)zbx_malloc(NULL, sizeof(zbx_log_value_t));
			log->value = zbx_strdup(NULL, src->value.log->value);
			log->source = zbx_strdup(NULL, src->value.log->source);
			log->logeventid = src->value.log->logeventid;
			log->severity = src->value.log->severity;
			log->timestamp = src->value.log->timestamp;
			dst->value.log = log;
			break;
	}
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_read_values                                           *
 *                                                                            *
 * Purpose: reads historical values from input data                           *
 *                                                                            *
 * Parameters: hdata      - [IN] handle to the history values in input data   *
 *             value_type - [IN] the history record value type                *
 *             values     - [OUT] the read values                             *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_read_values(zbx_mock_handle_t hdata, unsigned char value_type, zbx_vector_history_record_t *values)
{
	zbx_mock_error_t	err;
	zbx_mock_handle_t	hvalue;
	zbx_history_record_t	rec;

	while (ZBX_MOCK_END_OF_VECTOR != (err = (zbx_mock_vector_element(hdata, &hvalue))))
	{
		zbx_vcmock_read_history_value(hvalue, value_type, &rec.value, &rec.timestamp);
		zbx_vector_history_record_append_ptr(values, &rec);
	}
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_ds_init                                               *
 *                                                                            *
 * Purpose: initializes history data storage                                  *
 *                                                                            *
 * Comments: History data storage is used to emulate history storage backend. *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_ds_init(void)
{
	zbx_mock_handle_t	hitems, hitem;
	zbx_mock_error_t	err;
	int			items_num = 0;
	zbx_vcmock_ds_item_t	item;

	if (0 != setenv("TZ", "UTC", 1))
		fail_msg("Cannot set 'TZ' environment variable: %s", zbx_strerror(errno));

	tzset();

	zbx_hashset_create(&vc_ds.items, 10, ZBX_DEFAULT_UINT64_HASH_FUNC, ZBX_DEFAULT_UINT64_COMPARE_FUNC);

	hitems = zbx_mock_get_parameter_handle("in.history");

	while (ZBX_MOCK_END_OF_VECTOR != (err = (zbx_mock_vector_element(hitems, &hitem))))
	{
		if (ZBX_MOCK_SUCCESS != err)
			fail_msg("Cannot read 'history' element #%d: %s", items_num, zbx_mock_error_string(err));

		zbx_vcmock_ds_read_item(hitem, &item);
		zbx_hashset_insert(&vc_ds.items, &item, sizeof(item));

		items_num++;
	}
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_ds_destroy                                            *
 *                                                                            *
 * Purpose: destroys history data storage                                     *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_ds_destroy(void)
{
	zbx_hashset_iter_t	iter;
	zbx_vcmock_ds_item_t	*item;

	zbx_hashset_iter_reset(&vc_ds.items, &iter);
	while (NULL != (item = zbx_hashset_iter_next(&iter)))
		zbx_history_record_vector_destroy(&item->data, item->value_type);

	zbx_hashset_destroy(&vc_ds.items);
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_history_dump                                          *
 *                                                                            *
 * Purpose: dumps history record vector contents to standard output           *
 *                                                                            *
 ******************************************************************************/
static void	zbx_vcmock_history_dump(unsigned char value_type, const zbx_vector_history_record_t *values)
{
	int	i;
	char	buffer[256];

	for (i = 0; i < values->values_num; i++)
	{
		const zbx_history_record_t	*rec = &values->values[i];

		zbx_timespec_to_strtime(&rec->timestamp, buffer, sizeof(buffer));
		printf("  - %s\n", buffer);
		zbx_history_value2str(buffer, sizeof(buffer), &rec->value, value_type);
		printf("    %s\n", buffer);
	}
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_ds_dump                                               *
 *                                                                            *
 * Purpose: dumps history data store to standard output                       *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_ds_dump(void)
{
	zbx_hashset_iter_t	iter;
	zbx_vcmock_ds_item_t	*item;

	zbx_hashset_iter_reset((zbx_hashset_t *)&vc_ds.items, &iter);
	while (NULL != (item = zbx_hashset_iter_next(&iter)))
	{
		printf("itemid:" ZBX_FS_UI64 ", value_type:%d\n", item->itemid, item->value_type);
		zbx_vcmock_history_dump(item->value_type, &item->data);
	}
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_get_cache_mode                                        *
 *                                                                            *
 * Purpose: converts value cache mode from text format                        *
 *                                                                            *
 ******************************************************************************/
int	zbx_vcmock_str_to_cache_mode(const char *mode)
{
	if (0 == strcmp(mode, "ZBX_VC_MODE_NORMAL"))
		return ZBX_VC_MODE_NORMAL;

	if (0 == strcmp(mode, "ZBX_VC_MODE_LOWMEM"))
		return ZBX_VC_MODE_LOWMEM;

	fail_msg("Unknown value cache mode \"%s\"", mode);
	return FAIL;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_str_to_item_status                                    *
 *                                                                            *
 * Purpose: converts value cache item status from text format                 *
 *                                                                            *
 ******************************************************************************/
int	zbx_vcmock_str_to_item_status(const char *str)
{
	if (0 == strcmp(str, "ZBX_ITEM_STATUS_CACHED_ALL"))
		return ZBX_ITEM_STATUS_CACHED_ALL;

	return 0;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_check_records                                         *
 *                                                                            *
 * Purpose: Compares two history record vectors and throw assertion if either *
 *          values or timestamps don't match                                  *
 *                                                                            *
 * Parameters: prefix          - [IN] the assert message prefix               *
 *             value_type      - [IN] the value type                          *
 *             expected_values - [IN] the expected history records            *
 *             returned_values - [IN] the returned history records            *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_check_records(const char *prefix, unsigned char value_type,
		const zbx_vector_history_record_t *expected_values, const zbx_vector_history_record_t *returned_values)
{
	int				i;
	const zbx_history_record_t	*expected, *returned;
	const zbx_log_value_t		*expected_log, *returned_log;

	printf("Expected values:\n");
	zbx_vcmock_history_dump(value_type, expected_values);

	printf("Returned values:\n");
	zbx_vcmock_history_dump(value_type, returned_values);

	zbx_mock_assert_int_eq(prefix, expected_values->values_num, returned_values->values_num);

	for (i = 0; i < expected_values->values_num; i++)
	{
		expected = &expected_values->values[i];
		returned = &returned_values->values[i];

		zbx_mock_assert_timespec_eq(prefix, &expected->timestamp, &returned->timestamp);

		switch (value_type)
		{
			case ITEM_VALUE_TYPE_STR:
			case ITEM_VALUE_TYPE_TEXT:
				zbx_mock_assert_str_eq(prefix, expected->value.str, returned->value.str);
				break;
			case ITEM_VALUE_TYPE_UINT64:
				zbx_mock_assert_uint64_eq(prefix, expected->value.ui64, returned->value.ui64);
				break;
			case ITEM_VALUE_TYPE_LOG:
				expected_log = expected->value.log;
				returned_log = returned->value.log;
				zbx_mock_assert_str_eq(prefix, expected_log->value, returned_log->value);
				zbx_mock_assert_str_eq(prefix, expected_log->source, returned_log->source);
				zbx_mock_assert_uint64_eq(prefix, expected_log->logeventid, returned_log->logeventid);
				break;
			case ITEM_VALUE_TYPE_FLOAT:
				zbx_mock_assert_double_eq(prefix, expected->value.dbl, returned->value.dbl);
				break;
		}
	}
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_get_dc_history                                        *
 *                                                                            *
 * Purpose: reads ZBX_DC_HISTORY vector from input data                       *
 *                                                                            *
 * Parameters: handle  - [IN] the history data handle in input data           *
 *             history - [OUT] the history records                            *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_get_dc_history(zbx_mock_handle_t handle, zbx_vector_ptr_t *history)
{
	zbx_mock_handle_t	hitem, hdata;
	zbx_mock_error_t	err;
	ZBX_DC_HISTORY		*data;
	const char		*itemid;

	while (ZBX_MOCK_END_OF_VECTOR != (err = (zbx_mock_vector_element(handle, &hitem))))
	{
		if (ZBX_MOCK_SUCCESS != err)
		{
			fail_msg("Cannot read 'values' element #%d: %s", history->values_num,
					zbx_mock_error_string(err));
		}

		data = (ZBX_DC_HISTORY *)zbx_malloc(NULL, sizeof(ZBX_DC_HISTORY));
		memset(data, 0, sizeof(ZBX_DC_HISTORY));

		itemid = zbx_mock_get_object_member_string(hitem, "itemid");
		if (SUCCEED != is_uint64(itemid, &data->itemid))
			fail_msg("Invalid itemid \"%s\"", itemid);

		data->value_type = zbx_mock_str_to_value_type(zbx_mock_get_object_member_string(hitem, "value type"));
		hdata = zbx_mock_get_object_member_handle(hitem, "data");
		zbx_vcmock_read_history_value(hdata, data->value_type, &data->value, &data->ts);

		zbx_vector_ptr_append(history, data);
	}
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_free_dc_history                                       *
 *                                                                            *
 * Purpose: frees ZBX_DC_HISTORY structure                                    *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_free_dc_history(void *ptr)
{
	ZBX_DC_HISTORY	*h = (ZBX_DC_HISTORY *)ptr;

	switch (h->value_type)
	{
		case ITEM_VALUE_TYPE_STR:
		case ITEM_VALUE_TYPE_TEXT:
			zbx_free(h->value.str);
			break;
		case ITEM_VALUE_TYPE_LOG:
			zbx_free(h->value.log->source);
			zbx_free(h->value.log->value);
			zbx_free(h->value.log);
			break;
	}

	zbx_free(h);
}

/*
 * mock functions
 */

static zbx_mutex_t	*vc_mutex = NULL;
zbx_mem_info_t		*vc_meminfo = NULL;

static size_t		vcmock_mem = ZBX_MEBIBYTE * 1024;

int	__wrap_zbx_mutex_create(zbx_mutex_t *mutex, zbx_mutex_name_t name, char **error)
{
	vc_mutex = mutex;
	ZBX_UNUSED(name);
	ZBX_UNUSED(error);

	return SUCCEED;
}

void	__wrap_zbx_mutex_destroy(zbx_mutex_t *mutex)
{
	zbx_mock_assert_ptr_eq("Attempting to destroy unknown mutex", vc_mutex, mutex);
}

int	__wrap_zbx_mem_create(zbx_mem_info_t **info, zbx_uint64_t size, const char *descr, const char *param,
		int allow_oom, char **error)
{
	*info = vc_meminfo;
	ZBX_UNUSED(size);
	ZBX_UNUSED(descr);
	ZBX_UNUSED(param);
	ZBX_UNUSED(allow_oom);
	ZBX_UNUSED(error);

	return SUCCEED;
}

void	*__wrap___zbx_mem_malloc(const char *file, int line, zbx_mem_info_t *info, const void *old, size_t size)
{
	size_t	*psize;

	ZBX_UNUSED(file);
	ZBX_UNUSED(line);

	zbx_mock_assert_ptr_eq("Unknown memory info block in memory allocator", vc_meminfo, info);
	zbx_mock_assert_ptr_eq("Allocating unfreed memory", NULL, old);

	if (vcmock_mem < size)
		return NULL;

	psize = (size_t *)zbx_malloc(NULL, size + sizeof(size_t));
	vcmock_mem -= size;
	*psize = size;

	return (void *)(psize + 1);
}

void	*__wrap___zbx_mem_realloc(const char *file, int line, zbx_mem_info_t *info, void *old, size_t size)
{
	size_t	*psize;

	ZBX_UNUSED(file);
	ZBX_UNUSED(line);

	zbx_mock_assert_ptr_eq("Unknown memory info block in memory reallocator", vc_meminfo, info);

	psize = (size_t *)((char *)old - sizeof(size_t));

	if (vcmock_mem + *psize < size)
		return NULL;

	psize = (size_t *)zbx_realloc(psize, size + sizeof(size_t));
	vcmock_mem -= size;
	*psize = size;

	return (void *)(psize + 1);
}

void	__wrap___zbx_mem_free(const char *file, int line, zbx_mem_info_t *info, void *ptr)
{
	size_t	*psize;

	ZBX_UNUSED(file);
	ZBX_UNUSED(line);

	zbx_mock_assert_ptr_eq("Unknown memory info block in memory destructor", vc_meminfo, info);

	if (NULL == ptr)
		return;

	psize = (size_t *)((char *)ptr - sizeof(size_t));

	vcmock_mem += *psize;

	zbx_free(psize);
}

void	__wrap_zbx_mem_dump_stats(int level, zbx_mem_info_t *info)
{
	ZBX_UNUSED(level);
	ZBX_UNUSED(info);
}

int	__wrap_zbx_history_get_values(zbx_uint64_t itemid, int value_type, int start, int count, int end,
		zbx_vector_history_record_t *values)
{
	zbx_vcmock_ds_item_t	*item;
	zbx_history_record_t	*rec, rec_local;
	int			i;

	if (0 > start)
		fail_msg("invalid parameters passed to zbx_history_get_values function (start < 0)");

	if (start >= end)
		fail_msg("invalid parameters passed to zbx_history_get_values function (start >= end)");

	if (NULL == (item = zbx_hashset_search(&vc_ds.items, &itemid)))
		return SUCCEED;

	if (0 == count)
		count--;

	for (i = item->data.values_num - 1; 0 <= i; i--)
	{
		rec = &item->data.values[i];
		if (rec->timestamp.sec > end)
			continue;

		if (rec->timestamp.sec <= start || 0 == count--)
			break;

		zbx_vcmock_ds_clone_record(rec, value_type, &rec_local);
		zbx_vector_history_record_append_ptr(values, &rec_local);
	}

	return SUCCEED;
}

int	__wrap_zbx_history_add_values(const zbx_vector_ptr_t *history)
{
	int			i;
	zbx_vcmock_ds_item_t	*item, item_local;
	zbx_history_record_t	src, dst;

	ZBX_UNUSED(history);

	for (i = 0; i < history->values_num; i++)
	{
		const ZBX_DC_HISTORY	*h = (ZBX_DC_HISTORY *)history->values[i];

		if (NULL == (item = zbx_hashset_search(&vc_ds.items, &h->itemid)))
		{
			item_local.itemid = h->itemid;
			item_local.value_type = h->value_type;
			zbx_history_record_vector_create(&item_local.data);

			item = zbx_hashset_insert(&vc_ds.items, &item_local, sizeof(item_local));
		}

		src.value = h->value;
		src.timestamp = h->ts;
		zbx_vcmock_ds_clone_record(&src, h->value_type, &dst);
		zbx_vector_history_record_append_ptr(&item->data, &dst);
		zbx_vector_history_record_sort(&item->data, history_compare);
	}

	return SUCCEED;
}

int	__wrap_zbx_history_sql_init(zbx_history_iface_t *hist, unsigned char value_type, char **error)
{
	ZBX_UNUSED(hist);
	ZBX_UNUSED(value_type);
	ZBX_UNUSED(error);

	return SUCCEED;
}

int	__wrap_zbx_history_elastic_init(zbx_history_iface_t *hist, unsigned char value_type, char **error)
{
	ZBX_UNUSED(hist);
	ZBX_UNUSED(value_type);
	ZBX_UNUSED(error);

	return SUCCEED;
}

/*
 * cache allocator size limit handling
 */

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_set_available_mem                                     *
 *                                                                            *
 * Purpose:  sets the available memory for the wrapped memory allocator       *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_set_available_mem(size_t size)
{
	vcmock_mem = size;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_mem_get_available                                     *
 *                                                                            *
 * Purpose:  retrieves the memory available in the wrapped memory allocator   *
 *                                                                            *
 ******************************************************************************/
size_t	zbx_vcmock_get_available_mem(void)
{
	return vcmock_mem;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_set_cache_size                                        *
 *                                                                            *
 * Purpose: sets the available size in value cache if the specified key is    *
 *          present in input data                                             *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_set_cache_size(zbx_mock_handle_t hitem, const char *key)
{
	const char		*data;
	zbx_mock_handle_t	hmem;
	zbx_uint64_t		cache_size;

	if (ZBX_MOCK_SUCCESS == zbx_mock_object_member(hitem, key, &hmem))
	{
		if (ZBX_MOCK_SUCCESS != zbx_mock_string(hmem, &data) || SUCCEED != is_uint64(data, &cache_size))
			fail_msg("Cannot read \"%s\" parameter", key);

		zbx_vcmock_set_available_mem(cache_size);
	}
}

/*
 * input data parsing utility functions
 */

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_get_request_params                                    *
 *                                                                            *
 * Purpose: gets value cache precache or requests parameters from input data  *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_get_request_params(zbx_mock_handle_t handle, zbx_uint64_t *itemid, unsigned char *value_type,
		int *seconds, int *count, zbx_timespec_t *end)
{
	if (FAIL == is_uint64(zbx_mock_get_object_member_string(handle, "itemid"), itemid))
		fail_msg("Invalid itemid value");

	*value_type = zbx_mock_str_to_value_type(zbx_mock_get_object_member_string(handle, "value type"));
	*seconds = atoi(zbx_mock_get_object_member_string(handle, "seconds"));
	*count = atoi(zbx_mock_get_object_member_string(handle, "count"));
	zbx_strtime_to_timespec(zbx_mock_get_object_member_string(handle, "end"), end);
}

/*
 * cache working mode handling
 */

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_set_mode                                              *
 *                                                                            *
 * Purpose: sets value cache mode if the specified key is present in input    *
 *          data                                                              *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_set_mode(zbx_mock_handle_t hitem, const char *key)
{
	const char		*data;
	zbx_mock_handle_t	hmode;
	zbx_mock_error_t	err;

	if (ZBX_MOCK_SUCCESS == zbx_mock_object_member(hitem, key, &hmode))
	{
		if (ZBX_MOCK_SUCCESS != (err = zbx_mock_string(hmode, &data)))
			fail_msg("Cannot read \"%s\" parameter: %s", key, zbx_mock_error_string(err));

		zbx_vc_set_mode(zbx_vcmock_str_to_cache_mode(data));
	}
}

/*
 * time() emulation
 */

time_t	__wrap_time(time_t *ptr)
{
	if (NULL != ptr)
		*ptr = vcmock_time;

	return vcmock_time;
}

/******************************************************************************
 *                                                                            *
 * Function: zbx_vcmock_set_time                                              *
 *                                                                            *
 * Purpose: sets the current time. The key must be present in input data or   *
 *          the test case will fail                                           *
 *                                                                            *
 ******************************************************************************/
void	zbx_vcmock_set_time(zbx_mock_handle_t hitem, const char *key)
{
	zbx_mock_error_t	err;
	const char		*data;
	zbx_timespec_t		ts;

	data = zbx_mock_get_object_member_string(hitem, key);

	if (ZBX_MOCK_SUCCESS != (err = zbx_strtime_to_timespec(data, &ts)))
		fail_msg("Cannot read \"%s\" parameter", key);

	vcmock_time = ts.sec;
}
