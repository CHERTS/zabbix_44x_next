# List of backported patches from version 5.0/5.2 and list of my patches

[По-русски / In Russian](PATCHLIST.ru.md)

~~~~
[ZBX-17252] fixed building of Zabbix daemons with link time optimization (https://support.zabbix.com/browse/ZBX-17252)
[ZBX-18014] fixed memory leak when action message does not have to be sent (https://support.zabbix.com/browse/ZBX-18014)
[ZBX-18024] added ODBC empty query check (https://support.zabbix.com/browse/ZBX-18024)
[ZBX-18069] fixed server exit when trying to execute IPMI script without IPMI pollers (https://support.zabbix.com/browse/ZBX-18069)
[ZBX-18105] fixed use on uninitialised variable (https://support.zabbix.com/browse/ZBX-18105)
[ZBXNEXT-435] Add Oracle TNSNAMES connect support (https://support.zabbix.com/browse/ZBXNEXT-435)
[ZBXNEXT-5554] Added webhook through proxy (https://support.zabbix.com/browse/ZBXNEXT-5554)
[DBS-001] Added the ability to display 4 tags on the Problems page
[DBS-002] Added space between tags on Problems page
[DBS-003] Added a quick jump to the "Latest data" page from the Configuration -> Hosts page
[DBS-004] Change default setting: ZBX_UNITS_ROUNDOFF_MIDDLE_LIMIT - 4 => 2 and ZBX_DEFAULT_URL - zabbix.php?action=dashboard.view => zabbix.php?action=problem.view
[DBS-005] Increased the width of the pop-up window on the Problems page
[DBS-006] Tag column has been moved before the Problem column. The width of the Tag column has also been increased
~~~~
