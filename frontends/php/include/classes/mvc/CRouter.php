<?php declare(strict_types = 1);
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


class CRouter {
	/**
	 * Layout used for view rendering.
	 *
	 * @var string
	 */
	private $layout = null;

	/**
	 * Controller class for action handling.
	 *
	 * @var string
	 */
	private $controller = null;

	/**
	 * View used to generate HTML, CSV, JSON and other content.
	 *
	 * @var string
	 */
	private $view = null;

	/**
	 * Unique action (request) identifier.
	 *
	 * @var string
	 */
	private $action = null;

	/**
	 * Mapping between action and corresponding controller, layout and view.
	 *
	 * @var array
	 */
	private $routes = [
		// action							controller										layout					view
		'authentication.edit'			=> ['CControllerAuthenticationEdit',				'layout.htmlpage',		'administration.authentication.edit'],
		'authentication.update'			=> ['CControllerAuthenticationUpdate',				null,					null],
		'autoreg.edit'					=> ['CControllerAutoregEdit',						'layout.htmlpage',		'administration.autoreg.edit'],
		'autoreg.update'				=> ['CControllerAutoregUpdate',						null,					null],
		'dashboard.view'				=> ['CControllerDashboardView',						'layout.htmlpage',		'monitoring.dashboard.view'],
		'dashboard.list'				=> ['CControllerDashboardList',						'layout.htmlpage',		'monitoring.dashboard.list'],
		'dashboard.delete'				=> ['CControllerDashboardDelete',					null,					null],
		'dashboard.widget.edit'			=> ['CControllerDashboardWidgetEdit',				'layout.json',			'monitoring.dashboard.widget.edit'],
		'dashboard.widget.check'		=> ['CControllerDashboardWidgetCheck',				'layout.json',			null],
		'dashboard.widget.configure'	=> ['CControllerDashboardWidgetConfigure',			'layout.json',			null],
		'dashboard.widget.rfrate'		=> ['CControllerDashboardWidgetRfRate',				'layout.json',			null],
		'dashboard.properties.check'	=> ['CControllerDashboardPropertiesCheck',			'layout.json',			null],
		'dashboard.properties.edit'		=> ['CControllerDashboardPropertiesEdit',			'layout.json',			'dashboard.properties.edit'],
		'dashboard.share.edit'			=> ['CControllerDashboardShareEdit',				'layout.json',			'dashboard.sharing.edit'],
		'dashboard.share.update'		=> ['CControllerDashboardShareUpdate',				'layout.json',			null],
		'dashboard.update'				=> ['CControllerDashboardUpdate',					'layout.json',			null],
		'discovery.view'				=> ['CControllerDiscoveryView',						'layout.htmlpage',		'monitoring.discovery.view'],
		'export.hosts.xml'				=> ['CControllerExportXml',							'layout.xml',			null],
		'export.mediatypes.xml'			=> ['CControllerExportXml',							'layout.xml',			null],
		'export.screens.xml'			=> ['CControllerExportXml',							'layout.xml',			null],
		'export.sysmaps.xml'			=> ['CControllerExportXml',							'layout.xml',			null],
		'export.templates.xml'			=> ['CControllerExportXml',							'layout.xml',			null],
		'export.valuemaps.xml'			=> ['CControllerExportXml',							'layout.xml',			null],
		'favourite.create'				=> ['CControllerFavouriteCreate',					'layout.javascript',	null],
		'favourite.delete'				=> ['CControllerFavouriteDelete',					'layout.javascript',	null],
		'gui.edit'						=> ['CControllerGuiEdit',							'layout.htmlpage',		'administration.gui.edit'],
		'gui.update'					=> ['CControllerGuiUpdate',							null,					null],
		'housekeeping.edit'				=> ['CControllerHousekeepingEdit',					'layout.htmlpage',		'administration.housekeeping.edit'],
		'housekeeping.update'			=> ['CControllerHousekeepingUpdate',				null,					null],
		'iconmap.create'				=> ['CControllerIconMapCreate',						null,					null],
		'iconmap.delete'				=> ['CControllerIconMapDelete',						null,					null],
		'iconmap.edit'					=> ['CControllerIconMapEdit',						'layout.htmlpage',		'administration.iconmap.edit'],
		'iconmap.list'					=> ['CControllerIconMapList',						'layout.htmlpage',		'administration.iconmap.list'],
		'iconmap.update'				=> ['CControllerIconMapUpdate',						null,					null],
		'image.create'					=> ['CControllerImageCreate',						null,					null],
		'image.delete'					=> ['CControllerImageDelete',						null,					null],
		'image.edit'					=> ['CControllerImageEdit',							'layout.htmlpage',		'administration.image.edit'],
		'image.list'					=> ['CControllerImageList',							'layout.htmlpage',		'administration.image.list'],
		'image.update'					=> ['CControllerImageUpdate',						null,					null],
		'macros.edit'					=> ['CControllerMacrosEdit',						'layout.htmlpage',		'administration.macros.edit'],
		'macros.update'					=> ['CControllerMacrosUpdate',						null,					null],
		'hostmacros.list'				=> ['CControllerHostMacrosList',					'layout.json',			'hostmacros.list'],
		'latest.view'					=> ['CControllerLatestView',						'layout.htmlpage',		'monitoring.latest.view'],
		'latest.view.refresh'			=> ['CControllerLatestViewRefresh',					'layout.json',			'monitoring.latest.view.refresh'],
		'map.view'						=> ['CControllerMapView',							'layout.htmlpage',		'monitoring.map.view'],
		'mediatype.create'				=> ['CControllerMediatypeCreate',					null,					null],
		'mediatype.delete'				=> ['CControllerMediatypeDelete',					null,					null],
		'mediatype.disable'				=> ['CControllerMediatypeDisable',					null,					null],
		'mediatype.edit'				=> ['CControllerMediatypeEdit',						'layout.htmlpage',		'administration.mediatype.edit'],
		'mediatype.enable'				=> ['CControllerMediatypeEnable',					null,					null],
		'mediatype.list'				=> ['CControllerMediatypeList',						'layout.htmlpage',		'administration.mediatype.list'],
		'mediatype.update'				=> ['CControllerMediatypeUpdate',					null,					null],
		'menu.popup'					=> ['CControllerMenuPopup',							'layout.json',			null],
		'miscconfig.edit'				=> ['CControllerMiscConfigEdit',					'layout.htmlpage',		'administration.miscconfig.edit'],
		'miscconfig.update'				=> ['CControllerMiscConfigUpdate',					null,					null],
		'notifications.get'				=> ['CControllerNotificationsGet',					'layout.json',			null],
		'notifications.mute'			=> ['CControllerNotificationsMute',					'layout.json',			null],
		'notifications.read'			=> ['CControllerNotificationsRead',					'layout.json',			null],
		'popup.acknowledge.edit'		=> ['CControllerPopupAcknowledgeEdit',				'layout.json',			'popup.acknowledge.edit'],
		'popup.acknowledge.create'		=> ['CControllerPopupAcknowledgeCreate',			'layout.json',			null],
		'popup.generic'					=> ['CControllerPopupGeneric',						'layout.json',			'popup.generic'],
		'popup.httpstep'				=> ['CControllerPopupHttpStep',						'layout.json',			'popup.httpstep'],
		'popup.media'					=> ['CControllerPopupMedia',						'layout.json',			'popup.media'],
		'popup.preproctest.edit'		=> ['CControllerPopupPreprocTestEdit',				'layout.json',			'popup.preproctestedit.view'],
		'popup.preproctest.send'		=> ['CControllerPopupPreprocTestSend',				'layout.json',			null],
		'popup.scriptexec'				=> ['CControllerPopupScriptExec',					'layout.json',			'popup.scriptexec'],
		'popup.triggerexpr'				=> ['CControllerPopupTriggerExpr',					'layout.json',			'popup.triggerexpr'],
		'popup.services'				=> ['CControllerPopupServices',						'layout.json',			'popup.services'],
		'popup.testtriggerexpr'			=> ['CControllerPopupTestTriggerExpr',				'layout.json',			'popup.testtriggerexpr'],
		'popup.triggerwizard'			=> ['CControllerPopupTriggerWizard',				'layout.json',			'popup.triggerwizard'],
		'popup.mediatypetest.edit'		=> ['CControllerPopupMediatypeTestEdit',			'layout.json',			'popup.mediatypetest.edit'],
		'popup.mediatypetest.send'		=> ['CControllerPopupMediatypeTestSend',			'layout.json',			null],
		'problem.view'					=> ['CControllerProblemView',						'layout.htmlpage',		'monitoring.problem.view'],
		'problem.view.csv'				=> ['CControllerProblemView',						'layout.csv',			'monitoring.problem.view'],
		'profile.update'				=> ['CControllerProfileUpdate',						'layout.json',			null],
		'proxy.create'					=> ['CControllerProxyCreate',						null,					null],
		'proxy.delete'					=> ['CControllerProxyDelete',						null,					null],
		'proxy.edit'					=> ['CControllerProxyEdit',							'layout.htmlpage',		'administration.proxy.edit'],
		'proxy.hostdisable'				=> ['CControllerProxyHostDisable',					null,					null],
		'proxy.hostenable'				=> ['CControllerProxyHostEnable',					null,					null],
		'proxy.list'					=> ['CControllerProxyList',							'layout.htmlpage',		'administration.proxy.list'],
		'proxy.update'					=> ['CControllerProxyUpdate',						null,					null],
		'regex.create'					=> ['CControllerRegExCreate',						null,					null],
		'regex.delete'					=> ['CControllerRegExDelete',						null,					null],
		'regex.edit'					=> ['CControllerRegExEdit',							'layout.htmlpage',		'administration.regex.edit'],
		'regex.list'					=> ['CControllerRegExList',							'layout.htmlpage',		'administration.regex.list'],
		'regex.test'					=> ['CControllerRegExTest',							null,					null],
		'regex.update'					=> ['CControllerRegExUpdate',						null,					null],
		'report.services'				=> ['CControllerReportServices',					'layout.htmlpage',		'report.services'],
		'report.status'					=> ['CControllerReportStatus',						'layout.htmlpage',		'report.status'],
		'script.create'					=> ['CControllerScriptCreate',						null,					null],
		'script.delete'					=> ['CControllerScriptDelete',						null,					null],
		'script.edit'					=> ['CControllerScriptEdit',						'layout.htmlpage',		'administration.script.edit'],
		'script.list'					=> ['CControllerScriptList',						'layout.htmlpage',		'administration.script.list'],
		'script.update'					=> ['CControllerScriptUpdate',						null,					null],
		'search'						=> ['CControllerSearch',							'layout.htmlpage',		'search'],
		'system.warning'				=> ['CControllerSystemWarning',						'layout.warning',		'system.warning'],
		'timeselector.update'			=> ['CControllerTimeSelectorUpdate',				'layout.json',			null],
		'trigdisplay.edit'				=> ['CControllerTrigDisplayEdit',					'layout.htmlpage',		'administration.trigdisplay.edit'],
		'trigdisplay.reset'				=> ['CControllerTrigDisplayReset',					null,					null],
		'trigdisplay.update'			=> ['CControllerTrigDisplayUpdate',					null,					null],
		'trigseverity.edit'				=> ['CControllerTrigSeverityEdit',					'layout.htmlpage',		'administration.trigseverity.edit'],
		'trigseverity.reset'			=> ['CControllerTrigSeverityReset',					null,					null],
		'trigseverity.update'			=> ['CControllerTrigSeverityUpdate',				null,					null],
		'user.create'					=> ['CControllerUserCreate',						null,					null],
		'user.delete'					=> ['CControllerUserDelete',						null,					null],
		'user.edit'						=> ['CControllerUserEdit',							'layout.htmlpage',		'administration.user.edit'],
		'user.list'						=> ['CControllerUserList',							'layout.htmlpage',		'administration.user.list'],
		'user.unblock'					=> ['CControllerUserUnblock',						null,					null],
		'user.update'					=> ['CControllerUserUpdate',						null,					null],
		'usergroup.create'				=> ['CControllerUsergroupCreate',					null,					null],
		'usergroup.delete'				=> ['CControllerUsergroupDelete',					null,					null],
		'usergroup.edit'				=> ['CControllerUsergroupEdit',						'layout.htmlpage',		'administration.usergroup.edit'],
		'usergroup.groupright.add'		=> ['CControllerUsergroupGrouprightAdd',			'layout.json',			'administration.usergroup.grouprights'],
		'usergroup.list'				=> ['CControllerUsergroupList',						'layout.htmlpage',		'administration.usergroup.list'],
		'usergroup.massupdate'			=> ['CControllerUsergroupMassUpdate',				null,					null],
		'usergroup.tagfilter.add'		=> ['CControllerUsergroupTagfilterAdd',				'layout.json',			'administration.usergroup.tagfilters'],
		'usergroup.update'				=> ['CControllerUsergroupUpdate',					null,					null],
		'userprofile.edit'				=> ['CControllerUserProfileEdit',					'layout.htmlpage',		'administration.user.edit'],
		'userprofile.update'			=> ['CControllerUserProfileUpdate',					null,					null],
		'valuemap.create'				=> ['CControllerValuemapCreate',					null,					null],
		'valuemap.delete'				=> ['CControllerValuemapDelete',					null,					null],
		'valuemap.edit'					=> ['CControllerValuemapEdit',						'layout.htmlpage',		'administration.valuemap.edit'],
		'valuemap.list'					=> ['CControllerValuemapList',						'layout.htmlpage',		'administration.valuemap.list'],
		'valuemap.update'				=> ['CControllerValuemapUpdate',					null,					null],
		'web.view'						=> ['CControllerWebView',							'layout.htmlpage',		'monitoring.web.view'],
		'widget.favgraphs.view'			=> ['CControllerWidgetFavGraphsView',				'layout.widget',		'monitoring.widget.favgraphs.view'],
		'widget.favmaps.view'			=> ['CControllerWidgetFavMapsView',					'layout.widget',		'monitoring.widget.favmaps.view'],
		'widget.favscreens.view'		=> ['CControllerWidgetFavScreensView',				'layout.widget',		'monitoring.widget.favscreens.view'],
		'widget.discovery.view'			=> ['CControllerWidgetDiscoveryView',				'layout.widget',		'monitoring.widget.discovery.view'],
		'widget.graph.view'				=> ['CControllerWidgetGraphView',					'layout.widget',		'monitoring.widget.graph.view'],
		'widget.graphprototype.view'	=> ['CControllerWidgetIteratorGraphPrototypeView',	'layout.json',			null],
		'widget.problemhosts.view'		=> ['CControllerWidgetProblemHostsView',			'layout.widget',		'monitoring.widget.problemhosts.view'],
		'widget.problems.view'			=> ['CControllerWidgetProblemsView',				'layout.widget',		'monitoring.widget.problems.view'],
		'widget.systeminfo.view'		=> ['CControllerWidgetSystemInfoView',				'layout.widget',		'monitoring.widget.systeminfo.view'],
		'widget.svggraph.view'			=> ['CControllerWidgetSvgGraphView',				'layout.widget',		'monitoring.widget.svggraph.view'],
		'widget.problemsbysv.view'		=> ['CControllerWidgetProblemsBySvView',			'layout.widget',		'monitoring.widget.problemsbysv.view'],
		'widget.web.view'				=> ['CControllerWidgetWebView',						'layout.widget',		'monitoring.widget.web.view'],
		'widget.clock.view'				=> ['CControllerWidgetClockView',					'layout.widget',		'monitoring.widget.clock.view'],
		'widget.map.view'				=> ['CControllerWidgetMapView',						'layout.widget',		'monitoring.widget.map.view'],
		'widget.navtree.view'			=> ['CControllerWidgetNavTreeView',					'layout.widget',		'monitoring.widget.navtree.view'],
		'widget.navtree.item.edit'		=> ['CControllerWidgetNavTreeItemEdit',				'layout.json',			null],
		'widget.navtree.item.update'	=> ['CControllerWidgetNavTreeItemUpdate',			'layout.json',			null],
		'widget.actionlog.view'			=> ['CControllerWidgetActionLogView',				'layout.widget',		'monitoring.widget.actionlog.view'],
		'widget.dataover.view'			=> ['CControllerWidgetDataOverView',				'layout.widget',		'monitoring.widget.dataover.view'],
		'widget.trigover.view'			=> ['CControllerWidgetTrigOverView',				'layout.widget',		'monitoring.widget.trigover.view'],
		'widget.url.view'				=> ['CControllerWidgetUrlView',						'layout.widget',		'monitoring.widget.url.view'],
		'widget.plaintext.view'			=> ['CControllerWidgetPlainTextView',				'layout.widget',		'monitoring.widget.plaintext.view'],
		'widget.hostavail.view'			=> ['CControllerWidgetHostAvailView',				'layout.widget',		'monitoring.widget.hostavail.view'],
		'workingtime.edit'				=> ['CControllerWorkingTimeEdit',					'layout.htmlpage',		'administration.workingtime.edit'],
		'workingtime.update'			=> ['CControllerWorkingTimeUpdate',					null,					null],

		// legacy actions
		'actionconf.php'				=> ['CLegacyAction', null, null],
		'applications.php'				=> ['CLegacyAction', null, null],
		'auditacts.php'					=> ['CLegacyAction', null, null],
		'auditlogs.php'					=> ['CLegacyAction', null, null],
		'browserwarning.php'			=> ['CLegacyAction', null, null],
		'chart.php'						=> ['CLegacyAction', null, null],
		'chart2.php'					=> ['CLegacyAction', null, null],
		'chart3.php'					=> ['CLegacyAction', null, null],
		'chart4.php'					=> ['CLegacyAction', null, null],
		'chart5.php'					=> ['CLegacyAction', null, null],
		'chart6.php'					=> ['CLegacyAction', null, null],
		'chart7.php'					=> ['CLegacyAction', null, null],
		'charts.php'					=> ['CLegacyAction', null, null],
		'conf.import.php'				=> ['CLegacyAction', null, null],
		'correlation.php'				=> ['CLegacyAction', null, null],
		'disc_prototypes.php'			=> ['CLegacyAction', null, null],
		'discoveryconf.php'				=> ['CLegacyAction', null, null],
		'graphs.php'					=> ['CLegacyAction', null, null],
		'history.php'					=> ['CLegacyAction', null, null],
		'host_discovery.php'			=> ['CLegacyAction', null, null],
		'host_prototypes.php'			=> ['CLegacyAction', null, null],
		'host_screen.php'				=> ['CLegacyAction', null, null],
		'hostgroups.php'				=> ['CLegacyAction', null, null],
		'hostinventories.php'			=> ['CLegacyAction', null, null],
		'hostinventoriesoverview.php'	=> ['CLegacyAction', null, null],
		'hosts.php'						=> ['CLegacyAction', null, null],
		'httpconf.php'					=> ['CLegacyAction', null, null],
		'httpdetails.php'				=> ['CLegacyAction', null, null],
		'image.php'						=> ['CLegacyAction', null, null],
		'imgstore.php'					=> ['CLegacyAction', null, null],
		'index.php'						=> ['CLegacyAction', null, null],
		'index_http.php'				=> ['CLegacyAction', null, null],
		'items.php'						=> ['CLegacyAction', null, null],
		'jsrpc.php'						=> ['CLegacyAction', null, null],
		'maintenance.php'				=> ['CLegacyAction', null, null],
		'map.import.php'				=> ['CLegacyAction', null, null],
		'map.php'						=> ['CLegacyAction', null, null],
		'overview.php'					=> ['CLegacyAction', null, null],
		'queue.php'						=> ['CLegacyAction', null, null],
		'report2.php'					=> ['CLegacyAction', null, null],
		'report4.php'					=> ['CLegacyAction', null, null],
		'screen.import.php'				=> ['CLegacyAction', null, null],
		'screenconf.php'				=> ['CLegacyAction', null, null],
		'screenedit.php'				=> ['CLegacyAction', null, null],
		'screens.php'					=> ['CLegacyAction', null, null],
		'services.php'					=> ['CLegacyAction', null, null],
		'slideconf.php'					=> ['CLegacyAction', null, null],
		'slides.php'					=> ['CLegacyAction', null, null],
		'srv_status.php'				=> ['CLegacyAction', null, null],
		'sysmap.php'					=> ['CLegacyAction', null, null],
		'sysmaps.php'					=> ['CLegacyAction', null, null],
		'templates.php' 				=> ['CLegacyAction', null, null],
		'toptriggers.php'				=> ['CLegacyAction', null, null],
		'tr_events.php'					=> ['CLegacyAction', null, null],
		'trigger_prototypes.php'		=> ['CLegacyAction', null, null],
		'triggers.php'					=> ['CLegacyAction', null, null]
	];

	/**
	 * Add new actions (potentially overwritting the existing ones).
	 *
	 * @param array  $actions                           List of actions.
	 * @param string $actions['action_name']            Definition of the 'action_name' action.
	 * @param string $actions['action_name']['class']   Controller class name of the 'action_name' action.
	 * @param string $actions['action_name']['layout']  Optional layout of the 'action_name' action.
	 * @param string $actions['action_name']['view']    Optional view of the 'action_name' action.
	 */
	public function addActions(array $actions): void {
		foreach ($actions as $action => $route) {
			if (is_array($route) && array_key_exists('class', $route)) {
				$this->routes[$action] = [
					$route['class'],
					array_key_exists('layout', $route) ? $route['layout'] : null,
					array_key_exists('view', $route) ? $route['view'] : null
				];
			}
		}
	}

	/**
	 * Set controller, layout and view associated with the specified action.
	 *
	 * @param string $action  Action name.
	 */
	public function setAction(string $action): void {
		$this->action = $action;

		if (array_key_exists($action, $this->routes)) {
			[$this->controller, $this->layout, $this->view] = $this->routes[$action];
		}
		else {
			$this->controller = null;
			$this->layout = null;
			$this->view = null;
		}
	}

	/**
	 * Returns layout name.
	 *
	 * @return string|null
	 */
	public function getLayout(): ?string {
		return $this->layout;
	}

	/**
	 * Returns controller name.
	 *
	 * @return string|null
	 */
	public function getController(): ?string {
		return $this->controller;
	}

	/**
	 * Returns view name.
	 *
	 * @return string|null
	 */
	public function getView(): ?string {
		return $this->view;
	}

	/**
	 * Returns action name.
	 *
	 * @return string|null
	 */
	public function getAction(): ?string {
		return $this->action;
	}
}
