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

#include "db.h"

/******************************************************************************
 *                                                                            *
 * Purpose: save item state, error, mtime, lastlogsize changes to             *
 *          database                                                          *
 *                                                                            *
 ******************************************************************************/
void	zbx_db_save_item_changes(char **sql, size_t *sql_alloc, size_t *sql_offset, const zbx_vector_ptr_t *item_diff,
		zbx_uint64_t mask)
{
	int			i;
	const zbx_item_diff_t	*diff;
	char			*value_esc;
	zbx_uint64_t		flags;

	for (i = 0; i < item_diff->values_num; i++)
	{
		char	delim = ' ';

		diff = (const zbx_item_diff_t *)item_diff->values[i];
		flags = diff->flags & mask;

		if (0 == (ZBX_FLAGS_ITEM_DIFF_UPDATE_DB & flags))
			continue;

		zbx_strcpy_alloc(sql, sql_alloc, sql_offset, "update item_rtdata set");

		if (0 != (ZBX_FLAGS_ITEM_DIFF_UPDATE_LASTLOGSIZE & flags))
		{
			zbx_snprintf_alloc(sql, sql_alloc, sql_offset, "%clastlogsize=" ZBX_FS_UI64, delim,
					diff->lastlogsize);
			delim = ',';
		}

		if (0 != (ZBX_FLAGS_ITEM_DIFF_UPDATE_MTIME & flags))
		{
			zbx_snprintf_alloc(sql, sql_alloc, sql_offset, "%cmtime=%d", delim, diff->mtime);
			delim = ',';
		}

		if (0 != (ZBX_FLAGS_ITEM_DIFF_UPDATE_STATE & flags))
		{
			zbx_snprintf_alloc(sql, sql_alloc, sql_offset, "%cstate=%d", delim, (int)diff->state);
			delim = ',';
		}

		if (0 != (ZBX_FLAGS_ITEM_DIFF_UPDATE_ERROR & flags))
		{
			value_esc = DBdyn_escape_field("item_rtdata", "error", diff->error);
			zbx_snprintf_alloc(sql, sql_alloc, sql_offset, "%cerror='%s'", delim, value_esc);
			zbx_free(value_esc);
		}

		zbx_snprintf_alloc(sql, sql_alloc, sql_offset, " where itemid=" ZBX_FS_UI64 ";\n", diff->itemid);

		DBexecute_overflowed_sql(sql, sql_alloc, sql_offset);
	}
}
