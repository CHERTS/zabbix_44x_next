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

require_once dirname(__FILE__).'/testGeneric.php';
require_once dirname(__FILE__).'/testPageDashboard.php';
require_once dirname(__FILE__).'/testPageDashboardWidgets.php';
require_once dirname(__FILE__).'/testPageOverview.php';
require_once dirname(__FILE__).'/testPageLatestData.php';
require_once dirname(__FILE__).'/testPageWeb.php';
require_once dirname(__FILE__).'/testPageProblems.php';
require_once dirname(__FILE__).'/testPageScreens.php';
require_once dirname(__FILE__).'/testPageActions.php';
require_once dirname(__FILE__).'/testPageAdministrationDMProxies.php';
require_once dirname(__FILE__).'/testFormAdministrationGeneralAutoRegistration.php';
require_once dirname(__FILE__).'/testPageAdministrationGeneralIconMapping.php';
require_once dirname(__FILE__).'/testPageAdministrationGeneralImages.php';
require_once dirname(__FILE__).'/testPageAdministrationGeneralRegexp.php';
require_once dirname(__FILE__).'/testPageAdministrationGeneralValuemap.php';
require_once dirname(__FILE__).'/testPageAdministrationMediaTypes.php';
require_once dirname(__FILE__).'/testPageAdministrationScripts.php';
require_once dirname(__FILE__).'/testPageAvailabilityReport.php';
require_once dirname(__FILE__).'/testPageEventCorrelation.php';
require_once dirname(__FILE__).'/testPageGraphPrototypes.php';
require_once dirname(__FILE__).'/testPageHistory.php';
require_once dirname(__FILE__).'/testPageHostGraph.php';
require_once dirname(__FILE__).'/testPageHostPrototypes.php';
require_once dirname(__FILE__).'/testPageHosts.php';
require_once dirname(__FILE__).'/testPageInventory.php';
require_once dirname(__FILE__).'/testPageItems.php';
require_once dirname(__FILE__).'/testPageItemPrototypes.php';
require_once dirname(__FILE__).'/testPageTriggers.php';
require_once dirname(__FILE__).'/testPageTriggerUrl.php';
require_once dirname(__FILE__).'/testPageTriggerPrototypes.php';
require_once dirname(__FILE__).'/testPageMaintenance.php';
require_once dirname(__FILE__).'/testPageMaps.php';
require_once dirname(__FILE__).'/testPageNetworkDiscovery.php';
require_once dirname(__FILE__).'/testPageLowLevelDiscovery.php';
/*
require_once dirname(__FILE__).'/testPageQueueDetails.php';
require_once dirname(__FILE__).'/testPageQueueOverview.php';
require_once dirname(__FILE__).'/testPageQueueOverviewByProxy.php';
*/
require_once dirname(__FILE__).'/testPageReportsActionLog.php';
require_once dirname(__FILE__).'/testPageReportsAudit.php';
require_once dirname(__FILE__).'/testPageReportsNotifications.php';
require_once dirname(__FILE__).'/testPageReportsTriggerTop.php';
require_once dirname(__FILE__).'/testPageSearch.php';
require_once dirname(__FILE__).'/testPageSlideShows.php';
require_once dirname(__FILE__).'/testPageStatusOfZabbix.php';
require_once dirname(__FILE__).'/testPageTemplates.php';
require_once dirname(__FILE__).'/testPageUserGroups.php';
require_once dirname(__FILE__).'/testPageUsers.php';
require_once dirname(__FILE__).'/dashboard/testProblemsBySeverityWidget.php';
require_once dirname(__FILE__).'/dashboard/testHostAvailabilityWidget.php';
require_once dirname(__FILE__).'/testFormAction.php';
require_once dirname(__FILE__).'/testFormAdministrationAuthenticationHttp.php';
require_once dirname(__FILE__).'/testFormAdministrationAuthenticationLdap.php';
require_once dirname(__FILE__).'/testFormAdministrationDMProxies.php';
require_once dirname(__FILE__).'/testFormAdministrationGeneralGUI.php';
require_once dirname(__FILE__).'/testFormAdministrationGeneralHousekeeper.php';
require_once dirname(__FILE__).'/testFormAdministrationGeneralIconMapping.php';
//require_once dirname(__FILE__).'/testFormAdministrationGeneralImages.php';
require_once dirname(__FILE__).'/testFormAdministrationGeneralMacro.php';
require_once dirname(__FILE__).'/testFormAdministrationGeneralOtherParams.php';
require_once dirname(__FILE__).'/testFormAdministrationGeneralRegexp.php';
require_once dirname(__FILE__).'/testFormAdministrationGeneralTrigDisplOptions.php';
require_once dirname(__FILE__).'/testFormAdministrationGeneralValuemap.php';
require_once dirname(__FILE__).'/testFormAdministrationGeneralWorkperiod.php';
require_once dirname(__FILE__).'/testFormAdministrationGeneralInstallation.php';
require_once dirname(__FILE__).'/testFormAdministrationMediaTypes.php';
require_once dirname(__FILE__).'/testFormAdministrationMediaTypeWebhook.php';
require_once dirname(__FILE__).'/testFormAdministrationScripts.php';
require_once dirname(__FILE__).'/testFormAdministrationUserGroups.php';
require_once dirname(__FILE__).'/testFormConfigTriggerSeverity.php';
require_once dirname(__FILE__).'/testFormEventCorrelation.php';
require_once dirname(__FILE__).'/testFormGraph.php';
require_once dirname(__FILE__).'/testFormGraphPrototype.php';
require_once dirname(__FILE__).'/testFormHost.php';
require_once dirname(__FILE__).'/testFormHostGroup.php';
require_once dirname(__FILE__).'/testFormHostMacros.php';
require_once dirname(__FILE__).'/testFormHostPrototype.php';
require_once dirname(__FILE__).'/testFormHostTags.php';
require_once dirname(__FILE__).'/testFormItem.php';
require_once dirname(__FILE__).'/testFormItemHttpAgent.php';
require_once dirname(__FILE__).'/testFormItemPreprocessing.php';
require_once dirname(__FILE__).'/testFormItemPreprocessingTest.php';
require_once dirname(__FILE__).'/testFormItemPrototype.php';
require_once dirname(__FILE__).'/testFormItemPrototypePreprocessing.php';
require_once dirname(__FILE__).'/testFormLogin.php';
require_once dirname(__FILE__).'/testFormLowLevelDiscovery.php';
require_once dirname(__FILE__).'/testFormLowLevelDiscoveryPreprocessing.php';
require_once dirname(__FILE__).'/testFormMaintenance.php';
require_once dirname(__FILE__).'/testFormMap.php';
require_once dirname(__FILE__).'/testFormNetworkDiscovery.php';
require_once dirname(__FILE__).'/testFormScreen.php';
require_once dirname(__FILE__).'/testFormSysmap.php';
require_once dirname(__FILE__).'/testFormTrigger.php';
require_once dirname(__FILE__).'/testFormTriggerTags.php';
require_once dirname(__FILE__).'/testFormTemplate.php';
require_once dirname(__FILE__).'/testFormTemplateMacros.php';
require_once dirname(__FILE__).'/testFormTemplateTags.php';
require_once dirname(__FILE__).'/testFormTriggerPrototype.php';
require_once dirname(__FILE__).'/testFormUser.php';
require_once dirname(__FILE__).'/testFormUserProfile.php';
require_once dirname(__FILE__).'/testFormWeb.php';
require_once dirname(__FILE__).'/testFormWebStep.php';
require_once dirname(__FILE__).'/testFormApplication.php';
require_once dirname(__FILE__).'/testPageApplications.php';
require_once dirname(__FILE__).'/testPageBrowserWarning.php';
require_once dirname(__FILE__).'/testInheritanceApplication.php';
require_once dirname(__FILE__).'/testInheritanceItem.php';
require_once dirname(__FILE__).'/testInheritanceTrigger.php';
require_once dirname(__FILE__).'/testInheritanceGraph.php';
require_once dirname(__FILE__).'/testInheritanceWeb.php';
require_once dirname(__FILE__).'/testInheritanceDiscoveryRule.php';
require_once dirname(__FILE__).'/testInheritanceItemPrototype.php';
require_once dirname(__FILE__).'/testInheritanceTriggerPrototype.php';
require_once dirname(__FILE__).'/testInheritanceGraphPrototype.php';
require_once dirname(__FILE__).'/testInheritanceHostPrototype.php';
require_once dirname(__FILE__).'/testMultiselect.php';
require_once dirname(__FILE__).'/testTagBasedPermissions.php';
require_once dirname(__FILE__).'/testTemplateInheritance.php';
require_once dirname(__FILE__).'/testTriggerDependencies.php';
require_once dirname(__FILE__).'/testTriggerExpressions.php';
require_once dirname(__FILE__).'/testUrlParameters.php';
require_once dirname(__FILE__).'/testUrlUserPermissions.php';
require_once dirname(__FILE__).'/testZBX6339.php';
require_once dirname(__FILE__).'/testZBX6648.php';
require_once dirname(__FILE__).'/testZBX6663.php';
require_once dirname(__FILE__).'/dashboard/testGraphWidget.php';

class SeleniumTests {
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('selenium');

		$suite->addTestSuite('testGeneric');
		$suite->addTestSuite('testPageActions');
		$suite->addTestSuite('testPageAdministrationDMProxies');
		$suite->addTestSuite('testFormAdministrationGeneralAutoRegistration');
		$suite->addTestSuite('testPageAdministrationGeneralIconMapping');
		$suite->addTestSuite('testPageAdministrationGeneralImages');
		$suite->addTestSuite('testPageAdministrationGeneralRegexp');
		$suite->addTestSuite('testPageAdministrationGeneralValuemap');
		$suite->addTestSuite('testPageAdministrationMediaTypes');
		$suite->addTestSuite('testPageAdministrationScripts');
		$suite->addTestSuite('testPageAvailabilityReport');
		$suite->addTestSuite('testPageDashboard');
		$suite->addTestSuite('testPageDashboardWidgets');
		$suite->addTestSuite('testPageEventCorrelation');
		$suite->addTestSuite('testPageGraphPrototypes');
		$suite->addTestSuite('testPageProblems');
		$suite->addTestSuite('testPageHistory');
		$suite->addTestSuite('testPageHostGraph');
		$suite->addTestSuite('testPageHostPrototypes');
		$suite->addTestSuite('testPageHosts');
		$suite->addTestSuite('testPageInventory');
		$suite->addTestSuite('testPageItems');
		$suite->addTestSuite('testPageItemPrototypes');
		$suite->addTestSuite('testPageTriggers');
		$suite->addTestSuite('testPageTriggerUrl');
		$suite->addTestSuite('testPageTriggerPrototypes');
		$suite->addTestSuite('testPageLatestData');
		$suite->addTestSuite('testPageLowLevelDiscovery');
		$suite->addTestSuite('testPageMaintenance');
		$suite->addTestSuite('testPageMaps');
		$suite->addTestSuite('testPageNetworkDiscovery');
		$suite->addTestSuite('testPageOverview');
/*
		$suite->addTestSuite('testPageQueueDetails');
		$suite->addTestSuite('testPageQueueOverview');
		$suite->addTestSuite('testPageQueueOverviewByProxy');
*/
		$suite->addTestSuite('testPageReportsActionLog');
		$suite->addTestSuite('testPageReportsAudit');
		$suite->addTestSuite('testPageReportsNotifications');
		$suite->addTestSuite('testPageReportsTriggerTop');
		$suite->addTestSuite('testPageScreens');
		$suite->addTestSuite('testPageSearch');
		$suite->addTestSuite('testPageSlideShows');
		$suite->addTestSuite('testPageStatusOfZabbix');
		$suite->addTestSuite('testPageTemplates');
		$suite->addTestSuite('testPageUserGroups');
		$suite->addTestSuite('testPageUsers');
		$suite->addTestSuite('testPageWeb');
		$suite->addTestSuite('testProblemsBySeverityWidget');
		$suite->addTestSuite('testHostAvailabilityWidget');
		$suite->addTestSuite('testFormAction');
		$suite->addTestSuite('testFormAdministrationAuthenticationHttp');
		$suite->addTestSuite('testFormAdministrationAuthenticationLdap');
		$suite->addTestSuite('testFormAdministrationDMProxies');
		$suite->addTestSuite('testFormAdministrationGeneralGUI');
		$suite->addTestSuite('testFormAdministrationGeneralHousekeeper');
		$suite->addTestSuite('testFormAdministrationGeneralIconMapping');
//		$suite->addTestSuite('testFormAdministrationGeneralImages');
		$suite->addTestSuite('testFormAdministrationGeneralMacro');
		$suite->addTestSuite('testFormAdministrationGeneralOtherParams');
		$suite->addTestSuite('testFormAdministrationGeneralRegexp');
		$suite->addTestSuite('testFormAdministrationGeneralTrigDisplOptions');
		$suite->addTestSuite('testFormAdministrationGeneralValuemap');
		$suite->addTestSuite('testFormAdministrationGeneralWorkperiod');
		$suite->addTestSuite('testFormAdministrationGeneralInstallation');
		$suite->addTestSuite('testFormAdministrationMediaTypes');
		$suite->addTestSuite('testFormAdministrationMediaTypeWebhook');
		$suite->addTestSuite('testFormAdministrationScripts');
		$suite->addTestSuite('testFormAdministrationUserGroups');
		$suite->addTestSuite('testFormConfigTriggerSeverity');
		$suite->addTestSuite('testFormEventCorrelation');
		$suite->addTestSuite('testFormGraph');
		$suite->addTestSuite('testFormGraphPrototype');
		$suite->addTestSuite('testFormHost');
		$suite->addTestSuite('testFormHostGroup');
		$suite->addTestSuite('testFormHostMacros');
		$suite->addTestSuite('testFormHostPrototype');
		$suite->addTestSuite('testFormHostTags');
		$suite->addTestSuite('testFormItem');
		$suite->addTestSuite('testFormItemHttpAgent');
		$suite->addTestSuite('testFormItemPreprocessing');
		$suite->addTestSuite('testFormItemPreprocessingTest');
		$suite->addTestSuite('testFormItemPrototype');
		$suite->addTestSuite('testFormItemPrototypePreprocessing');
		$suite->addTestSuite('testFormLogin');
		$suite->addTestSuite('testFormLowLevelDiscovery');
		$suite->addTestSuite('testFormLowLevelDiscoveryPreprocessing');
		$suite->addTestSuite('testFormMaintenance');
		$suite->addTestSuite('testFormMap');
		$suite->addTestSuite('testFormNetworkDiscovery');
		$suite->addTestSuite('testFormScreen');
		$suite->addTestSuite('testFormSysmap');
		$suite->addTestSuite('testFormTemplate');
		$suite->addTestSuite('testFormTemplateMacros');
		$suite->addTestSuite('testFormTemplateTags');
		$suite->addTestSuite('testFormTrigger');
		$suite->addTestSuite('testFormTriggerTags');
		$suite->addTestSuite('testFormTriggerPrototype');
		$suite->addTestSuite('testFormUser');
		$suite->addTestSuite('testFormUserProfile');
		$suite->addTestSuite('testFormWeb');
		$suite->addTestSuite('testFormWebStep');
		$suite->addTestSuite('testFormApplication');
		$suite->addTestSuite('testPageApplications');
		$suite->addTestSuite('testPageBrowserWarning');
		$suite->addTestSuite('testInheritanceApplication');
		$suite->addTestSuite('testInheritanceItem');
		$suite->addTestSuite('testInheritanceTrigger');
		$suite->addTestSuite('testInheritanceGraph');
		$suite->addTestSuite('testInheritanceGraphPrototype');
		$suite->addTestSuite('testInheritanceWeb');
		$suite->addTestSuite('testInheritanceDiscoveryRule');
		$suite->addTestSuite('testInheritanceHostPrototype');
		$suite->addTestSuite('testInheritanceItemPrototype');
		$suite->addTestSuite('testInheritanceTriggerPrototype');
		$suite->addTestSuite('testMultiselect');
		$suite->addTestSuite('testTagBasedPermissions');
		$suite->addTestSuite('testTemplateInheritance');
		$suite->addTestSuite('testTriggerDependencies');
		$suite->addTestSuite('testTriggerExpressions');
		$suite->addTestSuite('testUrlParameters');
		$suite->addTestSuite('testUrlUserPermissions');
		$suite->addTestSuite('testZBX6339');
		$suite->addTestSuite('testZBX6648');
		$suite->addTestSuite('testZBX6663');
		$suite->addTestSuite('testGraphWidget');

		return $suite;
	}
}
