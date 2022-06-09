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

#include "common.h"
#include "log.h"
#include "sysinfo.h"

#define CHECKED_SYSCONF_SYSCALL(sysconf_name)									\
	errno = 0;												\
	if (-1 == (res##sysconf_name = sysconf(sysconf_name)))							\
	{													\
		SET_MSG_RESULT(result, zbx_dsprintf(NULL, "Cannot get sysconf(" #sysconf_name "), errno: %s",	\
				zbx_strerror(errno)));								\
		ret = SYSINFO_RET_FAIL;										\
		goto out;											\
	}													

#ifdef HAVE_VMINFO_T_UPDATES
#include "stats.h"
#endif

static int	VM_MEMORY_TOTAL(AGENT_RESULT *result)
{
	int	ret;
	long	 res_SC_PHYS_PAGES, res_SC_PAGESIZE;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s", __func__);

	CHECKED_SYSCONF_SYSCALL(_SC_PHYS_PAGES);
	CHECKED_SYSCONF_SYSCALL(_SC_PAGESIZE);

	SET_UI64_RESULT(result, (zbx_uint64_t)res_SC_PHYS_PAGES * res_SC_PAGESIZE);
	ret = SYSINFO_RET_OK;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));
	return ret;
}

#ifndef HAVE_VMINFO_T_UPDATES
static int	VM_MEMORY_USED(AGENT_RESULT *result)
{
	int		ret;
	zbx_uint64_t	used;
	long		res_SC_PHYS_PAGES, res_SC_AVPHYS_PAGES, res_SC_PAGESIZE;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s, (no HAVE_VMINFO_T_UPDATES)", __func__);

	CHECKED_SYSCONF_SYSCALL(_SC_PHYS_PAGES);
	CHECKED_SYSCONF_SYSCALL(_SC_AVPHYS_PAGES);
	CHECKED_SYSCONF_SYSCALL(_SC_PAGESIZE);

	used = res_SC_PHYS_PAGES - res_SC_AVPHYS_PAGES;

	SET_UI64_RESULT(result, used * res_SC_PAGESIZE);
	ret = SYSINFO_RET_OK;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

static int	VM_MEMORY_PUSED(AGENT_RESULT *result)
{
	int		ret;
	zbx_uint64_t	used, total;
	long		res_SC_PHYS_PAGES, res_SC_AVPHYS_PAGES;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s, (no HAVE_VMINFO_T_UPDATES)", __func__);

	CHECKED_SYSCONF_SYSCALL(_SC_PHYS_PAGES);
	CHECKED_SYSCONF_SYSCALL(_SC_AVPHYS_PAGES);

	if (0 == (total = res_SC_PHYS_PAGES))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot calculate percentage because total is zero."));
		ret = SYSINFO_RET_FAIL;
		goto out;
	}

	used = total - res_SC_AVPHYS_PAGES;

	SET_DBL_RESULT(result, used / (double)total * 100);
	ret = SYSINFO_RET_OK;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

static int	VM_MEMORY_AVAILABLE(AGENT_RESULT *result)
{
	int		ret;
	long		res_SC_AVPHYS_PAGES, res_SC_PAGESIZE;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s, (no HAVE_VMINFO_T_UPDATES)", __func__);

	CHECKED_SYSCONF_SYSCALL(_SC_AVPHYS_PAGES);
	CHECKED_SYSCONF_SYSCALL(_SC_PAGESIZE);

	SET_UI64_RESULT(result, (zbx_uint64_t)res_SC_AVPHYS_PAGES * res_SC_PAGESIZE);
	ret = SYSINFO_RET_OK;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

static int	VM_MEMORY_PAVAILABLE(AGENT_RESULT *result)
{
	int		ret;
	zbx_uint64_t	total;
	long		res_SC_PHYS_PAGES, res_SC_AVPHYS_PAGES;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s, (no HAVE_VMINFO_T_UPDATES)", __func__);

	CHECKED_SYSCONF_SYSCALL(_SC_PHYS_PAGES);

	if (0 == (total = res_SC_PHYS_PAGES))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot calculate percentage because total is zero."));
		ret = SYSINFO_RET_FAIL;
		goto out;
	}

	CHECKED_SYSCONF_SYSCALL(_SC_AVPHYS_PAGES);

	SET_DBL_RESULT(result, res_SC_AVPHYS_PAGES / (double)total * 100);
	ret = SYSINFO_RET_OK;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

#else /*HAVE_VMINFO_T_UPDATES*/

static int	VM_MEMORY_USED(AGENT_RESULT *result)
{
	int		ret;
	zbx_uint64_t	freemem;
	char		*error = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s (with HAVE_VMINFO_T_UPDATES)", __func__);

	if (SUCCEED == zbx_kstat_get_freemem(&freemem, &error))
	{
		long	res_SC_PHYS_PAGES, res_SC_PAGESIZE;

		CHECKED_SYSCONF_SYSCALL(_SC_PHYS_PAGES);
		CHECKED_SYSCONF_SYSCALL(_SC_PAGESIZE);

		SET_UI64_RESULT(result, res_SC_PHYS_PAGES * res_SC_PAGESIZE - freemem);
	}
	else if (NULL != error)
	{
		SET_MSG_RESULT(result, error);
		ret = SYSINFO_RET_FAIL;
		goto out;
	}
	else
		zabbix_log(LOG_LEVEL_DEBUG, "zbx_kstat_get_freemem() failed, but error is NULL");

	ret = SYSINFO_RET_OK;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

static int	VM_MEMORY_PUSED(AGENT_RESULT *result)
{
	int		ret;
	zbx_uint64_t	freemem, total;
	char		*error = NULL;
	long		res_SC_PHYS_PAGES;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s (with HAVE_VMINFO_T_UPDATES)", __func__);

	CHECKED_SYSCONF_SYSCALL(_SC_PHYS_PAGES);

	if (0 == (total = res_SC_PHYS_PAGES))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot calculate percentage because total is zero."));
		ret = SYSINFO_RET_FAIL;
		goto out;
	}

	if (SUCCEED == zbx_kstat_get_freemem(&freemem, &error))
	{
		long	res_SC_PAGESIZE;

		CHECKED_SYSCONF_SYSCALL(_SC_PAGESIZE);

		total *= res_SC_PHYS_PAGES;
		SET_DBL_RESULT(result, (total - freemem) / (double)total * 100);
	}
	else if (NULL != error)
	{
		SET_MSG_RESULT(result, error);
		ret = SYSINFO_RET_FAIL;
		goto out;
	}
	else
		zabbix_log(LOG_LEVEL_DEBUG, "zbx_kstat_get_freemem() failed, but error is NULL");

	ret = SYSINFO_RET_OK;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

static int	VM_MEMORY_AVAILABLE(AGENT_RESULT *result)
{
	int		ret;
	zbx_uint64_t	freemem;
	char		*error = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s, (with HAVE_VMINFO_T_UPDATES)", __func__);

	if (SUCCEED == zbx_kstat_get_freemem(&freemem, &error))
	{
		SET_UI64_RESULT(result, freemem);
	}
	else if (NULL != error)
	{
		SET_MSG_RESULT(result, error);
		ret = SYSINFO_RET_FAIL;
		goto out;
	}
	else
		zabbix_log(LOG_LEVEL_DEBUG, "zbx_kstat_get_freemem() failed, but error is NULL");

	ret = SYSINFO_RET_OK;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

static int	VM_MEMORY_PAVAILABLE(AGENT_RESULT *result)
{
	int		ret;
	zbx_uint64_t	total, freemem;
	char		*error = NULL;
	long		res_SC_PHYS_PAGES;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s, (with HAVE_VMINFO_T_UPDATES)", __func__);

	CHECKED_SYSCONF_SYSCALL(_SC_PHYS_PAGES);

	if (0 == (total = res_SC_PHYS_PAGES))
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Cannot calculate percentage because total is zero."));
		ret = SYSINFO_RET_FAIL;
		goto out;
	}

	if (SUCCEED == zbx_kstat_get_freemem(&freemem, &error))
	{
		long	res_SC_PAGESIZE;

		CHECKED_SYSCONF_SYSCALL(_SC_PAGESIZE);

		total *= res_SC_PAGESIZE;
		SET_DBL_RESULT(result, freemem / (double)total * 100);
	}
	else if (NULL != error)
	{
		SET_MSG_RESULT(result, error);
		ret = SYSINFO_RET_FAIL;
		goto out;
	}
	else
		zabbix_log(LOG_LEVEL_DEBUG, "zbx_kstate_get_freemem() failed, but error is NULL");

	ret = SYSINFO_RET_OK;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}
#endif /*HAVE_VMINFO_T_UPDATES*/

int	VM_MEMORY_SIZE(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*mode;
	int	ret;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s", __func__);

	if (1 < request->nparam)
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Too many parameters."));
		ret = SYSINFO_RET_FAIL;
		goto out;
	}

	mode = get_rparam(request, 0);

	if (NULL == mode || '\0' == *mode || 0 == strcmp(mode, "total"))
		ret = VM_MEMORY_TOTAL(result);
	else if (0 == strcmp(mode, "used"))
		ret = VM_MEMORY_USED(result);
	else if (0 == strcmp(mode, "pused"))
		ret = VM_MEMORY_PUSED(result);
	else if (0 == strcmp(mode, "available") || 0 == strcmp(mode, "free"))
		ret = VM_MEMORY_AVAILABLE(result);
	else if (0 == strcmp(mode, "pavailable"))
		ret = VM_MEMORY_PAVAILABLE(result);
	else
	{
		SET_MSG_RESULT(result, zbx_strdup(NULL, "Invalid first parameter."));
		ret = SYSINFO_RET_FAIL;
	}
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}
