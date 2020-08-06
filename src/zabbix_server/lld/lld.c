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

#include "lld.h"
#include "db.h"
#include "log.h"
#include "../events.h"
#include "zbxalgo.h"
#include "zbxserver.h"
#include "zbxregexp.h"
#include "proxy.h"

/* lld rule filter condition (item_condition table record) */
typedef struct
{
	zbx_uint64_t		id;
	char			*macro;
	char			*regexp;
	zbx_vector_ptr_t	regexps;
	unsigned char		op;
}
lld_condition_t;

/* lld rule filter */
typedef struct
{
	zbx_vector_ptr_t	conditions;
	char			*expression;
	int			evaltype;
}
lld_filter_t;

/******************************************************************************
 *                                                                            *
 * Function: lld_condition_free                                               *
 *                                                                            *
 * Purpose: release resources allocated by filter condition                   *
 *                                                                            *
 * Parameters: condition  - [IN] the filter condition                         *
 *                                                                            *
 ******************************************************************************/
static void	lld_condition_free(lld_condition_t *condition)
{
	zbx_regexp_clean_expressions(&condition->regexps);
	zbx_vector_ptr_destroy(&condition->regexps);

	zbx_free(condition->macro);
	zbx_free(condition->regexp);
	zbx_free(condition);
}

/******************************************************************************
 *                                                                            *
 * Function: lld_conditions_free                                              *
 *                                                                            *
 * Purpose: release resources allocated by filter conditions                  *
 *                                                                            *
 * Parameters: conditions - [IN] the filter conditions                        *
 *                                                                            *
 ******************************************************************************/
static void	lld_conditions_free(zbx_vector_ptr_t *conditions)
{
	zbx_vector_ptr_clear_ext(conditions, (zbx_clean_func_t)lld_condition_free);
	zbx_vector_ptr_destroy(conditions);
}

/******************************************************************************
 *                                                                            *
 * Function: lld_condition_compare_by_macro                                   *
 *                                                                            *
 * Purpose: compare two filter conditions by their macros                     *
 *                                                                            *
 * Parameters: item1  - [IN] the first filter condition                       *
 *             item2  - [IN] the second filter condition                      *
 *                                                                            *
 ******************************************************************************/
static int	lld_condition_compare_by_macro(const void *item1, const void *item2)
{
	lld_condition_t	*condition1 = *(lld_condition_t **)item1;
	lld_condition_t	*condition2 = *(lld_condition_t **)item2;

	return strcmp(condition1->macro, condition2->macro);
}

/******************************************************************************
 *                                                                            *
 * Function: lld_filter_init                                                  *
 *                                                                            *
 * Purpose: initializes lld filter                                            *
 *                                                                            *
 * Parameters: filter  - [IN] the lld filter                                  *
 *                                                                            *
 ******************************************************************************/
static void	lld_filter_init(lld_filter_t *filter)
{
	zbx_vector_ptr_create(&filter->conditions);
	filter->expression = NULL;
	filter->evaltype = CONDITION_EVAL_TYPE_AND_OR;
}

/******************************************************************************
 *                                                                            *
 * Function: lld_filter_clean                                                 *
 *                                                                            *
 * Purpose: releases resources allocated by lld filter                        *
 *                                                                            *
 * Parameters: filter  - [IN] the lld filter                                  *
 *                                                                            *
 ******************************************************************************/
static void	lld_filter_clean(lld_filter_t *filter)
{
	zbx_free(filter->expression);
	lld_conditions_free(&filter->conditions);
}

/******************************************************************************
 *                                                                            *
 * Function: lld_filter_load                                                  *
 *                                                                            *
 * Purpose: loads lld filter data                                             *
 *                                                                            *
 * Parameters: filter     - [IN] the lld filter                               *
 *             lld_ruleid - [IN] the lld rule id                              *
 *             error      - [OUT] the error description                       *
 *                                                                            *
 ******************************************************************************/
static int	lld_filter_load(lld_filter_t *filter, zbx_uint64_t lld_ruleid, char **error)
{
	DB_RESULT	result;
	DB_ROW		row;
	lld_condition_t	*condition;
	DC_ITEM		item;
	int		errcode, ret = SUCCEED;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	DCconfig_get_items_by_itemids(&item, &lld_ruleid, &errcode, 1);

	if (SUCCEED != errcode)
	{
		*error = zbx_dsprintf(*error, "Invalid discovery rule ID [" ZBX_FS_UI64 "].",
				lld_ruleid);
		ret = FAIL;
		goto out;
	}

	result = DBselect(
			"select item_conditionid,macro,value,operator"
			" from item_condition"
			" where itemid=" ZBX_FS_UI64,
			lld_ruleid);

	while (NULL != (row = DBfetch(result)))
	{
		condition = (lld_condition_t *)zbx_malloc(NULL, sizeof(lld_condition_t));
		ZBX_STR2UINT64(condition->id, row[0]);
		condition->macro = zbx_strdup(NULL, row[1]);
		condition->regexp = zbx_strdup(NULL, row[2]);
		condition->op = (unsigned char)atoi(row[3]);

		zbx_vector_ptr_create(&condition->regexps);

		zbx_vector_ptr_append(&filter->conditions, condition);

		if ('@' == *condition->regexp)
		{
			DCget_expressions_by_name(&condition->regexps, condition->regexp + 1);

			if (0 == condition->regexps.values_num)
			{
				*error = zbx_dsprintf(*error, "Global regular expression \"%s\" does not exist.",
						condition->regexp + 1);
				ret = FAIL;
				break;
			}
		}
		else
		{
			substitute_simple_macros(NULL, NULL, NULL, NULL, NULL, NULL, &item, NULL, NULL,
					&condition->regexp, MACRO_TYPE_LLD_FILTER, NULL, 0);
		}
	}
	DBfree_result(result);

	if (SUCCEED != ret)
		lld_conditions_free(&filter->conditions);
	else if (CONDITION_EVAL_TYPE_AND_OR == filter->evaltype)
		zbx_vector_ptr_sort(&filter->conditions, lld_condition_compare_by_macro);
out:
	DCconfig_clean_items(&item, &errcode, 1);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: filter_condition_match                                           *
 *                                                                            *
 * Purpose: check if the lld data passes filter evaluation                    *
 *                                                                            *
 * Parameters: filter          - [IN] the lld filter                          *
 *             jp_row          - [IN] the lld data row                        *
 *             lld_macro_paths - [IN] use json path to extract from jp_row    *
 *                                                                            *
 * Return value: SUCCEED - the lld data passed filter evaluation              *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	filter_condition_match(const struct zbx_json_parse *jp_row, const zbx_vector_ptr_t *lld_macro_paths,
		const lld_condition_t *condition)
{
	char	*value = NULL;
	int	ret;

	if (SUCCEED == (ret = zbx_lld_macro_value_by_name(jp_row, lld_macro_paths, condition->macro, &value)))
	{
		switch (regexp_match_ex(&condition->regexps, value, condition->regexp, ZBX_CASE_SENSITIVE))
		{
			case ZBX_REGEXP_MATCH:
				ret = (CONDITION_OPERATOR_REGEXP == condition->op ? SUCCEED : FAIL);
				break;
			case ZBX_REGEXP_NO_MATCH:
				ret = (CONDITION_OPERATOR_NOT_REGEXP == condition->op ? SUCCEED : FAIL);
				break;
			default:
				ret = FAIL;
		}
	}

	zbx_free(value);

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: filter_evaluate_and_or                                           *
 *                                                                            *
 * Purpose: check if the lld data passes filter evaluation by and/or rule     *
 *                                                                            *
 * Parameters: filter          - [IN] the lld filter                          *
 *             jp_row          - [IN] the lld data row                        *
 *             lld_macro_paths - [IN] use json path to extract from jp_row    *
 *                                                                            *
 * Return value: SUCCEED - the lld data passed filter evaluation              *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	filter_evaluate_and_or(const lld_filter_t *filter, const struct zbx_json_parse *jp_row,
		const zbx_vector_ptr_t *lld_macro_paths)
{
	int	i, ret = SUCCEED, rc = SUCCEED;
	char	*lastmacro = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	for (i = 0; i < filter->conditions.values_num; i++)
	{
		const lld_condition_t	*condition = (lld_condition_t *)filter->conditions.values[i];

		rc = filter_condition_match(jp_row, lld_macro_paths, condition);
		/* check if a new condition group has started */
		if (NULL == lastmacro || 0 != strcmp(lastmacro, condition->macro))
		{
			/* if any of condition groups are false the evaluation returns false */
			if (FAIL == ret)
				break;

			ret = rc;
		}
		else
		{
			if (SUCCEED == rc)
				ret = rc;
		}

		lastmacro = condition->macro;
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: filter_evaluate_and                                              *
 *                                                                            *
 * Purpose: check if the lld data passes filter evaluation by and rule        *
 *                                                                            *
 * Parameters: filter          - [IN] the lld filter                          *
 *             jp_row          - [IN] the lld data row                        *
 *             lld_macro_paths - [IN] use json path to extract from jp_row    *
 *                                                                            *
 * Return value: SUCCEED - the lld data passed filter evaluation              *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	filter_evaluate_and(const lld_filter_t *filter, const struct zbx_json_parse *jp_row,
		const zbx_vector_ptr_t *lld_macro_paths)
{
	int	i, ret = SUCCEED;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	for (i = 0; i < filter->conditions.values_num; i++)
	{
		/* if any of conditions are false the evaluation returns false */
		if (SUCCEED != (ret = filter_condition_match(jp_row, lld_macro_paths,
				(lld_condition_t *)filter->conditions.values[i])))
		{
			break;
		}
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: filter_evaluate_or                                               *
 *                                                                            *
 * Purpose: check if the lld data passes filter evaluation by or rule         *
 *                                                                            *
 * Parameters: filter          - [IN] the lld filter                          *
 *             jp_row          - [IN] the lld data row                        *
 *             lld_macro_paths - [IN] use json path to extract from jp_row    *
 *                                                                            *
 * Return value: SUCCEED - the lld data passed filter evaluation              *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	filter_evaluate_or(const lld_filter_t *filter, const struct zbx_json_parse *jp_row,
		const zbx_vector_ptr_t *lld_macro_paths)
{
	int	i, ret = SUCCEED;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	for (i = 0; i < filter->conditions.values_num; i++)
	{
		/* if any of conditions are true the evaluation returns true */
		if (SUCCEED == (ret = filter_condition_match(jp_row, lld_macro_paths,
				(lld_condition_t *)filter->conditions.values[i])))
		{
			break;
		}
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: filter_evaluate_expression                                       *
 *                                                                            *
 * Purpose: check if the lld data passes filter evaluation by custom          *
 *          expression                                                        *
 *                                                                            *
 * Parameters: filter          - [IN] the lld filter                          *
 *             jp_row          - [IN] the lld data row                        *
 *             lld_macro_paths - [IN] use json path to extract from jp_row    *
 *                                                                            *
 * Return value: SUCCEED - the lld data passed filter evaluation              *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 * Comments: 1) replace {item_condition} references with action condition     *
 *              evaluation results (1 or 0)                                   *
 *           2) call evaluate() to calculate the final result                 *
 *                                                                            *
 ******************************************************************************/
static int	filter_evaluate_expression(const lld_filter_t *filter, const struct zbx_json_parse *jp_row,
		const zbx_vector_ptr_t *lld_macro_paths)
{
	int	i, ret = FAIL, id_len;
	char	*expression, id[ZBX_MAX_UINT64_LEN + 2], *p, error[256];
	double	result;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() expression:%s", __func__, filter->expression);

	expression = zbx_strdup(NULL, filter->expression);

	for (i = 0; i < filter->conditions.values_num; i++)
	{
		const lld_condition_t	*condition = (lld_condition_t *)filter->conditions.values[i];

		ret = filter_condition_match(jp_row, lld_macro_paths, condition);

		zbx_snprintf(id, sizeof(id), "{" ZBX_FS_UI64 "}", condition->id);

		id_len = strlen(id);
		p = expression;

		while (NULL != (p = strstr(p, id)))
		{
			*p = (SUCCEED == ret ? '1' : '0');
			memset(p + 1, ' ', id_len - 1);
			p += id_len;
		}
	}

	if (SUCCEED == evaluate(&result, expression, error, sizeof(error), NULL))
		ret = (SUCCEED != zbx_double_compare(result, 0) ? SUCCEED : FAIL);

	zbx_free(expression);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: filter_evaluate                                                  *
 *                                                                            *
 * Purpose: check if the lld data passes filter evaluation                    *
 *                                                                            *
 * Parameters: filter          - [IN] the lld filter                          *
 *             jp_row          - [IN] the lld data row                        *
 *             lld_macro_paths - [IN] use json path to extract from jp_row    *
 *                                                                            *
 * Return value: SUCCEED - the lld data passed filter evaluation              *
 *               FAIL    - otherwise                                          *
 *                                                                            *
 ******************************************************************************/
static int	filter_evaluate(const lld_filter_t *filter, const struct zbx_json_parse *jp_row,
		const zbx_vector_ptr_t *lld_macro_paths)
{
	switch (filter->evaltype)
	{
		case CONDITION_EVAL_TYPE_AND_OR:
			return filter_evaluate_and_or(filter, jp_row, lld_macro_paths);
		case CONDITION_EVAL_TYPE_AND:
			return filter_evaluate_and(filter, jp_row, lld_macro_paths);
		case CONDITION_EVAL_TYPE_OR:
			return filter_evaluate_or(filter, jp_row, lld_macro_paths);
		case CONDITION_EVAL_TYPE_EXPRESSION:
			return filter_evaluate_expression(filter, jp_row, lld_macro_paths);
	}

	return FAIL;
}

/******************************************************************************
 *                                                                            *
 * Function: lld_check_received_data_for_filter                               *
 *                                                                            *
 * Purpose: Check if the LLD data contains a values for macros used in filter.*
 *          Create an informative warning for every macro that has not        *
 *          received any value.                                               *
 *                                                                            *
 * Parameters: filter          - [IN] the lld filter                          *
 *             jp_row          - [IN] the lld data row                        *
 *             lld_macro_paths - [IN] use json path to extract from jp_row    *
 *             info            - [OUT] the warning description                *
 *                                                                            *
 ******************************************************************************/
static void	lld_check_received_data_for_filter(lld_filter_t *filter, const struct zbx_json_parse *jp_row,
		const zbx_vector_ptr_t *lld_macro_paths, char **info)
{
	int			i, index;
	zbx_lld_macro_path_t	lld_macro_path_local, *lld_macro_path;
	char			*output = NULL;

	for (i = 0; i < filter->conditions.values_num; i++)
	{
		const lld_condition_t	*condition = (lld_condition_t *)filter->conditions.values[i];

		lld_macro_path_local.lld_macro = condition->macro;

		if (FAIL != (index = zbx_vector_ptr_bsearch(lld_macro_paths, &lld_macro_path_local,
				zbx_lld_macro_paths_compare)))
		{
			lld_macro_path = (zbx_lld_macro_path_t *)lld_macro_paths->values[index];

			if (FAIL == zbx_jsonpath_query(jp_row, lld_macro_path->path, &output) || NULL == output)
			{
				*info = zbx_strdcatf(*info,
						"Cannot accurately apply filter: no value received for macro \"%s\""
						" json path '%s'.\n", lld_macro_path->lld_macro, lld_macro_path->path);
			}
			zbx_free(output);

			continue;
		}

		if (NULL == zbx_json_pair_by_name(jp_row, condition->macro))
		{
			*info = zbx_strdcatf(*info,
					"Cannot accurately apply filter: no value received for macro \"%s\".\n",
					condition->macro);
		}
	}
}

static int	lld_rows_get(const char *value, lld_filter_t *filter, zbx_vector_ptr_t *lld_rows,
		const zbx_vector_ptr_t *lld_macro_paths, char **info, char **error)
{
	struct zbx_json_parse	jp, jp_array, jp_row;
	const char		*p;
	zbx_lld_row_t		*lld_row;
	int			ret = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __func__);

	if (SUCCEED != zbx_json_open(value, &jp))
	{
		*error = zbx_dsprintf(*error, "Invalid discovery rule value: %s", zbx_json_strerror());
		goto out;
	}

	if ('[' == *jp.start)
	{
		jp_array = jp;
	}
	else if (SUCCEED != zbx_json_brackets_by_name(&jp, ZBX_PROTO_TAG_DATA, &jp_array))	/* deprecated */
	{
		*error = zbx_dsprintf(*error, "Cannot find the \"%s\" array in the received JSON object.",
				ZBX_PROTO_TAG_DATA);
		goto out;
	}

	p = NULL;
	while (NULL != (p = zbx_json_next(&jp_array, p)))
	{
		if (FAIL == zbx_json_brackets_open(p, &jp_row))
			continue;

		lld_check_received_data_for_filter(filter, &jp_row, lld_macro_paths, info);

		if (SUCCEED != filter_evaluate(filter, &jp_row, lld_macro_paths))
			continue;

		lld_row = (zbx_lld_row_t *)zbx_malloc(NULL, sizeof(zbx_lld_row_t));
		lld_row->jp_row = jp_row;
		zbx_vector_ptr_create(&lld_row->item_links);

		zbx_vector_ptr_append(lld_rows, lld_row);
	}

	ret = SUCCEED;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __func__, zbx_result_string(ret));

	return ret;
}

static void	lld_item_link_free(zbx_lld_item_link_t *item_link)
{
	zbx_free(item_link);
}

static void	lld_row_free(zbx_lld_row_t *lld_row)
{
	zbx_vector_ptr_clear_ext(&lld_row->item_links, (zbx_clean_func_t)lld_item_link_free);
	zbx_vector_ptr_destroy(&lld_row->item_links);
	zbx_free(lld_row);
}

/******************************************************************************
 *                                                                            *
 * Function: lld_process_discovery_rule                                       *
 *                                                                            *
 * Purpose: add or update items, triggers and graphs for discovery item       *
 *                                                                            *
 * Parameters: lld_ruleid - [IN] discovery item identifier from database      *
 *             value      - [IN] received value from agent                    *
 *             error      - [OUT] error or informational message. Will be set *
 *                               to empty string on successful discovery      *
 *                               without additional information.              *
 *                                                                            *
 ******************************************************************************/
int	lld_process_discovery_rule(zbx_uint64_t lld_ruleid, const char *value, char **error)
{
	DB_RESULT		result;
	DB_ROW			row;
	zbx_uint64_t		hostid;
	char			*discovery_key = NULL, *info = NULL;
	int			lifetime, ret = SUCCEED;
	zbx_vector_ptr_t	lld_rows, lld_macro_paths;
	lld_filter_t		filter;
	time_t			now;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() itemid:" ZBX_FS_UI64, __func__, lld_ruleid);

	zbx_vector_ptr_create(&lld_rows);
	zbx_vector_ptr_create(&lld_macro_paths);

	lld_filter_init(&filter);

	result = DBselect(
			"select hostid,key_,evaltype,formula,lifetime"
			" from items"
			" where itemid=" ZBX_FS_UI64,
			lld_ruleid);

	if (NULL != (row = DBfetch(result)))
	{
		char	*lifetime_str;

		ZBX_STR2UINT64(hostid, row[0]);
		discovery_key = zbx_strdup(discovery_key, row[1]);
		filter.evaltype = atoi(row[2]);
		filter.expression = zbx_strdup(NULL, row[3]);
		lifetime_str = zbx_strdup(NULL, row[4]);
		substitute_simple_macros(NULL, NULL, NULL, NULL, &hostid, NULL, NULL, NULL, NULL,
				&lifetime_str, MACRO_TYPE_COMMON, NULL, 0);

		if (SUCCEED != is_time_suffix(lifetime_str, &lifetime, ZBX_LENGTH_UNLIMITED))
		{
			zabbix_log(LOG_LEVEL_WARNING, "cannot process lost resources for the discovery rule \"%s:%s\":"
					" \"%s\" is not a valid value",
					zbx_host_string(hostid), discovery_key, lifetime_str);
			lifetime = 25 * SEC_PER_YEAR;	/* max value for the field */
		}

		zbx_free(lifetime_str);
	}
	DBfree_result(result);

	if (NULL == row)
	{
		zabbix_log(LOG_LEVEL_WARNING, "invalid discovery rule ID [" ZBX_FS_UI64 "]", lld_ruleid);
		goto out;
	}

	if (SUCCEED != lld_filter_load(&filter, lld_ruleid, error))
	{
		ret = FAIL;
		goto out;
	}

	if (SUCCEED != zbx_lld_macro_paths_get(lld_ruleid, &lld_macro_paths, error))
	{
		ret = FAIL;
		goto out;
	}

	if (SUCCEED != lld_rows_get(value, &filter, &lld_rows, &lld_macro_paths, &info, error))
	{
		ret = FAIL;
		goto out;
	}

	*error = zbx_strdup(*error, "");

	now = time(NULL);

	if (SUCCEED != lld_update_items(hostid, lld_ruleid, &lld_rows, &lld_macro_paths, error, lifetime, now))
	{
		zabbix_log(LOG_LEVEL_DEBUG, "cannot update/add items because parent host was removed while"
				" processing lld rule");
		goto out;
	}

	lld_item_links_sort(&lld_rows);

	if (SUCCEED != lld_update_triggers(hostid, lld_ruleid, &lld_rows, &lld_macro_paths, error))
	{
		zabbix_log(LOG_LEVEL_DEBUG, "cannot update/add triggers because parent host was removed while"
				" processing lld rule");
		goto out;
	}

	if (SUCCEED != lld_update_graphs(hostid, lld_ruleid, &lld_rows, &lld_macro_paths, error))
	{
		zabbix_log(LOG_LEVEL_DEBUG, "cannot update/add graphs because parent host was removed while"
				" processing lld rule");
		goto out;
	}

	lld_update_hosts(lld_ruleid, &lld_rows, &lld_macro_paths, error, lifetime, now);

	/* add informative warning to the error message about lack of data for macros used in filter */
	if (NULL != info)
		*error = zbx_strdcat(*error, info);
out:
	zbx_free(info);
	zbx_free(discovery_key);

	lld_filter_clean(&filter);

	zbx_vector_ptr_clear_ext(&lld_rows, (zbx_clean_func_t)lld_row_free);
	zbx_vector_ptr_destroy(&lld_rows);
	zbx_vector_ptr_clear_ext(&lld_macro_paths, (zbx_clean_func_t)zbx_lld_macro_path_free);
	zbx_vector_ptr_destroy(&lld_macro_paths);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __func__);

	return ret;
}
