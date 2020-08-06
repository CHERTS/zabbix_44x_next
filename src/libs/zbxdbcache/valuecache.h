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

#ifndef ZABBIX_VALUECACHE_H
#define ZABBIX_VALUECACHE_H

#include "zbxtypes.h"
#include "zbxalgo.h"
#include "zbxhistory.h"

/*
 * The Value Cache provides read caching of item historical data residing in history
 * tables. No components must read history tables manually. Instead all history data
 * must be read from the Value Cache.
 *
 * Usage notes:
 *
 * Initialization
 *
 *   The value cache must be initialized at the start of the program with zbx_vc_init()
 *   function. To ensure proper removal of shared memory the value cache must be destroyed
 *   upon a program exit with zbx_vc_destroy() function.
 *
 * Adding data
 *
 *   Whenever a new item value is added to system (history tables) the item value must be
 *   also added added to Value Cache with zbx_dc_add_value() function to keep it up to date.
 *
 * Retrieving data
 *
 *   The history data is accessed with zbx_vc_get_values() and zbx_vc_get_value()
 *   functions. Afterwards the retrieved history data must be freed by the caller by using
 *   either zbx_history_record_vector_destroy() function (free the zbx_vc_get_values()
 *   call output) or zbx_history_record_clear() function (free the zbx_vc_get_value() call output).
 *
 * Locking
 *
 *   The cache ensures synchronization between processes by using automatic locks whenever
 *   a cache function (zbx_vc_*) is called and by providing manual cache locking functionality
 *   with zbx_vc_lock()/zbx_vc_unlock() functions.
 *
 */

#define ZBX_VC_MODE_NORMAL	0
#define ZBX_VC_MODE_LOWMEM	1

/* indicates that all values from database are cached */
#define ZBX_ITEM_STATUS_CACHED_ALL	1

/* the cache statistics */
typedef struct
{
	/* Value cache misses are new values cached during request and hits are calculated by  */
	/* subtracting misses from the total number of values returned (0 if the number of     */
	/* returned values is less than misses.                                                */
	/* When performing count based requests the number of cached values might be greater   */
	/* than number of returned values. This can skew the hits/misses ratio towards misses. */
	zbx_uint64_t	hits;
	zbx_uint64_t	misses;

	zbx_uint64_t	total_size;
	zbx_uint64_t	free_size;

	/* value cache operating mode - see ZBX_VC_MODE_* defines */
	int		mode;
}
zbx_vc_stats_t;

int	zbx_vc_init(char **error);

void	zbx_vc_destroy(void);

void	zbx_vc_reset(void);

void	zbx_vc_lock(void);

void	zbx_vc_unlock(void);

void	zbx_vc_enable(void);

void	zbx_vc_disable(void);

int	zbx_vc_get_values(zbx_uint64_t itemid, int value_type, zbx_vector_history_record_t *values, int seconds,
		int count, const zbx_timespec_t *ts);

int	zbx_vc_get_value(zbx_uint64_t itemid, int value_type, const zbx_timespec_t *ts, zbx_history_record_t *value);

int	zbx_vc_add_values(zbx_vector_ptr_t *history);

int	zbx_vc_get_statistics(zbx_vc_stats_t *stats);

void	zbx_vc_housekeeping_value_cache(void);

#endif	/* ZABBIX_VALUECACHE_H */
