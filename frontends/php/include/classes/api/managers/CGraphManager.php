<?php
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


/**
 * Class to perform low level graph related actions.
 */
class CGraphManager {

	/**
	 * Deletes graphs and related entities without permission check.
	 *
	 * @param array $graphids
	 */
	public static function delete(array $graphids) {
		$del_graphids = [];

		// Selecting all inherited graphs.
		$parent_graphids = array_flip($graphids);
		do {
			$db_graphs = DBselect(
				'SELECT g.graphid FROM graphs g WHERE '.dbConditionInt('g.templateid', array_keys($parent_graphids))
			);

			$del_graphids += $parent_graphids;
			$parent_graphids = [];

			while ($db_graph = DBfetch($db_graphs)) {
				if (!array_key_exists($db_graph['graphid'], $del_graphids)) {
					$parent_graphids[$db_graph['graphid']] = true;
				}
			}
		} while ($parent_graphids);

		$del_graphids = array_keys($del_graphids);

		DB::delete('screens_items', [
			'resourceid' => $del_graphids,
			'resourcetype' => SCREEN_RESOURCE_GRAPH
		]);

		DB::delete('profiles', [
			'idx' => 'web.favorite.graphids',
			'source' => 'graphid',
			'value_id' => $del_graphids
		]);

		DB::delete('profiles', [
			'idx' => 'web.latest.graphid',
			'value_id' => $del_graphids
		]);

		DB::delete('widget_field', ['value_graphid' => $del_graphids]);
		DB::delete('graph_discovery', ['graphid' => $del_graphids]);
		DB::delete('graphs_items', ['graphid' => $del_graphids]);
		DB::delete('graphs', ['graphid' => $del_graphids]);
	}
}
