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
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "common.h"

#if defined(HAVE_LIBXML2) && defined(HAVE_LIBCURL)

#include "log.h"
#include "zbxjson.h"
#include "zbxalgo.h"
#include "checks_simple_vmware.h"
#include"../vmware/vmware.h"

#define ZBX_VMWARE_DATASTORE_SIZE_TOTAL		0
#define ZBX_VMWARE_DATASTORE_SIZE_FREE		1
#define ZBX_VMWARE_DATASTORE_SIZE_PFREE		2
#define ZBX_VMWARE_DATASTORE_SIZE_UNCOMMITTED	3

#define ZBX_DATASTORE_TOTAL			""
#define ZBX_DATASTORE_COUNTER_CAPACITY		0x01
#define ZBX_DATASTORE_COUNTER_USED		0x02
#define ZBX_DATASTORE_COUNTER_PROVISIONED	0x04

static int	vmware_set_powerstate_result(AGENT_RESULT *result)
{
	int	ret = SYSINFO_RET_OK;

	if (NULL != GET_STR_RESULT(result))
	{
		if (0 == strcmp(result->str, "poweredOff"))
			SET_UI64_RESULT(result, 0);
		else if (0 == strcmp(result->str, "poweredOn"))
			SET_UI64_RESULT(result, 1);
		else if (0 == strcmp(result->str, "suspended"))
			SET_UI64_RESULT(result, 2);
		else
			ret = SYSINFO_RET_FAIL;

		UNSET_STR_RESULT(result);
	}

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: hv_get                                                           *
 *                                                                            *
 * Purpose: return pointer to Hypervisor data from hashset with uuid          *
 *                                                                            *
 * Parameters: hvs  - [IN] the hashset with all Hypervisors                   *
 *             uuid - [IN] the uuid of Hypervisor                             *
 *                                                                            *
 * Return value: zbx_vmware_hv_t* - the operation has completed successfully  *
 *               NULL             - the operation has failed                  *
 *                                                                            *
 ******************************************************************************/
static zbx_vmware_hv_t	*hv_get(zbx_hashset_t *hvs, const char *uuid)
{
	zbx_vmware_hv_t	*hv, hv_local = {.uuid = (char *)uuid};

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() uuid:'%s'", __func__, uuid);

	hv = (zbx_vmware_hv_t *)zbx_hashset_search(hvs, &hv_local);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%p", __func__, (void *)hv);

	return hv;
}

/******************************************************************************
 *                                                                            *
 * Function: ds_get                                                           *
 *                                                                            *
 * Purpose: return pointer to Datastore data from vector with id              *
 *                                                                            *
 * Parameters: dss - [IN] the vector with all Datastores                      *
 *             id  - [IN] the id of Datastore                                 *
 *                                                                            *
 * Return value:                                                              *
 *        zbx_vmware_datastore_t* - the operation has completed successfully  *
 *        NULL                    - the operation has failed                  *
 *                                                                            *
 ******************************************************************************/
static zbx_vmware_datastore_t	*ds_get(const zbx_vector_vmware_datastore_t *dss, const char *name)
{
	int			i;
	zbx_vmware_datastore_t	ds_cmp;

	ds_cmp.name = (char *)name;

	if (FAIL == (i = zbx_vector_vmware_datastore_bsearch(dss, &ds_cmp, vmware_ds_name_compare)))
		return NULL;

	return dss->values[i];
}

static zbx_vmware_hv_t	*service_hv_get_by_vm_uuid(zbx_vmware_service_t *service, const char *uuid)
{
	zbx_vmware_vm_t		vm_local = {.uuid = (char *)uuid};
	zbx_vmware_vm_index_t	vmi_local = {&vm_local, NULL}, *vmi;
	zbx_vmware_hv_t		*hv = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() uuid:'%s'", __func__, uuid);

	if (NULL != (vmi = (zbx_vmware_vm_index_t *)zbx_hashset_search(&service->data->vms_index, &vmi_local)))
		hv = vmi->hv;
	else
		hv = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%p", __func__, (void *)hv);

	return hv;

}

static zbx_vmware_vm_t	*service_vm_get(zbx_vmware_service_t *service, const char *uuid)
{
	zbx_vmware_vm_t		vm_local = {.uuid = (char *)uuid}, *vm;
	zbx_vmware_vm_index_t	vmi_local = {&vm_local, NULL}, *vmi;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() uuid:'%s'", __func__, uuid);

	if (NULL != (vmi = (zbx_vmware_vm_index_t *)zbx_hashset_search(&service->data->vms_index, &vmi_local)))
		vm = vmi->vm;
	else
		vm = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%p", __func__, (void *)vm);

	return vm;
}

static zbx_vmware_cluster_t	*cluster_get(zbx_vector_ptr_t *clusters, const char *clusterid)
{
	int			i;
	zbx_vmware_cluster_t	*cluster;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() uuid:'%s'", __func__, clusterid);

	for (i = 0; i < clusters->values_num; i++)
	{
		cluster = (zbx_vmware_cluster_t *)clusters->values[i];

		if (0 == strcmp(cluster->id, clusterid))
			goto out;
	}

	cluster = NULL;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%p", __func__, (void *)cluster);

	return cluster;
}

static zbx_vmware_cluster_t	*cluster_get_by_name(zbx_vector_ptr_t *clusters, const char *name)
{
	int			i;
	zbx_vmware_cluster_t	*cluster;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() name:'%s'", __func__, name);

	for (i = 0; i < clusters->values_num; i++)
	{
		cluster = (zbx_vmware_cluster_t *)clusters->values[i];

		if (0 == strcmp(cluster->name, name))
			goto out;
	}

	cluster = NULL;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%p", __func__, (void *)cluster);

	return cluster;
}

/******************************************************************************
 *                                                                            *
 * Function: vmware_service_get_counter_value_by_id                           *
 *                                                                            *
 * Purpose: gets vmware performance counter value by its identifier           *
 *                                                                            *
 * Parameters: service   - [IN] the vmware service                            *
 *             type      - [IN] the performance entity type (HostSystem,      *
 *                              VirtualMachine, Datastore)                    *
 *             id        - [IN] the performance entity identifier             *
 *             counterid - [IN] the performance counter identifier            *
 *             instance  - [IN] the performance counter instance or "" for    *
 *                              aggregate data                                *
 *             coeff     - [IN] the coefficient to apply to the value         *
 *             result    - [OUT] the output result                            *
 *                                                                            *
 * Return value: SYSINFO_RET_OK, result has value - performance counter value *
 *                               was successfully retrieved                   *
 *               SYSINFO_RET_OK, result has no value - performance counter    *
 *                               was found without a value                    *
 *               SYSINFO_RET_FAIL - otherwise, error message is set in result *
 *                                                                            *
 * Comments: There can be situation when performance counter is configured    *
 *           to be read but the collector has not yet processed it. In this   *
 *           case return SYSINFO_RET_OK with empty result so that it is       *
 *           ignored by server rather than generating error.                  *
 *                                                                            *
 ******************************************************************************/
static int	vmware_service_get_counter_value_by_id(zbx_vmware_service_t *service, const char *type, const char *id,
		zbx_uint64_t counterid, const char *instance, int coeff, AGENT_RESULT *result)
{
	zbx_vmware_perf_entity_t	*entity;
	zbx_vmware_perf_counter_t	*perfcounter;
	zbx_str_uint64_pair_t		*perfvalue;
	int				i, ret = SYSINFO_RET_FAIL;
	zbx_uint64_t			value;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() type:%s id:%s counterid:" ZBX_FS_UI64 " instance:%s", __func__,
			type, id, counterid, instance);

	if (NULL == (entity = zbx_vmware_service_get_perf_entity(service, type, id)))
	{
		/* the requested counter has not been queried yet */
		zabbix_log(LOG_LEVEL_DEBUG, "performance data is not yet ready, ignoring request");
		ret = SYSINFO_RET_OK;
		goto out;
	}

	if (NULL != entity->error)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, entity->error));
		goto out;
	}

	if (FAIL == (i = zbx_vector_ptr_bsearch(&entity->counters, &counterid, ZBX_DEFAULT_UINT64_PTR_COMPARE_FUNC)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Performance counter data was not found."));
		goto out;
	}

	perfcounter = (zbx_vmware_perf_counter_t *)entity->counters.values[i];

	if (0 == (perfcounter->state & ZBX_VMWARE_COUNTER_READY))
	{
		ret = SYSINFO_RET_OK;
		goto out;
	}

	if (0 == perfcounter->values.values_num)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Performance counter data is not available."));
		goto out;
	}

	for (i = 0; i < perfcounter->values.values_num; i++)
	{
		perfvalue = &perfcounter->values.values[i];

		if (0 == strcmp(perfvalue->name, instance))
			break;
	}

	if (i == perfcounter->values.values_num)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Performance counter instance was not found."));
		goto out;
	}

	/* VMware returns -1 value if the performance data for the specified period is not ready - ignore it */
	if (ZBX_MAX_UINT64 == perfvalue->value)
	{
		ret = SYSINFO_RET_OK;
		goto out;
	}

	value = perfvalue->value * coeff;
	SET_UI64_RESULT(result, value);
	ret = SYSINFO_RET_OK;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: vmware_service_get_counter_value_by_path                         *
 *                                                                            *
 * Purpose: gets vmware performance counter value by the path                 *
 *                                                                            *
 * Parameters: service  - [IN] the vmware service                             *
 *             type     - [IN] the performance entity type (HostSystem,       *
 *                             VirtualMachine, Datastore)                     *
 *             id       - [IN] the performance entity identifier              *
 *             path     - [IN] the performance counter path                   *
 *                             (<group>/<key>[<rollup type>])                 *
 *             instance - [IN] the performance counter instance or "" for     *
 *                             aggregate data                                 *
 *             coeff    - [IN] the coefficient to apply to the value          *
 *             result   - [OUT] the output result                             *
 *                                                                            *
 * Return value: SYSINFO_RET_OK, result has value - performance counter value *
 *                               was successfully retrieved                   *
 *               SYSINFO_RET_OK, result has no value - performance counter    *
 *                               was found without a value                    *
 *               SYSINFO_RET_FAIL - otherwise, error message is set in result *
 *                                                                            *
 * Comments: There can be situation when performance counter is configured    *
 *           to be read but the collector has not yet processed it. In this   *
 *           case return SYSINFO_RET_OK with empty result so that it is       *
 *           ignored by server rather than generating error.                  *
 *                                                                            *
 ******************************************************************************/
static int	vmware_service_get_counter_value_by_path(zbx_vmware_service_t *service, const char *type,
		const char *id, const char *path, const char *instance, int coeff, AGENT_RESULT *result)
{
	zbx_uint64_t	counterid;

	if (FAIL == zbx_vmware_service_get_counterid(service, path, &counterid))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Performance counter is not available."));
		return SYSINFO_RET_FAIL;
	}

	return vmware_service_get_counter_value_by_id(service, type, id, counterid, instance, coeff, result);
}

static int	vmware_service_get_vm_counter(zbx_vmware_service_t *service, const char *uuid, const char *instance,
		const char *path, int coeff, AGENT_RESULT *result)
{
	zbx_vmware_vm_t	*vm;
	int		ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() uuid:%s instance:%s path:%s", __func__, uuid, instance, path);

	if (NULL == (vm = service_vm_get(service, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown virtual machine uuid."));
		goto out;
	}

	ret = vmware_service_get_counter_value_by_path(service, "VirtualMachine", vm->id, path, instance, coeff,
			result);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: get_vmware_service                                               *
 *                                                                            *
 * Purpose: gets vmware service object                                        *
 *                                                                            *
 * Parameters: url       - [IN] the vmware service URL                        *
 *             username  - [IN] the vmware service username                   *
 *             password  - [IN] the vmware service password                   *
 *             ret       - [OUT] the operation result code                    *
 *                                                                            *
 * Return value: The vmware service object or NULL if the service was not     *
 *               found, did not have data or any error occurred. In the last  *
 *               case the error message will be stored in agent result.       *
 *                                                                            *
 * Comments: There are three possible cases:                                  *
 *             1) the vmware service is not ready. This can happen when       *
 *                service was added, but not yet processed by collector.      *
 *                In this case NULL is returned and result code is set to     *
 *                SYSINFO_RET_OK.                                             *
 *             2) the vmware service update failed. This can happen if there  *
 *                was a network problem, authentication failure or any error  *
 *                that prevented from obtaining and parsing vmware data.      *
 *                In this case NULL is returned and result code is set to     *
 *                SYSINFO_RET_FAIL.                                           *
 *             3) the vmware service has been updated successfully.           *
 *                In this case the service object is returned and result code *
 *                is not set.                                                 *
 *                                                                            *
 ******************************************************************************/
static zbx_vmware_service_t	*get_vmware_service(const char *url, const char *username, const char *password,
		AGENT_RESULT *result, int *ret)
{
	zbx_vmware_service_t	*service;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() '%s'@'%s'", __func__, username, url);

	if (NULL == (service = zbx_vmware_get_service(url, username, password)))
	{
		*ret = SYSINFO_RET_OK;
		goto out;
	}

	if (0 != (service->state & ZBX_VMWARE_STATE_FAILED))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, NULL != service->data->error ? service->data->error :
				"Unknown VMware service error."));

		zabbix_log(LOG_LEVEL_DEBUG, "failed to query VMware service: %s",
				NULL != service->data->error ? service->data->error : "unknown error");

		*ret = SYSINFO_RET_FAIL;
		service = NULL;
	}
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%p", __func__, (void *)service);

	return service;
}

/******************************************************************************
 *                                                                            *
 * Function: get_vcenter_vmprop                                               *
 *                                                                            *
 * Purpose: retrieves data from virtual machine details                       *
 *                                                                            *
 * Parameters: request   - [IN] the original request. The first parameter is  *
 *                              vmware service URL and the second parameter   *
 *                              is virtual machine uuid.                      *
 *             username  - [IN] the vmware service user name                  *
 *             password  - [IN] the vmware service password                   *
 *             xpath     - [IN] the xpath describing data to retrieve         *
 *             result    - [OUT] the request result                           *
 *                                                                            *
 ******************************************************************************/
static int	get_vcenter_vmprop(AGENT_REQUEST *request, const char *username, const char *password,
		int propid, AGENT_RESULT *result)
{
	zbx_vmware_service_t	*service;
	zbx_vmware_vm_t		*vm = NULL;
	char			*url, *uuid, *value;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() propid:%d", __func__, propid);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (vm = service_vm_get(service, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown virtual machine uuid."));
		goto unlock;
	}

	if (NULL == (value = vm->props[propid]))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Value is not available."));
		goto unlock;
	}

	SET_STR_RESULT(result, zbx_strdup(NULL, value));

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: get_vcenter_hvprop                                               *
 *                                                                            *
 * Purpose: retrieves hypervisor property                                     *
 *                                                                            *
 * Parameters: request   - [IN] the original request. The first parameter is  *
 *                              vmware service URL and the second parameter   *
 *                              is hypervisor uuid.                           *
 *             username  - [IN] the vmware service user name                  *
 *             password  - [IN] the vmware service password                   *
 *             propid    - [IN] the property id                               *
 *             result    - [OUT] the request result                           *
 *                                                                            *
 ******************************************************************************/
static int	get_vcenter_hvprop(AGENT_REQUEST *request, const char *username, const char *password, int propid,
		AGENT_RESULT *result)
{
	zbx_vmware_service_t	*service;
	const char		*uuid, *url, *value;
	zbx_vmware_hv_t		*hv;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() propid:%d", __func__, propid);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = hv_get(&service->data->hvs, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
		goto unlock;
	}

	if (NULL == (value = hv->props[propid]))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Value is not available."));
		goto unlock;
	}

	SET_STR_RESULT(result, zbx_strdup(NULL, value));
	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_cluster_discovery(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	struct zbx_json		json_data;
	char			*url;
	zbx_vmware_service_t	*service;
	int			i, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (1 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	zbx_json_initarray(&json_data, ZBX_JSON_STAT_BUF_LEN);

	for (i = 0; i < service->data->clusters.values_num; i++)
	{
		zbx_vmware_cluster_t	*cluster = (zbx_vmware_cluster_t *)service->data->clusters.values[i];

		zbx_json_addobject(&json_data, NULL);
		zbx_json_addstring(&json_data, "{#CLUSTER.ID}", cluster->id, ZBX_JSON_TYPE_STRING);
		zbx_json_addstring(&json_data, "{#CLUSTER.NAME}", cluster->name, ZBX_JSON_TYPE_STRING);
		zbx_json_close(&json_data);
	}

	zbx_json_close(&json_data);

	SET_STR_RESULT(result, zbx_strdup(NULL, json_data.buffer));

	zbx_json_free(&json_data);

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_cluster_status(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *name;
	zbx_vmware_service_t	*service;
	zbx_vmware_cluster_t	*cluster;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	name = get_rparam(request, 1);

	if ('\0' == *name)
		goto out;

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (cluster = cluster_get_by_name(&service->data->clusters, name)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown cluster name."));
		goto unlock;
	}

	if (NULL == cluster->status)
		goto unlock;

	ret = SYSINFO_RET_OK;

	if (0 == strcmp(cluster->status, "gray"))
		SET_UI64_RESULT(result, 0);
	else if (0 == strcmp(cluster->status, "green"))
		SET_UI64_RESULT(result, 1);
	else if (0 == strcmp(cluster->status, "yellow"))
		SET_UI64_RESULT(result, 2);
	else if (0 == strcmp(cluster->status, "red"))
		SET_UI64_RESULT(result, 3);
	else
		ret = SYSINFO_RET_FAIL;

unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

static void	vmware_get_events(const zbx_vector_ptr_t *events, zbx_uint64_t eventlog_last_key, const DC_ITEM *item,
		zbx_vector_ptr_t *add_results)
{
	int	i;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() eventlog_last_key:" ZBX_FS_UI64, __func__, eventlog_last_key);

	/* events were retrieved in reverse chronological order */
	for (i = events->values_num - 1; i >= 0; i--)
	{
		const zbx_vmware_event_t	*event = (zbx_vmware_event_t *)events->values[i];
		AGENT_RESULT			*add_result = NULL;

		if (event->key <= eventlog_last_key)
			continue;

		add_result = (AGENT_RESULT *)zbx_malloc(add_result, sizeof(AGENT_RESULT));
		init_result(add_result);

		if (SUCCEED == set_result_type(add_result, item->value_type, event->message))
		{
			set_result_meta(add_result, event->key, 0);

			if (ITEM_VALUE_TYPE_LOG == item->value_type)
			{
				add_result->log->logeventid = event->key;
				add_result->log->timestamp = event->timestamp;
			}

			zbx_vector_ptr_append(add_results, add_result);
		}
		else
			zbx_free(add_result);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s(): events:%d", __func__, add_results->values_num);
}

int	check_vcenter_eventlog(AGENT_REQUEST *request, const DC_ITEM *item, AGENT_RESULT *result,
		zbx_vector_ptr_t *add_results)
{
	const char		*url, *skip;
	unsigned char		skip_old;
	zbx_vmware_service_t	*service;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 < request->nparam || 0 == request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);

	if (NULL == (skip = get_rparam(request, 1)) || '\0' == *skip || 0 == strcmp(skip, "all"))
	{
		skip_old = 0;
	}
	else if (0 == strcmp(skip, "skip"))
	{
		skip_old = 1;
	}
	else
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, item->username, item->password, result, &ret)))
		goto unlock;

	if (ZBX_VMWARE_EVENT_KEY_UNINITIALIZED == service->eventlog.last_key)
	{
		service->eventlog.last_key = request->lastlogsize;
		service->eventlog.skip_old = skip_old;
	}
	else if (request->lastlogsize < service->eventlog.last_key)
	{
		/* this may happen if there are multiple vmware.eventlog items for the same service URL or item has  */
		/* been polled, but values got stuck in history cache and item's lastlogsize hasn't been updated yet */
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Too old events requested."));
		goto unlock;
	}
	else if (0 < service->data->events.values_num)
	{
		vmware_get_events(&service->data->events, request->lastlogsize, item, add_results);
		service->eventlog.last_key = ((const zbx_vmware_event_t *)service->data->events.values[0])->key;
	}

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_version(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url;
	zbx_vmware_service_t	*service;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (1 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == service->version)
		goto unlock;

	SET_STR_RESULT(result, zbx_strdup(NULL, service->version));

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_fullname(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url;
	zbx_vmware_service_t	*service;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (1 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == service->fullname)
		goto unlock;

	SET_STR_RESULT(result, zbx_strdup(NULL, service->fullname));

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_cluster_name(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *uuid;
	zbx_vmware_hv_t		*hv;
	zbx_vmware_service_t	*service;
	zbx_vmware_cluster_t	*cluster = NULL;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = hv_get(&service->data->hvs, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
		goto unlock;
	}

	if (NULL != hv->clusterid)
		cluster = cluster_get(&service->data->clusters, hv->clusterid);

	SET_STR_RESULT(result, zbx_strdup(NULL, NULL != cluster ? cluster->name : ""));

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_cpu_usage(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_OVERALL_CPU_USAGE, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * 1000000;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_discovery(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	struct zbx_json		json_data;
	char			*url, *name;
	zbx_vmware_service_t	*service;
	int			ret = SYSINFO_RET_FAIL;
	zbx_vmware_hv_t		*hv;
	zbx_hashset_iter_t	iter;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (1 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	zbx_json_initarray(&json_data, ZBX_JSON_STAT_BUF_LEN);

	zbx_hashset_iter_reset(&service->data->hvs, &iter);
	while (NULL != (hv = (zbx_vmware_hv_t *)zbx_hashset_iter_next(&iter)))
	{
		zbx_vmware_cluster_t	*cluster = NULL;

		if (NULL == (name = hv->props[ZBX_VMWARE_HVPROP_NAME]))
			continue;

		if (NULL != hv->clusterid)
			cluster = cluster_get(&service->data->clusters, hv->clusterid);

		zbx_json_addobject(&json_data, NULL);
		zbx_json_addstring(&json_data, "{#HV.UUID}", hv->uuid, ZBX_JSON_TYPE_STRING);
		zbx_json_addstring(&json_data, "{#HV.ID}", hv->id, ZBX_JSON_TYPE_STRING);
		zbx_json_addstring(&json_data, "{#HV.NAME}", name, ZBX_JSON_TYPE_STRING);
		zbx_json_addstring(&json_data, "{#DATACENTER.NAME}", hv->datacenter_name, ZBX_JSON_TYPE_STRING);
		zbx_json_addstring(&json_data, "{#CLUSTER.NAME}",
				NULL != cluster ? cluster->name : "", ZBX_JSON_TYPE_STRING);
		zbx_json_addstring(&json_data, "{#PARENT.NAME}", hv->parent_name, ZBX_JSON_TYPE_STRING);
		zbx_json_addstring(&json_data, "{#PARENT.TYPE}", hv->parent_type, ZBX_JSON_TYPE_STRING);
		zbx_json_close(&json_data);
	}

	zbx_json_close(&json_data);

	SET_STR_RESULT(result, zbx_strdup(NULL, json_data.buffer));

	zbx_json_free(&json_data);

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_fullname(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_FULL_NAME, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_hw_cpu_num(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_HW_NUM_CPU_CORES, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_hw_cpu_freq(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_HW_CPU_MHZ, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * 1000000;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_hw_cpu_model(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_HW_CPU_MODEL, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_hw_cpu_threads(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_HW_NUM_CPU_THREADS, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_hw_memory(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_HW_MEMORY_SIZE, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_hw_model(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_HW_MODEL, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_hw_uuid(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_HW_UUID, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_hw_vendor(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_HW_VENDOR, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_memory_size_ballooned(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int			i, ret = SYSINFO_RET_FAIL;
	zbx_vmware_service_t	*service;
	const char		*uuid, *url;
	zbx_vmware_hv_t		*hv;
	zbx_uint64_t		value = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = hv_get(&service->data->hvs, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
		goto unlock;
	}

	for (i = 0; i < hv->vms.values_num; i++)
	{
		zbx_uint64_t	mem;
		const char	*value_str;
		zbx_vmware_vm_t	*vm = (zbx_vmware_vm_t *)hv->vms.values[i];

		if (NULL == (value_str = vm->props[ZBX_VMWARE_VMPROP_MEMORY_SIZE_BALLOONED]))
			continue;

		if (SUCCEED != is_uint64(value_str, &mem))
			continue;

		value += mem;
	}

	value *= ZBX_MEBIBYTE;
	SET_UI64_RESULT(result, value);

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_memory_used(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_MEMORY_USED, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * ZBX_MEBIBYTE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_sensor_health_state(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_HEALTH_STATE, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_STR_RESULT(result))
	{
		if (0 == strcmp(result->str, "gray") || 0 == strcmp(result->str, "unknown"))
			SET_UI64_RESULT(result, 0);
		else if (0 == strcmp(result->str, "green"))
			SET_UI64_RESULT(result, 1);
		else if (0 == strcmp(result->str, "yellow"))
			SET_UI64_RESULT(result, 2);
		else if (0 == strcmp(result->str, "red"))
			SET_UI64_RESULT(result, 3);
		else
			ret = SYSINFO_RET_FAIL;

		UNSET_STR_RESULT(result);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_status(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_STATUS, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_STR_RESULT(result))
	{
		if (0 == strcmp(result->str, "gray") || 0 == strcmp(result->str, "unknown"))
			SET_UI64_RESULT(result, 0);
		else if (0 == strcmp(result->str, "green"))
			SET_UI64_RESULT(result, 1);
		else if (0 == strcmp(result->str, "yellow"))
			SET_UI64_RESULT(result, 2);
		else if (0 == strcmp(result->str, "red"))
			SET_UI64_RESULT(result, 3);
		else
			ret = SYSINFO_RET_FAIL;

		UNSET_STR_RESULT(result);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_uptime(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_UPTIME, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_version(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_hvprop(request, username, password, ZBX_VMWARE_HVPROP_VERSION, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_vm_num(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int			ret = SYSINFO_RET_FAIL;
	zbx_vmware_service_t	*service;
	const char		*uuid, *url;
	zbx_vmware_hv_t		*hv;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = hv_get(&service->data->hvs, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
		goto unlock;
	}

	SET_UI64_RESULT(result, hv->vms.values_num);
	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_network_in(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *mode, *uuid;
	zbx_vmware_service_t	*service;
	zbx_vmware_hv_t		*hv;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 > request->nparam || request->nparam > 3)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);
	mode = get_rparam(request, 2);

	if (NULL != mode && '\0' != *mode && 0 != strcmp(mode, "bps"))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid third parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = hv_get(&service->data->hvs, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
		goto unlock;
	}

	ret = vmware_service_get_counter_value_by_path(service, "HostSystem", hv->id, "net/received[average]", "",
			ZBX_KIBIBYTE, result);
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_network_out(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *mode, *uuid;
	zbx_vmware_service_t	*service;
	zbx_vmware_hv_t		*hv;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 > request->nparam || request->nparam > 3)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);
	mode = get_rparam(request, 2);

	if (NULL != mode && '\0' != *mode && 0 != strcmp(mode, "bps"))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid third parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = hv_get(&service->data->hvs, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
		goto unlock;
	}

	ret = vmware_service_get_counter_value_by_path(service, "HostSystem", hv->id, "net/transmitted[average]", "",
			ZBX_KIBIBYTE, result);
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_datacenter_name(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *uuid;
	zbx_vmware_service_t	*service;
	zbx_vmware_hv_t		*hv;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = hv_get(&service->data->hvs, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
		goto unlock;
	}

	SET_STR_RESULT(result, zbx_strdup(NULL, hv->datacenter_name));

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_datastore_discovery(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *uuid;
	zbx_vmware_service_t	*service;
	zbx_vmware_hv_t		*hv;
	struct zbx_json		json_data;
	int			i, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = hv_get(&service->data->hvs, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
		goto unlock;
	}

	zbx_json_initarray(&json_data, ZBX_JSON_STAT_BUF_LEN);

	for (i = 0; i < hv->ds_names.values_num; i++)
	{
		zbx_json_addobject(&json_data, NULL);
		zbx_json_addstring(&json_data, "{#DATASTORE}", hv->ds_names.values[i], ZBX_JSON_TYPE_STRING);
		zbx_json_close(&json_data);
	}

	zbx_json_close(&json_data);

	SET_STR_RESULT(result, zbx_strdup(NULL, json_data.buffer));

	zbx_json_free(&json_data);

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

static int	check_vcenter_hv_datastore_latency(AGENT_REQUEST *request, const char *username, const char *password,
		const char *perfcounter, AGENT_RESULT *result)
{
	char			*url, *mode, *uuid, *name;
	zbx_vmware_service_t	*service;
	zbx_vmware_hv_t		*hv;
	zbx_vmware_datastore_t	*datastore;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() perfcounter:%s", __func__, perfcounter);

	if (3 > request->nparam || request->nparam > 4)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);
	name = get_rparam(request, 2);
	mode = get_rparam(request, 3);

	if (NULL != mode && '\0' != *mode && 0 != strcmp(mode, "latency"))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid fourth parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = hv_get(&service->data->hvs, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
		goto unlock;
	}

	datastore = ds_get(&service->data->datastores, name);

	if (NULL == datastore)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown datastore name."));
		goto unlock;
	}

	if (FAIL == zbx_vector_str_bsearch(&hv->ds_names, datastore->name, ZBX_DEFAULT_STR_COMPARE_FUNC))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Datastore \"%s\" not found on this hypervisor.",
				datastore->name));
		goto unlock;
	}

	if (NULL == datastore->uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown datastore uuid."));
		goto unlock;
	}

	ret = vmware_service_get_counter_value_by_path(service, "HostSystem", hv->id, perfcounter, datastore->uuid, 1,
			result);
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_datastore_read(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	return check_vcenter_hv_datastore_latency(request, username, password, "datastore/totalReadLatency[average]",
			result);
}

int	check_vcenter_hv_datastore_write(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	return check_vcenter_hv_datastore_latency(request, username, password, "datastore/totalWriteLatency[average]",
			result);
}

static int	check_vcenter_hv_datastore_size_vsphere(int mode, const zbx_vmware_datastore_t *datastore,
		AGENT_RESULT *result)
{
	switch (mode)
	{
		case ZBX_VMWARE_DATASTORE_SIZE_TOTAL:
			if (ZBX_MAX_UINT64 == datastore->capacity)
			{
				SET_MSG_RESULT(result, zbx_strdup(NULL, "Datastore \"capacity\" is not available."));
				return SYSINFO_RET_FAIL;
			}
			SET_UI64_RESULT(result, datastore->capacity);
			break;
		case ZBX_VMWARE_DATASTORE_SIZE_FREE:
			if (ZBX_MAX_UINT64 == datastore->free_space)
			{
				SET_MSG_RESULT(result, zbx_strdup(NULL, "Datastore \"free space\" is not available."));
				return SYSINFO_RET_FAIL;
			}
			SET_UI64_RESULT(result, datastore->free_space);
			break;
		case ZBX_VMWARE_DATASTORE_SIZE_UNCOMMITTED:
			if (ZBX_MAX_UINT64 == datastore->uncommitted)
			{
				SET_MSG_RESULT(result, zbx_strdup(NULL, "Datastore \"uncommitted\" is not available."));
				return SYSINFO_RET_FAIL;
			}
			SET_UI64_RESULT(result, datastore->uncommitted);
			break;
		case ZBX_VMWARE_DATASTORE_SIZE_PFREE:
			if (ZBX_MAX_UINT64 == datastore->capacity)
			{
				SET_MSG_RESULT(result, zbx_strdup(NULL, "Datastore \"capacity\" is not available."));
				return SYSINFO_RET_FAIL;
			}
			if (ZBX_MAX_UINT64 == datastore->free_space)
			{
				SET_MSG_RESULT(result, zbx_strdup(NULL, "Datastore \"free space\" is not available."));
				return SYSINFO_RET_FAIL;
			}
			if (0 == datastore->capacity)
			{
				SET_MSG_RESULT(result, zbx_strdup(NULL, "Datastore \"capacity\" is zero."));
				return SYSINFO_RET_FAIL;
			}
			SET_DBL_RESULT(result, (double)datastore->free_space / datastore->capacity * 100);
			break;
	}

	return SYSINFO_RET_OK;
}

static int	check_vcenter_ds_param(const char *param, int *mode)
{

	if (NULL == param || '\0' == *param || 0 == strcmp(param, "total"))
	{
		*mode = ZBX_VMWARE_DATASTORE_SIZE_TOTAL;
	}
	else if (0 == strcmp(param, "free"))
	{
		*mode = ZBX_VMWARE_DATASTORE_SIZE_FREE;
	}
	else if (0 == strcmp(param, "pfree"))
	{
		*mode = ZBX_VMWARE_DATASTORE_SIZE_PFREE;
	}
	else if (0 == strcmp(param, "uncommitted"))
	{
		*mode = ZBX_VMWARE_DATASTORE_SIZE_UNCOMMITTED;
	}
	else
		return FAIL;

	return SUCCEED;
}

static int	check_vcenter_ds_size(const char *url, const char *hv_uuid, const char *name, const int mode,
		const char *username, const char *password, AGENT_RESULT *result)
{
	zbx_vmware_service_t	*service;
	int			ret = SYSINFO_RET_FAIL;
	zbx_vmware_datastore_t	*datastore = NULL;
	zbx_uint64_t		disk_used, disk_provisioned, disk_capacity;
	unsigned int		flags;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	datastore = ds_get(&service->data->datastores, name);

	if (NULL == datastore)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown datastore name."));
		goto unlock;
	}

	if (NULL != hv_uuid &&
			FAIL == zbx_vector_str_bsearch(&datastore->hv_uuids, hv_uuid, ZBX_DEFAULT_STR_COMPARE_FUNC))
	{
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Hypervisor '%s' not found on this datastore.", hv_uuid));
		goto unlock;
	}

	if (ZBX_VMWARE_TYPE_VSPHERE == service->type)
	{
		ret = check_vcenter_hv_datastore_size_vsphere(mode, datastore, result);
		goto unlock;
	}

	switch (mode)
	{
		case ZBX_VMWARE_DATASTORE_SIZE_TOTAL:
			flags = ZBX_DATASTORE_COUNTER_CAPACITY;
			break;
		case ZBX_VMWARE_DATASTORE_SIZE_FREE:
			flags = ZBX_DATASTORE_COUNTER_CAPACITY | ZBX_DATASTORE_COUNTER_USED;
			break;
		case ZBX_VMWARE_DATASTORE_SIZE_PFREE:
			flags = ZBX_DATASTORE_COUNTER_CAPACITY | ZBX_DATASTORE_COUNTER_USED;
			break;
		case ZBX_VMWARE_DATASTORE_SIZE_UNCOMMITTED:
			flags = ZBX_DATASTORE_COUNTER_PROVISIONED | ZBX_DATASTORE_COUNTER_USED;
			break;
	}

	if (0 != (flags & ZBX_DATASTORE_COUNTER_PROVISIONED))
	{
		ret = vmware_service_get_counter_value_by_path(service, "Datastore", datastore->id,
				"disk/provisioned[latest]", ZBX_DATASTORE_TOTAL, ZBX_KIBIBYTE, result);

		if (SYSINFO_RET_OK != ret || NULL == GET_UI64_RESULT(result))
			goto unlock;

		disk_provisioned = *GET_UI64_RESULT(result);
		UNSET_UI64_RESULT(result);
	}

	if (0 != (flags & ZBX_DATASTORE_COUNTER_USED))
	{
		ret = vmware_service_get_counter_value_by_path(service, "Datastore", datastore->id,
				"disk/used[latest]", ZBX_DATASTORE_TOTAL, ZBX_KIBIBYTE, result);

		if (SYSINFO_RET_OK != ret || NULL == GET_UI64_RESULT(result))
			goto unlock;

		disk_used = *GET_UI64_RESULT(result);
		UNSET_UI64_RESULT(result);
	}

	if (0 != (flags & ZBX_DATASTORE_COUNTER_CAPACITY))
	{
		ret = vmware_service_get_counter_value_by_path(service, "Datastore", datastore->id,
				"disk/capacity[latest]", ZBX_DATASTORE_TOTAL, ZBX_KIBIBYTE, result);

		if (SYSINFO_RET_OK != ret || NULL == GET_UI64_RESULT(result))
			goto unlock;

		disk_capacity = *GET_UI64_RESULT(result);
		UNSET_UI64_RESULT(result);
	}

	switch (mode)
	{
		case ZBX_VMWARE_DATASTORE_SIZE_TOTAL:
			SET_UI64_RESULT(result, disk_capacity);
			break;
		case ZBX_VMWARE_DATASTORE_SIZE_FREE:
			SET_UI64_RESULT(result, disk_capacity - disk_used);
			break;
		case ZBX_VMWARE_DATASTORE_SIZE_UNCOMMITTED:
			SET_UI64_RESULT(result, disk_provisioned - disk_used);
			break;
		case ZBX_VMWARE_DATASTORE_SIZE_PFREE:
			SET_DBL_RESULT(result, 0 != disk_capacity ?
					(double) (disk_capacity - disk_used) / disk_capacity * 100 : 0);
			break;
	}

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_datastore_size(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char	*url, *uuid, *name, *param;
	int	ret = SYSINFO_RET_FAIL, mode;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (3 > request->nparam || request->nparam > 4)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);
	name = get_rparam(request, 2);
	param = get_rparam(request, 3);

	if (SUCCEED == check_vcenter_ds_param(param, &mode))
		ret = check_vcenter_ds_size(url, uuid, name, mode, username, password, result);
	else
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid fourth parameter."));
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_perfcounter(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *uuid, *path;
	const char 		*instance;
	zbx_vmware_service_t	*service;
	zbx_vmware_hv_t		*hv;
	zbx_uint64_t		counterid;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (3 > request->nparam || request->nparam > 4)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);
	path = get_rparam(request, 2);
	instance = get_rparam(request, 3);

	if (NULL == instance)
		instance = "";

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = hv_get(&service->data->hvs, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
		goto unlock;
	}

	if (FAIL == zbx_vmware_service_get_counterid(service, path, &counterid))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Performance counter is not available."));
		goto unlock;
	}

	/* FAIL is returned if counter already exists */
	if (SUCCEED == zbx_vmware_service_add_perf_counter(service, "HostSystem", hv->id, counterid, "*"))
	{
		ret = SYSINFO_RET_OK;
		goto unlock;
	}

	/* the performance counter is already being monitored, try to get the results from statistics */
	ret = vmware_service_get_counter_value_by_id(service, "HostSystem", hv->id, counterid, instance, 1, result);
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_hv_datastore_list(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *hv_uuid, *ds_list = NULL;
	zbx_vmware_service_t	*service;
	zbx_vmware_hv_t		*hv;
	int			i, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam )
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	hv_uuid = get_rparam(request, 1);
	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = hv_get(&service->data->hvs, hv_uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
		goto unlock;
	}

	for (i = 0; i < hv->ds_names.values_num; i++)
	{
		ds_list = zbx_strdcatf(ds_list, "%s\n", hv->ds_names.values[i]);
	}

	if (NULL != ds_list)
		ds_list[strlen(ds_list)-1] = '\0';
	else
		ds_list = zbx_strdup(NULL, "");

	SET_TEXT_RESULT(result, ds_list);

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_datastore_hv_list(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *ds_name, *hv_list = NULL, *hv_name;
	zbx_vmware_service_t	*service;
	int			i, ret = SYSINFO_RET_FAIL;
	zbx_vmware_datastore_t	*datastore = NULL;
	zbx_vmware_hv_t		*hv;


	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam )
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	ds_name = get_rparam(request, 1);
	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	for (i = 0; i < service->data->datastores.values_num; i++)
	{
		if (0 != strcmp(ds_name, service->data->datastores.values[i]->name))
			continue;

		datastore = service->data->datastores.values[i];
		break;
	}

	if (NULL == datastore)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown datastore name."));
		goto unlock;
	}

	for (i=0; i < datastore->hv_uuids.values_num; i++)
	{
		if (NULL == (hv = hv_get(&service->data->hvs, datastore->hv_uuids.values[i])))
		{
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
			zbx_free(hv_list);
			goto unlock;
		}

		if (NULL == (hv_name = hv->props[ZBX_VMWARE_HVPROP_NAME]))
			hv_name = datastore->hv_uuids.values[i];

		hv_list = zbx_strdcatf(hv_list, "%s\n", hv_name);
	}

	if (NULL != hv_list)
		hv_list[strlen(hv_list)-1] = '\0';
	else
		hv_list = zbx_strdup(NULL, "");

	SET_TEXT_RESULT(result, hv_list);

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_datastore_size(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char	*url, *name, *param;
	int	ret = SYSINFO_RET_FAIL, mode;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 > request->nparam || request->nparam > 3)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	name = get_rparam(request, 1);
	param = get_rparam(request, 2);

	if (SUCCEED == check_vcenter_ds_param(param, &mode))
		ret = check_vcenter_ds_size(url, NULL, name, mode, username, password, result);
	else
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid third parameter."));
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_datastore_discovery(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url;
	zbx_vmware_service_t	*service;
	struct zbx_json		json_data;
	int			i, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (1 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	zbx_json_init(&json_data, ZBX_JSON_STAT_BUF_LEN);
	zbx_json_addarray(&json_data, ZBX_PROTO_TAG_DATA);

	for (i = 0; i < service->data->datastores.values_num; i++)
	{
		zbx_vmware_datastore_t	*datastore = service->data->datastores.values[i];
		zbx_json_addobject(&json_data, NULL);
		zbx_json_addstring(&json_data, "{#DATASTORE}", datastore->name, ZBX_JSON_TYPE_STRING);
		zbx_json_close(&json_data);
	}

	zbx_json_close(&json_data);

	SET_STR_RESULT(result, zbx_strdup(NULL, json_data.buffer));

	zbx_json_free(&json_data);

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

static int	check_vcenter_datastore_latency(AGENT_REQUEST *request, const char *username, const char *password,
		const char *perfcounter, AGENT_RESULT *result)
{
	char			*url, *mode, *name;
	zbx_vmware_service_t	*service;
	zbx_vmware_hv_t		*hv;
	zbx_vmware_datastore_t	*datastore;
	int			i, ret = SYSINFO_RET_FAIL, count = 0;
	zbx_uint64_t		latency = 0, counterid;
	unsigned char		is_maxlatency = 0;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() perfcounter:%s", __func__, perfcounter);

	if (2 > request->nparam || request->nparam > 3)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	name = get_rparam(request, 1);
	mode = get_rparam(request, 2);

	if (NULL != mode && '\0' != *mode && 0 != strcmp(mode, "latency") && 0 != strcmp(mode, "maxlatency"))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid third parameter."));
		goto out;
	}

	if (NULL != mode && 0 == strcmp(mode, "maxlatency"))
		is_maxlatency = 1;

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	datastore = ds_get(&service->data->datastores, name);

	if (NULL == datastore)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown datastore name."));
		goto unlock;
	}

	if (NULL == datastore->uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown datastore uuid."));
		goto unlock;
	}

	if (FAIL == zbx_vmware_service_get_counterid(service, perfcounter, &counterid))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Performance counter is not available."));
		goto unlock;
	}

	for (i = 0; i < datastore->hv_uuids.values_num; i++)
	{
		if (NULL == (hv = hv_get(&service->data->hvs, datastore->hv_uuids.values[i])))
		{
			SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown hypervisor uuid."));
			goto unlock;
		}

		if (SYSINFO_RET_OK != (ret = vmware_service_get_counter_value_by_id(service, "HostSystem", hv->id,
				counterid, datastore->uuid, 1, result)))
		{
			goto unlock;
		}

		if (0 == ISSET_VALUE(result))
			continue;

		if (0 == is_maxlatency)
		{
			latency += *GET_UI64_RESULT(result);
			count++;
		}
		else if (latency < *GET_UI64_RESULT(result))
			latency = *GET_UI64_RESULT(result);

		UNSET_UI64_RESULT(result);
	}

	if (0 == is_maxlatency && 0 != count)
		latency = latency / count;

	SET_UI64_RESULT(result, latency);
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_datastore_read(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	return check_vcenter_datastore_latency(request, username, password, "datastore/totalReadLatency[average]",
			result);
}

int	check_vcenter_datastore_write(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	return check_vcenter_datastore_latency(request, username, password, "datastore/totalWriteLatency[average]",
			result);
}

int	check_vcenter_vm_cpu_num(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_CPU_NUM, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_cluster_name(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *uuid;
	zbx_vmware_service_t	*service;
	zbx_vmware_cluster_t	*cluster = NULL;
	int			ret = SYSINFO_RET_FAIL;
	zbx_vmware_hv_t		*hv;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = service_hv_get_by_vm_uuid(service, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown virtual machine uuid."));
		goto unlock;
	}
	if (NULL != hv->clusterid)
		cluster = cluster_get(&service->data->clusters, hv->clusterid);

	SET_STR_RESULT(result, zbx_strdup(NULL, NULL != cluster ? cluster->name : ""));

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_cpu_ready(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	zbx_vmware_service_t	*service;
	int			ret = SYSINFO_RET_FAIL;
	const char		*url, *uuid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	ret = vmware_service_get_vm_counter(service, uuid, "", "cpu/ready[summation]", 1, result);
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_cpu_usage(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_CPU_USAGE, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * 1000000;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_datacenter_name(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	zbx_vmware_service_t	*service;
	zbx_vmware_hv_t		*hv;
	char			*url, *uuid;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (hv = service_hv_get_by_vm_uuid(service, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown virtual machine uuid."));
		goto unlock;
	}

	SET_STR_RESULT(result, zbx_strdup(NULL, hv->datacenter_name));
	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_discovery(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	struct zbx_json		json_data;
	char			*url, *vm_name, *hv_name;
	zbx_vmware_service_t	*service;
	zbx_vmware_hv_t		*hv;
	zbx_vmware_vm_t		*vm;
	zbx_hashset_iter_t	iter;
	int			i, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (1 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	zbx_json_initarray(&json_data, ZBX_JSON_STAT_BUF_LEN);

	zbx_hashset_iter_reset(&service->data->hvs, &iter);
	while (NULL != (hv = (zbx_vmware_hv_t *)zbx_hashset_iter_next(&iter)))
	{
		zbx_vmware_cluster_t	*cluster = NULL;

		if (NULL != hv->clusterid)
			cluster = cluster_get(&service->data->clusters, hv->clusterid);

		for (i = 0; i < hv->vms.values_num; i++)
		{
			vm = (zbx_vmware_vm_t *)hv->vms.values[i];

			if (NULL == (vm_name = vm->props[ZBX_VMWARE_VMPROP_NAME]))
				continue;

			if (NULL == (hv_name = hv->props[ZBX_VMWARE_HVPROP_NAME]))
				continue;

			zbx_json_addobject(&json_data, NULL);
			zbx_json_addstring(&json_data, "{#VM.UUID}", vm->uuid, ZBX_JSON_TYPE_STRING);
			zbx_json_addstring(&json_data, "{#VM.ID}", vm->id, ZBX_JSON_TYPE_STRING);
			zbx_json_addstring(&json_data, "{#VM.NAME}", vm_name, ZBX_JSON_TYPE_STRING);
			zbx_json_addstring(&json_data, "{#HV.NAME}", hv_name, ZBX_JSON_TYPE_STRING);
			zbx_json_addstring(&json_data, "{#DATACENTER.NAME}", hv->datacenter_name, ZBX_JSON_TYPE_STRING);
			zbx_json_addstring(&json_data, "{#CLUSTER.NAME}",
					NULL != cluster ? cluster->name : "", ZBX_JSON_TYPE_STRING);
			zbx_json_close(&json_data);
		}
	}

	zbx_json_close(&json_data);

	SET_STR_RESULT(result, zbx_strdup(NULL, json_data.buffer));

	zbx_json_free(&json_data);

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_hv_name(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	zbx_vmware_service_t	*service;
	zbx_vmware_hv_t		*hv;
	char			*url, *uuid, *name;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;


	if (NULL == (hv = service_hv_get_by_vm_uuid(service, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown virtual machine uuid."));
		goto unlock;
	}

	if (NULL == (name = hv->props[ZBX_VMWARE_HVPROP_NAME]))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "No hypervisor name found."));
		goto unlock;
	}

	SET_STR_RESULT(result, zbx_strdup(NULL, name));
	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_memory_size(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_MEMORY_SIZE, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * ZBX_MEBIBYTE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_memory_size_ballooned(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_MEMORY_SIZE_BALLOONED, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * ZBX_MEBIBYTE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_memory_size_compressed(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_MEMORY_SIZE_COMPRESSED, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * ZBX_MEBIBYTE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_memory_size_swapped(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_MEMORY_SIZE_SWAPPED, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * ZBX_MEBIBYTE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_memory_size_usage_guest(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_MEMORY_SIZE_USAGE_GUEST, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * ZBX_MEBIBYTE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_memory_size_usage_host(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_MEMORY_SIZE_USAGE_HOST, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * ZBX_MEBIBYTE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_memory_size_private(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_MEMORY_SIZE_PRIVATE, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * ZBX_MEBIBYTE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_memory_size_shared(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_MEMORY_SIZE_SHARED, result);

	if (SYSINFO_RET_OK == ret && NULL != GET_UI64_RESULT(result))
		result->ui64 = result->ui64 * ZBX_MEBIBYTE;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_powerstate(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_POWER_STATE, result);

	if (SYSINFO_RET_OK == ret)
		ret = vmware_set_powerstate_result(result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_net_if_discovery(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	struct zbx_json		json_data;
	zbx_vmware_service_t	*service;
	zbx_vmware_vm_t		*vm = NULL;
	zbx_vmware_dev_t	*dev;
	char			*url, *uuid;
	int			i, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (vm = service_vm_get(service, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown virtual machine uuid."));
		goto unlock;
	}

	zbx_json_initarray(&json_data, ZBX_JSON_STAT_BUF_LEN);

	for (i = 0; i < vm->devs.values_num; i++)
	{
		dev = (zbx_vmware_dev_t *)vm->devs.values[i];

		if (ZBX_VMWARE_DEV_TYPE_NIC != dev->type)
			continue;

		zbx_json_addobject(&json_data, NULL);
		zbx_json_addstring(&json_data, "{#IFNAME}", dev->instance, ZBX_JSON_TYPE_STRING);
		if (NULL != dev->label)
			zbx_json_addstring(&json_data, "{#IFDESC}", dev->label, ZBX_JSON_TYPE_STRING);

		zbx_json_close(&json_data);
	}

	zbx_json_close(&json_data);

	SET_STR_RESULT(result, zbx_strdup(NULL, json_data.buffer));

	zbx_json_free(&json_data);

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_net_if_in(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *uuid, *instance, *mode;
	zbx_vmware_service_t	*service;
	const char		*path;
	int 			coeff, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (3 > request->nparam || request->nparam > 4)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);
	instance = get_rparam(request, 2);
	mode = get_rparam(request, 3);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	if ('\0' == *instance)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid third parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == mode || '\0' == *mode || 0 == strcmp(mode, "bps"))
	{
		path = "net/received[average]";
		coeff = ZBX_KIBIBYTE;
	}
	else if (0 == strcmp(mode, "pps"))
	{
		path = "net/packetsRx[summation]";
		coeff = 1;
	}
	else
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid fourth parameter."));
		goto unlock;
	}

	ret = vmware_service_get_vm_counter(service, uuid, instance, path, coeff, result);
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_net_if_out(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *uuid, *instance, *mode;
	zbx_vmware_service_t	*service;
	const char		*path;
	int 			coeff, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (3 > request->nparam || request->nparam > 4)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);
	instance = get_rparam(request, 2);
	mode = get_rparam(request, 3);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	if ('\0' == *instance)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid third parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == mode || '\0' == *mode || 0 == strcmp(mode, "bps"))
	{
		path = "net/transmitted[average]";
		coeff = ZBX_KIBIBYTE;
	}
	else if (0 == strcmp(mode, "pps"))
	{
		path = "net/packetsTx[summation]";
		coeff = 1;
	}
	else
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid fourth parameter."));
		goto unlock;
	}

	ret = vmware_service_get_vm_counter(service, uuid, instance, path, coeff, result);
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_storage_committed(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_STORAGE_COMMITED, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_storage_unshared(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_STORAGE_UNSHARED, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_storage_uncommitted(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_STORAGE_UNCOMMITTED, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_uptime(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	ret = get_vcenter_vmprop(request, username, password, ZBX_VMWARE_VMPROP_UPTIME, result);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_vfs_dev_discovery(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	struct zbx_json		json_data;
	zbx_vmware_service_t	*service;
	zbx_vmware_vm_t		*vm = NULL;
	zbx_vmware_dev_t	*dev;
	char			*url, *uuid;
	int			i, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (vm = service_vm_get(service, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown virtual machine uuid."));
		goto unlock;
	}

	zbx_json_initarray(&json_data, ZBX_JSON_STAT_BUF_LEN);

	for (i = 0; i < vm->devs.values_num; i++)
	{
		dev = (zbx_vmware_dev_t *)vm->devs.values[i];

		if (ZBX_VMWARE_DEV_TYPE_DISK != dev->type)
			continue;

		zbx_json_addobject(&json_data, NULL);
		zbx_json_addstring(&json_data, "{#DISKNAME}", dev->instance, ZBX_JSON_TYPE_STRING);
		if (NULL != dev->label)
			zbx_json_addstring(&json_data, "{#DISKDESC}", dev->label, ZBX_JSON_TYPE_STRING);
		zbx_json_close(&json_data);
	}

	zbx_json_close(&json_data);

	SET_STR_RESULT(result, zbx_strdup(NULL, json_data.buffer));

	zbx_json_free(&json_data);

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_vfs_dev_read(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *uuid, *instance, *mode;
	zbx_vmware_service_t	*service;
	const char		*path;
	int			coeff, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (3 > request->nparam || request->nparam > 4)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);
	instance = get_rparam(request, 2);
	mode = get_rparam(request, 3);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	if ('\0' == *instance)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid third parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == mode || '\0' == *mode || 0 == strcmp(mode, "bps"))
	{
		path = "virtualDisk/read[average]";
		coeff = ZBX_KIBIBYTE;
	}
	else if (0 == strcmp(mode, "ops"))
	{
		path = "virtualDisk/numberReadAveraged[average]";
		coeff = 1;
	}
	else
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid fourth parameter."));
		goto unlock;
	}

	ret =  vmware_service_get_vm_counter(service, uuid, instance, path, coeff, result);
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_vfs_dev_write(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *uuid, *instance, *mode;
	zbx_vmware_service_t	*service;
	const char		*path;
	int			coeff, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (3 > request->nparam || request->nparam > 4)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);
	instance = get_rparam(request, 2);
	mode = get_rparam(request, 3);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	if ('\0' == *instance)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid third parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == mode || '\0' == *mode || 0 == strcmp(mode, "bps"))
	{
		path = "virtualDisk/write[average]";
		coeff = ZBX_KIBIBYTE;
	}
	else if (0 == strcmp(mode, "ops"))
	{
		path = "virtualDisk/numberWriteAveraged[average]";
		coeff = 1;
	}
	else
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid fourth parameter."));
		goto unlock;
	}

	ret =  vmware_service_get_vm_counter(service, uuid, instance, path, coeff, result);
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_vfs_fs_discovery(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	struct zbx_json		json_data;
	zbx_vmware_service_t	*service;
	zbx_vmware_vm_t		*vm = NULL;
	char			*url, *uuid;
	int			i, ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (2 != request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (vm = service_vm_get(service, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown virtual machine uuid."));
		goto unlock;
	}

	zbx_json_initarray(&json_data, ZBX_JSON_STAT_BUF_LEN);

	for (i = 0; i < vm->file_systems.values_num; i++)
	{
		zbx_vmware_fs_t	*fs = (zbx_vmware_fs_t *)vm->file_systems.values[i];

		zbx_json_addobject(&json_data, NULL);
		zbx_json_addstring(&json_data, "{#FSNAME}", fs->path, ZBX_JSON_TYPE_STRING);
		zbx_json_close(&json_data);
	}

	zbx_json_close(&json_data);

	SET_STR_RESULT(result, zbx_strdup(NULL, json_data.buffer));

	zbx_json_free(&json_data);

	ret = SYSINFO_RET_OK;
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_vfs_fs_size(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	zbx_vmware_service_t	*service;
	zbx_vmware_vm_t		*vm;
	char			*url, *uuid, *fsname, *mode;
	int			i, ret = SYSINFO_RET_FAIL;
	zbx_vmware_fs_t		*fs = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (3 > request->nparam || request->nparam > 4)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);
	fsname = get_rparam(request, 2);
	mode = get_rparam(request, 3);

	if ('\0' == *uuid)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid second parameter."));
		goto out;
	}

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (vm = service_vm_get(service, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown virtual machine uuid."));
		goto unlock;
	}

	for (i = 0; i < vm->file_systems.values_num; i++)
	{
		fs = (zbx_vmware_fs_t *)vm->file_systems.values[i];

		if (0 == strcmp(fs->path, fsname))
			break;
	}

	if (NULL == fs)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown file system path."));
		goto unlock;
	}

	ret = SYSINFO_RET_OK;

	if (NULL == mode || '\0' == *mode || 0 == strcmp(mode, "total"))
		SET_UI64_RESULT(result, fs->capacity);
	else if (0 == strcmp(mode, "free"))
		SET_UI64_RESULT(result, fs->free_space);
	else if (0 == strcmp(mode, "used"))
		SET_UI64_RESULT(result, fs->capacity - fs->free_space);
	else if (0 == strcmp(mode, "pfree"))
		SET_DBL_RESULT(result, 0 != fs->capacity ? (double)(100.0 * fs->free_space) / fs->capacity : 0);
	else if (0 == strcmp(mode, "pused"))
		SET_DBL_RESULT(result, 100.0 - (0 != fs->capacity ? 100.0 * fs->free_space / fs->capacity : 0));
	else
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid fourth parameter."));
		ret = SYSINFO_RET_FAIL;
	}
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

int	check_vcenter_vm_perfcounter(AGENT_REQUEST *request, const char *username, const char *password,
		AGENT_RESULT *result)
{
	char			*url, *uuid, *path;
	const char 		*instance;
	zbx_vmware_service_t	*service;
	zbx_vmware_vm_t		*vm;
	zbx_uint64_t		counterid;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (3 > request->nparam || request->nparam > 4)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid number of parameters."));
		goto out;
	}

	url = get_rparam(request, 0);
	uuid = get_rparam(request, 1);
	path = get_rparam(request, 2);
	instance = get_rparam(request, 3);

	if (NULL == instance)
		instance = "";

	zbx_vmware_lock();

	if (NULL == (service = get_vmware_service(url, username, password, result, &ret)))
		goto unlock;

	if (NULL == (vm = service_vm_get(service, uuid)))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Unknown virtual machine uuid."));
		goto unlock;
	}

	if (FAIL == zbx_vmware_service_get_counterid(service, path, &counterid))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Performance counter is not available."));
		goto unlock;
	}

	/* FAIL is returned if counter already exists */
	if (SUCCEED == zbx_vmware_service_add_perf_counter(service, "VirtualMachine", vm->id, counterid, "*"))
	{
		ret = SYSINFO_RET_OK;
		goto unlock;
	}

	/* the performance counter is already being monitored, try to get the results from statistics */
	ret = vmware_service_get_counter_value_by_id(service, "VirtualMachine", vm->id, counterid, instance, 1, result);
unlock:
	zbx_vmware_unlock();
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_sysinfo_ret_string(ret));

	return ret;
}

#endif	/* defined(HAVE_LIBXML2) && defined(HAVE_LIBCURL) */
