# Список бэкпортированных патчей из версии 5.0/5.2, а так же собственных доработок

[In English / По-английски](PATCHLIST.md)

~~~~
[ZBX-8081] fixed 'cmdline' parameter truncation on Solaris 11.3 and later (https://support.zabbix.com/browse/ZBX-8081)
[ZBX-13383] improved performance of the trigger and trigger prototype inheritance (https://support.zabbix.com/browse/ZBX-13383)
[ZBX-13789] improved performance of "Problems" widget with MySQL backend by removing DISTINCT modifier from SQL statements with tables joined by primary keys (https://support.zabbix.com/browse/ZBX-13789)
[ZBX-15117] fixed graph configuration with large amount of items (https://support.zabbix.com/browse/ZBX-15117)
[ZBX-15927] removed problem calculation for dependent triggers when master trigger is in problem state (https://support.zabbix.com/browse/ZBX-15927)
[ZBX-17252] fixed building of Zabbix daemons with link time optimization (https://support.zabbix.com/browse/ZBX-17252)
[ZBX-17468] fixed excess audit log record being added on host status update (https://support.zabbix.com/browse/ZBX-17468)
[ZBX-17624] fixed misleading error message in case of not available datastorage for vmware.hv.datastore.read and vmware.hv.datastore.write metrics (https://support.zabbix.com/browse/ZBX-17624)
[ZBX-17922] removed mtime check when matching files on disk with the cached list, this should reduce chances of log files being scanned again after system time changes (https://support.zabbix.com/browse/ZBX-17922)
[ZBX-17682] fixed button disable state in template item list and discovery list (https://support.zabbix.com/browse/ZBX-17682)
[ZBX-17820] fixed classic graph widget default header (https://support.zabbix.com/browse/ZBX-17820)
[ZBX-17860] fixed host prototype import (https://support.zabbix.com/browse/ZBX-17860)
[ZBX-17917] changed vm.memory.size keys on Solaris to get memory usage statistics from kstat (https://support.zabbix.com/browse/ZBX-17917)
[ZBX-17922] removed mtime check when matching files on disk with the cached list, this should reduce chances of log files being scanned again after system time changes (https://support.zabbix.com/browse/ZBX-17922)
[ZBX-17924] fixed php errors in "System information" window for MySQL (https://support.zabbix.com/browse/ZBX-17924)
[ZBX-17960] fixed undefined index in trends API (https://support.zabbix.com/browse/ZBX-17960)
[ZBX-17974] implement timeouts for WMI queries (https://support.zabbix.com/browse/ZBX-17974)
[ZBX-18000] added red label for disabled media types in user profile (https://support.zabbix.com/browse/ZBX-18000)
[ZBX-18014] fixed memory leak when action message does not have to be sent (https://support.zabbix.com/browse/ZBX-18014)
[ZBX-18024] added ODBC empty query check (https://support.zabbix.com/browse/ZBX-18024)
[ZBX-18029] fixed cpu counter wraparound handling on Solaris (https://support.zabbix.com/browse/ZBX-18029)
[ZBX-18046] imporved error message generation performance of user macro and item key parser (https://support.zabbix.com/browse/ZBX-18046)
[ZBX-18050] removed proxy name from host breadcrumbs (https://support.zabbix.com/browse/ZBX-18050)
[ZBX-18060] fixed inaccessible user be displayed as accessible in slide show properties screen (https://support.zabbix.com/browse/ZBX-18060)
[ZBX-18063] fixed usage of unsupported parameter in API call (https://support.zabbix.com/browse/ZBX-18063)
[ZBX-18069] fixed server exit when trying to execute IPMI script without IPMI pollers (https://support.zabbix.com/browse/ZBX-18069)
[ZBX-18075] fixed runtime error when creating a host via API (https://support.zabbix.com/browse/ZBX-18075)
[ZBX-18076] fixed PHP notice and multiple undefined index errors in hostinterface API (https://support.zabbix.com/browse/ZBX-18076)
[ZBX-18105] fixed use on uninitialised variable (https://support.zabbix.com/browse/ZBX-18105)
[ZBX-18147] fixed wrong character set detection logic for Oracle DB (https://support.zabbix.com/browse/ZBX-18147)
[ZBX-18160] fixed fatal error in items.php and host_discovery.php (https://support.zabbix.com/browse/ZBX-18160)
[ZBXNEXT-435] Add Oracle TNSNAMES connect support (https://support.zabbix.com/browse/ZBXNEXT-435)
[ZBXNEXT-1961] added support for connection string to db.odbc.* items (https://support.zabbix.com/browse/ZBXNEXT-1961)
[ZBXNEXT-5554] Added webhook through proxy (https://support.zabbix.com/browse/ZBXNEXT-5554)
[ZBXNEXT-5725] added ability to select specific severities in Monitoring->Problems (https://support.zabbix.com/browse/ZBXNEXT-5725)
[ZBXNEXT-5903] added vmware datacenters discovery (https://support.zabbix.com/browse/ZBXNEXT-5903)
[ZBXNEXT-6004] added host info to vmware event message log (https://support.zabbix.com/browse/ZBXNEXT-6004)
[DBS-001] На странице "Проблемы" реализована возможность отображения 4-х тегов
[DBS-002] На странице "Проблемы" в колонке "Теги" добавлен пробел для разделения тегов при выделении и копировани их в буфер обмена
[DBS-003] Добавлен переход на страницу "Последние данные" со страницы Конфигурация -> Хосты
[DBS-004] Изменены некоторые настройки по-умолчанию в веб-интерфейса: ZBX_UNITS_ROUNDOFF_MIDDLE_LIMIT - 4 => 2 и ZBX_DEFAULT_URL - zabbix.php?action=dashboard.view => zabbix.php?action=problem.view
[DBS-005] Увеличина ширина всплывающего окна на странице "Проблемы" при клике на аларм
[DBS-006] На странице "Проблемы" колонка "Теги" перемещена в позицию после корлонки "Проблема", так же увеличена ширина колонки "Теги"
~~~~
