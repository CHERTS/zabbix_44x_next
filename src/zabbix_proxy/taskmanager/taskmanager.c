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
#include "daemon.h"
#include "zbxself.h"
#include "zbxtasks.h"
#include "log.h"
#include "db.h"
#include "dbcache.h"
#include "zbxcrypto.h"

#include "../../zabbix_server/scripts/scripts.h"
#include "taskmanager.h"

#define ZBX_TM_PROCESS_PERIOD		5
#define ZBX_TM_CLEANUP_PERIOD		SEC_PER_HOUR

extern unsigned char	process_type, program_type;
extern int		server_num, process_num;

/******************************************************************************
 *                                                                            *
 * Function: tm_execute_remote_command                                        *
 *                                                                            *
 * Purpose: execute remote command task                                       *
 *                                                                            *
 * Parameters: taskid - [IN] the task identifier                              *
 *             clock  - [IN] the task creation time                           *
 *             ttl    - [IN] the task expiration period in seconds            *
 *             now    - [IN] the current time                                 *
 *                                                                            *
 * Return value: SUCCEED - the remote command was executed                    *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	tm_execute_remote_command(zbx_uint64_t taskid, int clock, int ttl, int now)
{
	DB_ROW		row;
	DB_RESULT	result;
	zbx_uint64_t	parent_taskid, hostid, alertid;
	zbx_tm_task_t	*task = NULL;
	int		ret = FAIL;
	zbx_script_t	script;
	char		*info = NULL, error[MAX_STRING_LEN];
	DC_HOST		host;

	result = DBselect("select command_type,execute_on,port,authtype,username,password,publickey,privatekey,"
					"command,parent_taskid,hostid,alertid"
				" from task_remote_command"
				" where taskid=" ZBX_FS_UI64,
				taskid);

	if (NULL == (row = DBfetch(result)))
		goto finish;

	task = zbx_tm_task_create(0, ZBX_TM_TASK_REMOTE_COMMAND_RESULT, ZBX_TM_STATUS_NEW, time(NULL), 0, 0);

	ZBX_STR2UINT64(parent_taskid, row[9]);

	if (0 != ttl && clock + ttl < now)
	{
		task->data = zbx_tm_remote_command_result_create(parent_taskid, FAIL,
				"The remote command has been expired.");
		goto finish;
	}

	ZBX_STR2UINT64(hostid, row[10]);
	if (FAIL == DCget_host_by_hostid(&host, hostid))
	{
		task->data = zbx_tm_remote_command_result_create(parent_taskid, FAIL, "Unknown host.");
		goto finish;
	}

	zbx_script_init(&script);

	ZBX_STR2UCHAR(script.type, row[0]);
	ZBX_STR2UCHAR(script.execute_on, row[1]);
	script.port = (0 == atoi(row[2]) ? (char *)"" : row[2]);
	ZBX_STR2UCHAR(script.authtype, row[3]);
	script.username = row[4];
	script.password = row[5];
	script.publickey = row[6];
	script.privatekey = row[7];
	script.command = row[8];

	if (ZBX_SCRIPT_EXECUTE_ON_PROXY == script.execute_on)
	{
		/* always wait for execution result when executing on Zabbix proxy */
		alertid = 0;

		if (ZBX_SCRIPT_TYPE_CUSTOM_SCRIPT == script.type)
		{
			if (0 == CONFIG_ENABLE_REMOTE_COMMANDS)
			{
				task->data = zbx_tm_remote_command_result_create(parent_taskid, FAIL,
						"Remote commands are not enabled");
				goto finish;
			}

			if (1 == CONFIG_LOG_REMOTE_COMMANDS)
				zabbix_log(LOG_LEVEL_WARNING, "Executing command '%s'", script.command);
			else
				zabbix_log(LOG_LEVEL_DEBUG, "Executing command '%s'", script.command);
		}
	}
	else
	{
		/* only wait for execution result when executed on Zabbix agent if it's not automatic alert but */
		/* manually initiated command through frontend                                                  */
		ZBX_DBROW2UINT64(alertid, row[11]);
	}

	if (SUCCEED != (ret = zbx_script_execute(&script, &host, 0 == alertid ? &info : NULL, error, sizeof(error))))
		task->data = zbx_tm_remote_command_result_create(parent_taskid, ret, error);
	else
		task->data = zbx_tm_remote_command_result_create(parent_taskid, ret, info);

	zbx_free(info);
finish:
	DBfree_result(result);

	DBbegin();

	if (NULL != task)
	{
		zbx_tm_save_task(task);
		zbx_tm_task_free(task);
	}

	DBexecute("update task set status=%d where taskid=" ZBX_FS_UI64, ZBX_TM_STATUS_DONE, taskid);

	DBcommit();

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: tm_process_check_now                                             *
 *                                                                            *
 * Purpose: process check now tasks for item rescheduling                     *
 *                                                                            *
 * Return value: The number of successfully processed tasks                   *
 *                                                                            *
 ******************************************************************************/
static int	tm_process_check_now(zbx_vector_uint64_t *taskids)
{
	DB_ROW			row;
	DB_RESULT		result;
	int			processed_num;
	char			*sql = NULL;
	size_t			sql_alloc = 0, sql_offset = 0;
	zbx_vector_uint64_t	itemids;
	zbx_uint64_t		itemid;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() tasks_num:%d", __func__, taskids->values_num);

	zbx_vector_uint64_create(&itemids);

	zbx_strcpy_alloc(&sql, &sql_alloc, &sql_offset, "select itemid from task_check_now where");
	DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "taskid", taskids->values, taskids->values_num);
	result = DBselect("%s", sql);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(itemid, row[0]);
		zbx_vector_uint64_append(&itemids, itemid);
	}
	DBfree_result(result);

	if (0 != (processed_num = itemids.values_num))
		zbx_dc_reschedule_items(&itemids, time(NULL), NULL);

	if (0 != taskids->values_num)
	{
		sql_offset = 0;
		zbx_snprintf_alloc(&sql, &sql_alloc, &sql_offset, "update task set status=%d where",
				ZBX_TM_STATUS_DONE);
		DBadd_condition_alloc(&sql, &sql_alloc, &sql_offset, "taskid", taskids->values, taskids->values_num);

		DBexecute("%s", sql);
	}

	zbx_free(sql);
	zbx_vector_uint64_destroy(&itemids);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s() processed:%d", __func__, processed_num);

	return processed_num;
}

/******************************************************************************
 *                                                                            *
 * Function: tm_process_tasks                                                 *
 *                                                                            *
 * Purpose: process task manager tasks depending on task type                 *
 *                                                                            *
 * Return value: The number of successfully processed tasks                   *
 *                                                                            *
 ******************************************************************************/
static int	tm_process_tasks(int now)
{
	DB_ROW			row;
	DB_RESULT		result;
	int			processed_num = 0, clock, ttl;
	zbx_uint64_t		taskid;
	unsigned char		type;
	zbx_vector_uint64_t	check_now_taskids;

	zbx_vector_uint64_create(&check_now_taskids);

	result = DBselect("select taskid,type,clock,ttl"
				" from task"
				" where status=%d"
					" and type in (%d, %d)"
				" order by taskid",
			ZBX_TM_STATUS_NEW, ZBX_TM_TASK_REMOTE_COMMAND, ZBX_TM_TASK_CHECK_NOW);

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(taskid, row[0]);
		ZBX_STR2UCHAR(type, row[1]);
		clock = atoi(row[2]);
		ttl = atoi(row[3]);

		switch (type)
		{
			case ZBX_TM_TASK_REMOTE_COMMAND:
				if (SUCCEED == tm_execute_remote_command(taskid, clock, ttl, now))
					processed_num++;
				break;
			case ZBX_TM_TASK_CHECK_NOW:
				zbx_vector_uint64_append(&check_now_taskids, taskid);
				break;
			default:
				THIS_SHOULD_NEVER_HAPPEN;
				break;
		}
	}
	DBfree_result(result);

	if (0 < check_now_taskids.values_num)
		processed_num += tm_process_check_now(&check_now_taskids);

	zbx_vector_uint64_destroy(&check_now_taskids);

	return processed_num;
}

/******************************************************************************
 *                                                                            *
 * Function: tm_remove_old_tasks                                              *
 *                                                                            *
 * Purpose: remove old done/expired tasks                                     *
 *                                                                            *
 ******************************************************************************/
static void	tm_remove_old_tasks(int now)
{
	DBbegin();
	DBexecute("delete from task where status in (%d,%d) and clock<=%d",
			ZBX_TM_STATUS_DONE, ZBX_TM_STATUS_EXPIRED, now - ZBX_TM_CLEANUP_TASK_AGE);
	DBcommit();
}

ZBX_THREAD_ENTRY(taskmanager_thread, args)
{
	static int	cleanup_time = 0;

	double	sec1, sec2;
	int	tasks_num, sleeptime, nextcheck;

	process_type = ((zbx_thread_args_t *)args)->process_type;
	server_num = ((zbx_thread_args_t *)args)->server_num;
	process_num = ((zbx_thread_args_t *)args)->process_num;

	zabbix_log(LOG_LEVEL_INFORMATION, "%s #%d started [%s #%d]", get_program_type_string(program_type),
			server_num, get_process_type_string(process_type), process_num);

	update_selfmon_counter(ZBX_PROCESS_STATE_BUSY);

#if defined(HAVE_POLARSSL) || defined(HAVE_GNUTLS) || defined(HAVE_OPENSSL)
	zbx_tls_init_child();
#endif
	zbx_setproctitle("%s [connecting to the database]", get_process_type_string(process_type));
	DBconnect(ZBX_DB_CONNECT_NORMAL);

	sec1 = zbx_time();

	sleeptime = ZBX_TM_PROCESS_PERIOD - (int)sec1 % ZBX_TM_PROCESS_PERIOD;

	zbx_setproctitle("%s [started, idle %d sec]", get_process_type_string(process_type), sleeptime);

	while (ZBX_IS_RUNNING())
	{
		zbx_sleep_loop(sleeptime);

		sec1 = zbx_time();
		zbx_update_env(sec1);

		zbx_setproctitle("%s [processing tasks]", get_process_type_string(process_type));

		tasks_num = tm_process_tasks((int)sec1);
		if (ZBX_TM_CLEANUP_PERIOD <= sec1 - cleanup_time)
		{
			tm_remove_old_tasks((int)sec1);
			cleanup_time = sec1;
		}

		sec2 = zbx_time();

		nextcheck = (int)sec1 - (int)sec1 % ZBX_TM_PROCESS_PERIOD + ZBX_TM_PROCESS_PERIOD;

		if (0 > (sleeptime = nextcheck - (int)sec2))
			sleeptime = 0;

		zbx_setproctitle("%s [processed %d task(s) in " ZBX_FS_DBL " sec, idle %d sec]",
				get_process_type_string(process_type), tasks_num, sec2 - sec1, sleeptime);
	}

	zbx_setproctitle("%s #%d [terminated]", get_process_type_string(process_type), process_num);

	while (1)
		zbx_sleep(SEC_PER_MIN);
}
