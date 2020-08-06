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

#define exit	__real_exit
#include <stdlib.h>
#undef exit

#include "zbxmocktest.h"
#include "zbxmockdata.h"

#include "common.h"

void	__wrap_exit(int status);

void	__wrap_exit(int status)
{
	zbx_mock_error_t	error;
	int			expected_status;

	if (ZBX_MOCK_NO_EXIT_CODE == (error = zbx_mock_exit_code(&expected_status)))
		fail_msg("exit() was not expected, exitcode: %d.", status);

	if (ZBX_MOCK_SUCCESS != error)
		fail_msg("Cannot get exit code from test case data: %s", zbx_mock_error_string(error));

	switch (status)
	{
		case EXIT_SUCCESS:
		case EXIT_FAILURE:
			if (status != expected_status)
				fail_msg("exit() called with status %d, expected %d.", status, expected_status);
			__real_exit(EXIT_SUCCESS);
		default:
			fail_msg("exit() called with status %d that is neither EXIT_SUCCESS nor EXIT_FAILURE.", status);
	}
}
