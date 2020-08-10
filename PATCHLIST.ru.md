# Список бэкпортированных патчей из версии 5.0/5.2, а так же собственных доработок

[In English / По-английски](PATCHLIST.md)

~~~~
[ZBX-18014] fixed memory leak when action message does not have to be sent (https://support.zabbix.com/browse/ZBX-18014)
[ZBX-18105] fixed use on uninitialised variable (https://support.zabbix.com/browse/ZBX-18105)
[ZBXNEXT-435] Добавлена поддержка подключения к Oracle RDBMS с указанием tns-имени (https://support.zabbix.com/browse/ZBXNEXT-435)
[ZBXNEXT-5554] Добавлена поддержка работы веб-хуков через http/socks5 прокси (https://support.zabbix.com/browse/ZBXNEXT-5554)
[DBS-001] На странице "Проблемы" реализована возможность отображения 4-х тегов
[DBS-002] На странице "Проблемы" в колонке "Теги" добавлен пробел для разделения тегов при выделении и копировани их в буфер обмена
[DBS-003] Добавлен переход на страницу "Последние данные" со страницы Конфигурация -> Хосты
[DBS-004] Изменены некоторые настройки по-умолчанию в веб-интерфейса: ZBX_UNITS_ROUNDOFF_MIDDLE_LIMIT - 4 => 2 и ZBX_DEFAULT_URL - zabbix.php?action=dashboard.view => zabbix.php?action=problem.view
[DBS-005] Увеличина ширина всплывающего окна на странице "Проблемы" при клике на аларм
[DBS-006] На странице "Проблемы" колонка "Теги" перемещена в позицию после корлонки "Проблема", так же увеличена ширина колонки "Теги"
~~~~
