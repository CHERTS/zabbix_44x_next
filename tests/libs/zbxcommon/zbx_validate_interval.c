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

#include "zbxmocktest.h"
#include "zbxmockutil.h"
#include "zbxmockassert.h"

void	zbx_mock_test_entry(void **state)
{
	const char		*str;
	char			*error = NULL;
	int			value, expected_ret, ret;
	zbx_custom_interval_t	*custom_intervals;

	ZBX_UNUSED(state);

	str = zbx_mock_get_parameter_string("in.str");
	expected_ret = zbx_mock_str_to_return_code(zbx_mock_get_parameter_string("out.return"));

	if (FAIL == (ret = zbx_validate_interval(str, &error)))
		zbx_free(error);

	zbx_mock_assert_int_eq("return value", expected_ret, ret);

	expected_ret = NULL == strstr(str, "{$") ? expected_ret : FAIL;

	if (SUCCEED == (ret = zbx_interval_preproc(str, &value, &custom_intervals, NULL)))
		zbx_custom_interval_free(custom_intervals);

	zbx_mock_assert_int_eq("return value", expected_ret, ret);


}
