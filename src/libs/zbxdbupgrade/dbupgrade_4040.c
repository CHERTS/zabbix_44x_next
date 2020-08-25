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
#include "db.h"
#include "dbupgrade.h"
#include "log.h"

extern unsigned char	program_type;

/*
 * 4.4 maintenance database patches
 */

#ifndef HAVE_SQLITE3

static int	DBpatch_4040000(void)
{
	return SUCCEED;
}

static int	DBpatch_4040001(void)
{
	DB_RESULT		result;
	DB_ROW			row;
	zbx_uint64_t		time_period_id, every;
	int			invalidate = 0;
	const ZBX_TABLE		*timeperiods;
	const ZBX_FIELD		*field;

	if (NULL != (timeperiods = DBget_table("timeperiods")) &&
			NULL != (field = DBget_field(timeperiods, "every")))
	{
		ZBX_STR2UINT64(every, field->default_value);
	}
	else
	{
		THIS_SHOULD_NEVER_HAPPEN;
		return FAIL;
	}

	result = DBselect("select timeperiodid from timeperiods where every=0");

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_STR2UINT64(time_period_id, row[0]);

		zabbix_log(LOG_LEVEL_WARNING, "Invalid maintenance time period found: "ZBX_FS_UI64
				", changing \"every\" to "ZBX_FS_UI64, time_period_id, every);
		invalidate = 1;
	}

	DBfree_result(result);

	if (0 != invalidate &&
			ZBX_DB_OK > DBexecute("update timeperiods set every=1 where timeperiodid!=0 and every=0"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_4040002(void)
{
	if (0 == (program_type & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	if (ZBX_DB_OK > DBexecute("delete from profiles where idx='web.screens.graphid'"))
		return FAIL;

	return SUCCEED;
}

static int	DBpatch_4040003(void)
{
	DB_ROW		row;
	DB_RESULT	result;
	zbx_uint64_t	profileid, userid, idx2;
	int		ret = SUCCEED, value_int, i;
	const char	*profile = "web.problem.filter.severities";

	if (0 == (program_type & ZBX_PROGRAM_TYPE_SERVER))
		return SUCCEED;

	result = DBselect(
			"select profileid,userid,value_int"
			" from profiles"
			" where idx='web.problem.filter.severity'");

	while (NULL != (row = DBfetch(result)))
	{
		ZBX_DBROW2UINT64(profileid, row[0]);

		if (0 == (value_int = atoi(row[2])))
		{
			if (ZBX_DB_OK > DBexecute("delete from profiles where profileid=" ZBX_FS_UI64, profileid))
			{
				ret = FAIL;
				break;
			}

			continue;
		}

		if (ZBX_DB_OK > DBexecute("update profiles set idx='%s'"
				" where profileid=" ZBX_FS_UI64, profile, profileid))
		{
			ret = FAIL;
			break;
		}

		ZBX_DBROW2UINT64(userid, row[1]);
		idx2 = 0;

		for (i = value_int + 1; i < 6; i++)
		{
			if (ZBX_DB_OK > DBexecute("insert into profiles (profileid,userid,idx,idx2,value_id,value_int,"
					"type) values (" ZBX_FS_UI64 "," ZBX_FS_UI64 ",'%s'," ZBX_FS_UI64 ",0,%d,2)",
					DBget_maxid("profiles"), userid, profile, ++idx2, i))
			{
				ret = FAIL;
				break;
			}
		}
	}
	DBfree_result(result);

	return ret;
}

#endif

DBPATCH_START(4040)

/* version, duplicates flag, mandatory flag */

DBPATCH_ADD(4040000, 0, 1)
DBPATCH_ADD(4040001, 0, 0)
DBPATCH_ADD(4040002, 0, 0)
DBPATCH_ADD(4040003, 0, 0)

DBPATCH_END()
