-- Test data for API tests

-- Activate "Zabbix Server" host
UPDATE hosts SET status=0 WHERE host='Zabbix server';

-- applications
INSERT INTO hosts (hostid, host, name, status, description) VALUES (50009, 'API Host', 'API Host', 0, '');
INSERT INTO hosts (hostid, host, name, status, description) VALUES (50010, 'API Template', 'API Template', 3, '');
INSERT INTO interface (interfaceid,hostid,main,type,useip,ip,dns,port) values (50022,50009,1,1,1,'127.0.0.1','','10050');
INSERT INTO hstgrp (groupid,name,internal) VALUES (50012,'API group for hosts',0);
INSERT INTO hstgrp (groupid,name,internal) VALUES (50013,'API group for templates',0);
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (50009, 50009, 50012);
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (50011, 50010, 50013);
INSERT INTO hosts_templates (hosttemplateid, hostid, templateid) VALUES (50003, 50009, 50010);
INSERT INTO applications (applicationid,hostid,name) VALUES (366,50009,'API application');
INSERT INTO applications (applicationid,hostid,name) VALUES (367,50009,'API host application for update');
INSERT INTO applications (applicationid,hostid,name) VALUES (368,10093,'API template application for update');
INSERT INTO applications (applicationid,hostid,name) VALUES (369,50010,'API templated application');
INSERT INTO applications (applicationid,hostid,name) VALUES (370,50009,'API templated application');
INSERT INTO applications (applicationid,hostid,name) VALUES (371,50009,'API application delete');
INSERT INTO applications (applicationid,hostid,name) VALUES (372,50009,'API application delete2');
INSERT INTO applications (applicationid,hostid,name) VALUES (373,50009,'API application delete3');
INSERT INTO applications (applicationid,hostid,name) VALUES (374,50009,'API application delete4');
INSERT INTO applications (applicationid,hostid,name) VALUES (376,10084,'API application for Zabbix server');
INSERT INTO application_template (application_templateid,applicationid,templateid) VALUES (52,370,369);
-- discovered application
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,status,params,description,flags,posts,headers) VALUES (40066, 50009, 50022, 0, 2,'API discovery rule','vfs.fs.discovery',30,90,0,'','',1,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,status,params,description,flags,posts,headers) VALUES (40067, 50009, 50022, 0, 2,'API discovery item','vfs.fs.size[{#FSNAME},free]',30,90,0,'','',2,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,status,params,description,flags,posts,headers) VALUES (40068, 50009, 50022, 0, 2,'API discovery item','vfs.fs.size[/,free]',30,90,0,'','',4,'','');
INSERT INTO item_discovery (itemdiscoveryid,itemid,parent_itemid,key_) VALUES (15085,40067,40066,'vfs.fs.size[{#FSNAME},free]');
INSERT INTO item_discovery (itemdiscoveryid,itemid,parent_itemid,key_) VALUES (15086,40068,40067,'vfs.fs.size[{#FSNAME},free]');
INSERT INTO applications (applicationid,hostid,name,flags) VALUES (375,50009,'API discovery application',4);
INSERT INTO application_prototype (application_prototypeid,itemid,name) VALUES (900,40066,'API discovery application');
INSERT INTO application_discovery (application_discoveryid,applicationid,application_prototypeid,name) VALUES (1,375,900,'API discovery application');
INSERT INTO items_applications (itemappid,applicationid,itemid) VALUES (6000,375,40068);
INSERT INTO item_application_prototype (item_application_prototypeid,application_prototypeid,itemid) VALUES (900,900,40067);

-- valuemap
INSERT INTO valuemaps (valuemapid,name) VALUES (99,'API value map for update');
INSERT INTO valuemaps (valuemapid,name) VALUES (100,'API value map for update with mappings');
INSERT INTO valuemaps (valuemapid,name) VALUES (101,'API value map delete');
INSERT INTO valuemaps (valuemapid,name) VALUES (102,'API value map delete2');
INSERT INTO valuemaps (valuemapid,name) VALUES (103,'API value map delete3');
INSERT INTO valuemaps (valuemapid,name) VALUES (104,'API value map delete4');
INSERT INTO mappings (mappingid,valuemapid,value,newvalue) VALUES (9904,100,'One','Online');
INSERT INTO mappings (mappingid,valuemapid,value,newvalue) VALUES (9905,100,'Two','Offline');
INSERT INTO mappings (mappingid,valuemapid,value,newvalue) VALUES (9906,102,'Three','Other');
INSERT INTO mappings (mappingid,valuemapid,value,newvalue) VALUES (9907,103,'Four','Unknown');
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (4, 'zabbix-admin', '5fce1b3e34b520afeffb37ce08c7cd66', 0, 0, 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (5, 'zabbix-user', '5fce1b3e34b520afeffb37ce08c7cd66', 0, 0, 'en_GB', '30s', 1, 'default', 0, 0, 50);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (6, 8, 4);

-- host groups
INSERT INTO hstgrp (groupid,name,internal) VALUES (50005,'API host group for update',0);
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (50010, 50009, 50005);
INSERT INTO hstgrp (groupid,name,internal) VALUES (50006,'API host group for update internal',1);
INSERT INTO hstgrp (groupid,name,internal) VALUES (50007,'API host group delete internal',1);
INSERT INTO hstgrp (groupid,name,internal) VALUES (50008,'API host group delete',0);
INSERT INTO hstgrp (groupid,name,internal) VALUES (50009,'API host group delete2',0);
INSERT INTO hstgrp (groupid,name,internal) VALUES (50010,'API host group delete3',0);
INSERT INTO hstgrp (groupid,name,internal) VALUES (50011,'API host group delete4',0);
-- discovered host groups
INSERT INTO hosts (hostid, host, name, status, flags, description) VALUES (50011, 'API host prototype {#FSNAME}', 'API host prototype {#FSNAME}', 0, 2, '');
INSERT INTO hstgrp (groupid,name,internal) VALUES (50014,'API group for host prototype',0);
INSERT INTO host_discovery (hostid,parent_hostid,parent_itemid) VALUES (50011,NULL,40066);
INSERT INTO group_prototype (group_prototypeid, hostid, name, groupid, templateid) VALUES (8, 50011, 'API discovery group {#HV.NAME}', NULL, NULL);
INSERT INTO group_prototype (group_prototypeid, hostid, name, groupid, templateid) VALUES (9, 50011, '', 50014, NULL);
INSERT INTO hstgrp (groupid,name,internal,flags) VALUES (50015,'API discovery group {#HV.NAME}',0,4);
INSERT INTO group_discovery (groupid, parent_group_prototypeid, name) VALUES (50015, 8, 'API discovery group {#HV.NAME}');

-- user group
INSERT INTO usrgrp (usrgrpid, name) VALUES (13, 'API user group for update');
INSERT INTO usrgrp (usrgrpid, name) VALUES (14, 'API user group for update with user and rights');
INSERT INTO usrgrp (usrgrpid, name) VALUES (15, 'API user group with one user');
INSERT INTO usrgrp (usrgrpid, name) VALUES (16, 'API user group delete');
INSERT INTO usrgrp (usrgrpid, name) VALUES (17, 'API user group delete1');
INSERT INTO usrgrp (usrgrpid, name) VALUES (18, 'API user group delete2');
INSERT INTO usrgrp (usrgrpid, name) VALUES (19, 'API user group delete3');
INSERT INTO usrgrp (usrgrpid, name) VALUES (20, 'API user group in actions');
INSERT INTO usrgrp (usrgrpid, name) VALUES (21, 'API user group in scripts');
INSERT INTO usrgrp (usrgrpid, name) VALUES (22, 'API user group in configuration');
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (6, 'user-in-one-group', '5fce1b3e34b520afeffb37ce08c7cd66', 0, 0, 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (7, 'user-in-two-groups', '5fce1b3e34b520afeffb37ce08c7cd66', 0, 0, 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (8, 'api-user', '5fce1b3e34b520afeffb37ce08c7cd66', 0, 0, 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (8, 14, 4);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (9, 15, 6);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (10, 16, 7);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (11, 17, 7);
INSERT INTO rights (rightid, groupid, permission, id) VALUES (2, 14, 3, 50012);
INSERT INTO actions (actionid, name, eventsource, evaltype, status, esc_period, def_shortdata, def_longdata, r_shortdata, r_longdata, ack_longdata) VALUES (16,'API action',0,0,0,60,'{TRIGGER.NAME}: {TRIGGER.STATUS}','{TRIGGER.NAME}: {TRIGGER.STATUS}\r\nLast value: {ITEM.LASTVALUE}','{TRIGGER.NAME}: {TRIGGER.STATUS}','{TRIGGER.NAME}: {TRIGGER.STATUS}Last value: {ITEM.LASTVALUE}', '');
INSERT INTO operations (operationid, actionid, operationtype, esc_period, esc_step_from, esc_step_to, evaltype) VALUES (31, 16, 0, 0, 1, 1, 0);
INSERT INTO opmessage (operationid, default_msg, subject, message, mediatypeid) VALUES (31, 1, '{TRIGGER.NAME}: {TRIGGER.STATUS}', '{TRIGGER.NAME}: {TRIGGER.STATUS}Last value: {ITEM.LASTVALUE}{TRIGGER.URL}', NULL);
INSERT INTO opmessage_grp (opmessage_grpid, operationid, usrgrpid) VALUES (21, 31, 20);
INSERT INTO scripts (scriptid, name, command, host_access, usrgrpid, groupid, description) VALUES (5,'API script','test',2,21,NULL,'api script description');
UPDATE config SET alert_usrgrpid = 22 WHERE configid = 1;

-- users
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (9, 'api-user-for-update', '5fce1b3e34b520afeffb37ce08c7cd66', 0, '15m', 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (10, 'api-user-delete', '5fce1b3e34b520afeffb37ce08c7cd66', 0, '15m', 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (11, 'api-user-delete1', '5fce1b3e34b520afeffb37ce08c7cd66', 0, '15m', 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (12, 'api-user-delete2', '5fce1b3e34b520afeffb37ce08c7cd66', 0, '15m', 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (13, 'api-user-action', '5fce1b3e34b520afeffb37ce08c7cd66', 0, '15m', 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (14, 'api-user-map', '5fce1b3e34b520afeffb37ce08c7cd66', 0, '15m', 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (15, 'api-user-screen', '5fce1b3e34b520afeffb37ce08c7cd66', 0, '15m', 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (16, 'api-user-slideshow', '5fce1b3e34b520afeffb37ce08c7cd66', 0, '15m', 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (12, 14, 9);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (13, 14, 10);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (14, 14, 11);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (15, 14, 12);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (16, 9, 13);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (17, 14, 14);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (18, 14, 15);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (19, 14, 16);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (20, 14, 5);
INSERT INTO actions (actionid, name, eventsource, evaltype, status, esc_period, def_shortdata, def_longdata, r_shortdata, r_longdata, ack_longdata) VALUES (17,'API action with user',0,0,0,60,'{TRIGGER.NAME}: {TRIGGER.STATUS}','{TRIGGER.NAME}: {TRIGGER.STATUS}\r\nLast value: {ITEM.LASTVALUE}','{TRIGGER.NAME}: {TRIGGER.STATUS}','{TRIGGER.NAME}: {TRIGGER.STATUS}Last value: {ITEM.LASTVALUE}', '');
INSERT INTO operations (operationid, actionid, operationtype, esc_period, esc_step_from, esc_step_to, evaltype) VALUES (32, 17, 0, 0, 1, 1, 0);
INSERT INTO opmessage (operationid, default_msg, subject, message, mediatypeid) VALUES (32, 1, '{TRIGGER.NAME}: {TRIGGER.STATUS}', '{TRIGGER.NAME}: {TRIGGER.STATUS}Last value: {ITEM.LASTVALUE}{TRIGGER.URL}', NULL);
INSERT INTO opmessage_usr (opmessage_usrid, operationid, userid) VALUES (4, 32, 13);
INSERT INTO sysmaps (sysmapid, name, width, height, backgroundid, label_type, label_location, highlight, expandproblem, markelements, show_unack, userid, private) VALUES (6, 'API map', 800, 600, NULL, 0, 0, 1, 1, 1, 2, 14, 0);
INSERT INTO screens (screenid, name, hsize, vsize, templateid, userid, private) VALUES (200021, 'API screen', 1, 1, NULL, 15, 0);
INSERT INTO slideshows (slideshowid, name, delay, userid, private) VALUES (200004, 'API slide show', 10, 16, 0);
INSERT INTO screens (screenid, name, hsize, vsize, templateid, userid, private) VALUES (200022, 'API screen for slide show', 1, 1, NULL, 1, 0);
INSERT INTO slides (slideid, slideshowid, screenid, step, delay) VALUES (200012, 200004, 200022, 0, 0);

-- scripts
INSERT INTO hosts (hostid, host, name, status, description) VALUES (50013, 'API disabled host', 'API disabled host', 1, '');
INSERT INTO interface (interfaceid,hostid,main,type,useip,ip,dns,port) values (50024,50013,1,1,1,'127.0.0.1','','10050');
INSERT INTO hstgrp (groupid,name,internal) VALUES (90000,'API group for disabled host',0);
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (50013, 50013, 90000);
INSERT INTO hosts (hostid, host, name, status, description) VALUES (50012, 'API Host for read permissions', 'API Host for read permissions', 0, '');
INSERT INTO interface (interfaceid,hostid,main,type,useip,ip,dns,port) values (50023,50012,1,1,1,'127.0.0.1','','10050');
INSERT INTO hstgrp (groupid,name,internal) VALUES (50016,'API group with read permissions',0);
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (50012, 50012, 50016);
INSERT INTO rights (rightid, groupid, permission, id) VALUES (3, 14, 2, 50016);
INSERT INTO hosts (hostid, host, name, status, description) VALUES (50014, 'API Host for deny permissions', 'API Host for deny permissions', 0, '');
INSERT INTO interface (interfaceid,hostid,main,type,useip,ip,dns,port) values (50025,50014,1,1,1,'127.0.0.1','','10050');
INSERT INTO hstgrp (groupid,name,internal) VALUES (50017,'API group with deny permissions',0);
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (50014, 50014, 50017);
INSERT INTO rights (rightid, groupid, permission, id) VALUES (4, 14, 0, 50017);
INSERT INTO scripts (scriptid, name, command, host_access, usrgrpid, groupid, description) VALUES (6,'API script for update one','/sbin/shutdown -r',2,NULL,NULL,'');
INSERT INTO scripts (scriptid, name, command, host_access, usrgrpid, groupid, description) VALUES (7,'API script for update two','/sbin/shutdown -r',2,NULL,NULL,'');
INSERT INTO scripts (scriptid, name, command, host_access, usrgrpid, groupid, description) VALUES (8,'API script for delete','/sbin/shutdown -r',2,NULL,NULL,'');
INSERT INTO scripts (scriptid, name, command, host_access, usrgrpid, groupid, description) VALUES (9,'API script for delete1','/sbin/shutdown -r',2,NULL,NULL,'');
INSERT INTO scripts (scriptid, name, command, host_access, usrgrpid, groupid, description) VALUES (10,'API script for delete2','/sbin/shutdown -r',2,NULL,NULL,'');
INSERT INTO scripts (scriptid, name, command, host_access, usrgrpid, groupid, description) VALUES (11,'API script in action','/sbin/shutdown -r',2,NULL,NULL,'');
INSERT INTO scripts (scriptid, name, command, host_access, usrgrpid, groupid, description) VALUES (12,'API script with user group','/sbin/shutdown -r',2,7,NULL,'');
INSERT INTO scripts (scriptid, name, command, host_access, usrgrpid, groupid, description) VALUES (13,'API script with host group','/sbin/shutdown -r',2,NULL,4,'');
INSERT INTO scripts (scriptid, name, command, host_access, usrgrpid, groupid, description) VALUES (14,'API script with write permissions for the host group','/sbin/shutdown -r',3,NULL,NULL,'');
INSERT INTO actions (actionid, name, eventsource, evaltype, status, esc_period, def_shortdata, def_longdata, r_shortdata, r_longdata, ack_longdata) VALUES (18,'API action with script',0,0,0,60,'{TRIGGER.NAME}: {TRIGGER.STATUS}','{TRIGGER.NAME}: {TRIGGER.STATUS}\r\nLast value: {ITEM.LASTVALUE}','{TRIGGER.NAME}: {TRIGGER.STATUS}','{TRIGGER.NAME}: {TRIGGER.STATUS}Last value: {ITEM.LASTVALUE}', '');
INSERT INTO operations (operationid, actionid, operationtype, esc_period, esc_step_from, esc_step_to, evaltype) VALUES (33, 18, 1, 0, 1, 1, 0);
INSERT INTO opcommand_hst (opcommand_hstid, operationid, hostid) VALUES (4, 33, NULL);
INSERT INTO opcommand (operationid, type, scriptid, execute_on, port, authtype, username, password, publickey, privatekey, command) VALUES (33, 4, 11, 0, '', 0, '', '', '', '', '');

-- scripts / inherited hostgroups
INSERT INTO usrgrp (usrgrpid,name) VALUES (90000,'90000 Eur group write except one');
INSERT INTO users (userid,alias,passwd,type) VALUES (90000,'90000','5fce1b3e34b520afeffb37ce08c7cd66',2);
INSERT INTO users_groups (id,usrgrpid,userid) VALUES (90000,90000,90000);
INSERT INTO hosts (hostid,host,name,status,description) VALUES (90020,'90020','90020',0,'');
INSERT INTO hosts (hostid,host,name,status,description) VALUES (90021,'90021','90021',0,'');
INSERT INTO hosts (hostid,host,name,status,description) VALUES (90022,'90022','90022',0,'');
INSERT INTO hosts (hostid,host,name,status,description) VALUES (90023,'90023','90023',0,'');
INSERT INTO hstgrp (groupid,name,internal) VALUES (90020,'90000Eur',0);
INSERT INTO hstgrp (groupid,name,internal) VALUES (90021,'90000Eur/LV',0);
INSERT INTO hstgrp (groupid,name,internal) VALUES (90022,'90000Eur/LV/Rix',0);
INSERT INTO hstgrp (groupid,name,internal) VALUES (90023,'90000Eur/LV/Skipped/Rix',0);
INSERT INTO rights (rightid,groupid,permission,id) VALUES (90000,90000,3,90020);
INSERT INTO rights (rightid,groupid,permission,id) VALUES (90001,90000,2,90021);
INSERT INTO rights (rightid,groupid,permission,id) VALUES (90002,90000,3,90022);
INSERT INTO rights (rightid,groupid,permission,id) VALUES (90003,90000,3,90023);
INSERT INTO hosts_groups (hostid,groupid,hostgroupid) VALUES (90020,90020,90020);
INSERT INTO hosts_groups (hostid,groupid,hostgroupid) VALUES (90021,90021,90021);
INSERT INTO hosts_groups (hostid,groupid,hostgroupid) VALUES (90022,90022,90022);
INSERT INTO hosts_groups (hostid,groupid,hostgroupid) VALUES (90023,90023,90023);
INSERT INTO scripts (groupid,scriptid,host_access,name,command,usrgrpid,description) VALUES (90020,90020,2,'90020-acc-read','date',NULL,'');
INSERT INTO scripts (groupid,scriptid,host_access,name,command,usrgrpid,description) VALUES (90021,90021,3,'90021-acc-write','date',NULL,'');
INSERT INTO scripts (groupid,scriptid,host_access,name,command,usrgrpid,description) VALUES (90023,90023,2,'90023-acc-read','date',NULL,'');

-- global macro
INSERT INTO globalmacro (globalmacroid, macro, value, description) VALUES (13,'{$API_MACRO_FOR_UPDATE1}','update','desc');
INSERT INTO globalmacro (globalmacroid, macro, value, description) VALUES (14,'{$API_MACRO_FOR_UPDATE2}','update','');
INSERT INTO globalmacro (globalmacroid, macro, value, description) VALUES (15,'{$API_MACRO_FOR_DELETE}','abc','');
INSERT INTO globalmacro (globalmacroid, macro, value, description) VALUES (16,'{$API_MACRO_FOR_DELETE1}','1','');
INSERT INTO globalmacro (globalmacroid, macro, value, description) VALUES (17,'{$API_MACRO_FOR_DELETE2}','2','');

-- host macro
INSERT INTO hostmacro (hostmacroid, hostid, macro, value, description) VALUES (1,90020,'{$HOST_MACRO_1}','value','description');
INSERT INTO hostmacro (hostmacroid, hostid, macro, value, description) VALUES (2,90020,'{$HOST_MACRO_2}','value','');

-- icon map
INSERT INTO icon_map (iconmapid, name, default_iconid) VALUES (1,'API icon map',2);
INSERT INTO icon_mapping (iconmappingid, iconmapid, iconid, inventory_link, expression, sortorder) VALUES (1,1,2,1,'api icon map expression',0);
INSERT INTO icon_map (iconmapid, name, default_iconid) VALUES (2,'API icon map for update1',2);
INSERT INTO icon_mapping (iconmappingid, iconmapid, iconid, inventory_link, expression, sortorder) VALUES (2,2,2,1,'api expression for update1',0);
INSERT INTO icon_map (iconmapid, name, default_iconid) VALUES (3,'API icon map for update2',2);
INSERT INTO icon_mapping (iconmappingid, iconmapid, iconid, inventory_link, expression, sortorder) VALUES (3,3,2,1,'api expression for update2',0);
INSERT INTO icon_map (iconmapid, name, default_iconid) VALUES (4,'API icon map for delete',2);
INSERT INTO icon_mapping (iconmappingid, iconmapid, iconid, inventory_link, expression, sortorder) VALUES (4,4,2,1,'api expression for delete',0);
INSERT INTO icon_map (iconmapid, name, default_iconid) VALUES (5,'API icon map for delete1',2);
INSERT INTO icon_mapping (iconmappingid, iconmapid, iconid, inventory_link, expression, sortorder) VALUES (5,5,2,1,'api expression for delete1',0);
INSERT INTO icon_map (iconmapid, name, default_iconid) VALUES (6,'API icon map for delete2',2);
INSERT INTO icon_mapping (iconmappingid, iconmapid, iconid, inventory_link, expression, sortorder) VALUES (6,6,2,1,'api expression for delete2',0);
INSERT INTO icon_map (iconmapid, name, default_iconid) VALUES (7,'API iconmap in map',2);
INSERT INTO icon_mapping (iconmappingid, iconmapid, iconid, inventory_link, expression, sortorder) VALUES (7,7,7,1,'api expression',0);
INSERT INTO sysmaps (sysmapid, name, width, height, backgroundid, label_type, label_location, highlight, expandproblem, markelements, show_unack, iconmapid, userid, private) VALUES (7, 'Map with iconmap', 800, 600, NULL, 0, 0, 1, 1, 1, 2, 7, 1, 0);

-- web scenarios
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15000, 'Api web scenario', 60, 'Zabbix', 50009);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15000, 15000, 'Api step', 1, 'http://api.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15001, 'Api web scenario for update one', 60, 'Zabbix', 50009);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15001, 15001, 'Api step for update one', 1, 'http://api1.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15002, 'Api web scenario for update two', 60, 'Zabbix', 50009);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15002, 15002, 'Api step for update two', 1, 'http://api2.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15003, 'Api web scenario for delete0', 60, 'Zabbix', 50009);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15003, 15003, 'Api step for delete0', 1, 'http://api.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15004, 'Api web scenario for delete1', 60, 'Zabbix', 50009);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15004, 15004, 'Api step for delete1', 1, 'http://api.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15005, 'Api web scenario for delete2', 60, 'Zabbix', 50009);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15005, 15005, 'Api step for delete2', 1, 'http://api.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15006, 'Api templated web scenario', 60, 'Zabbix', 50010);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15006, 15006, 'Api templated step', 1, 'http://api.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid, templateid) VALUES (15007, 'Api templated web scenario', 60, 'Zabbix', 50009, 15006);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15007, 15007, 'Api templated step', 1, 'http://api.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15008, 'Api web scenario with read permissions', 60, 'Zabbix', 50012);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15008, 15008, 'Api step with read permissions', 1, 'http://api.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15009, 'Api web with deny permissions', 60, 'Zabbix', 50014);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15009, 15009, 'Api step with deny permissions', 1, 'http://api.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15010, 'Api web scenario for delete as zabbix-admin', 60, 'Zabbix', 50009);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15010, 15010, 'Api step for delete as zabbix-admin', 1, 'http://api.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15011, 'Api web scenario for delete as zabbix-user', 60, 'Zabbix', 50009);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15011, 15011, 'Api step for delete as zabbix-user', 1, 'http://api.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15012, 'Api web scenario for update having 1 step', 60, 'Zabbix', 50009);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15012, 15012, 'Api step for update having 1 step', 1, 'http://api.com', '');
INSERT INTO httptest (httptestid, name, delay, agent, hostid) VALUES (15013, 'Api web scenario for update having 2 steps', 60, 'Zabbix', 50009);
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15013, 15013, 'Api step 1', 1, 'http://api.com', '');
INSERT INTO httpstep (httpstepid, httptestid, name, no, url, posts) VALUES (15014, 15013, 'Api step 2', 2, 'http://api.com', '');

-- proxy
INSERT INTO hosts (hostid, host, status, description) VALUES (99000, 'Api active proxy for delete0', 5, '');
INSERT INTO hosts (hostid, host, status, description) VALUES (99001, 'Api active proxy for delete1', 5, '');
INSERT INTO hosts (hostid, host, status, description) VALUES (99002, 'Api passive proxy for delete', 6, '');
INSERT INTO interface (interfaceid, hostid, main, type, useip, ip, dns, port, bulk) VALUES (99002, 99002,1, 0, 1, '127.0.0.1', 'localhost', 10051, 1);
INSERT INTO hosts (hostid, host, status, description) VALUES (99003, 'Api active proxy in action', 5, '');
INSERT INTO hosts (hostid, host, status, description) VALUES (99004, 'Api active proxy with host', 5, '');
INSERT INTO hosts (hostid, proxy_hostid, host, name, status, description) VALUES (99005, 99004,'API Host monitored with proxy', 'API Host monitored with proxy', 0, '');
INSERT INTO interface (interfaceid,hostid,main,type,useip,ip,dns,port) values (99003,99004,1,1,1,'127.0.0.1','','10050');
INSERT INTO actions (actionid, name, eventsource, evaltype, status, esc_period, def_shortdata, def_longdata, r_shortdata, r_longdata, ack_longdata) VALUES (90,'API action with proxy',1,0,0,'1h','Discovery: {DISCOVERY.DEVICE.STATUS} {DISCOVERY.DEVICE.IPADDRESS}','Discovery rule: {DISCOVERY.RULE.NAME}','','','');
INSERT INTO operations (operationid, actionid, operationtype, esc_period, esc_step_from, esc_step_to, evaltype) VALUES (90, 90, 0, 0, 1, 1, 0);
INSERT INTO opmessage (operationid, default_msg, subject, message, mediatypeid) VALUES (90, 1, 'Discovery: {DISCOVERY.DEVICE.STATUS} {DISCOVERY.DEVICE.IPADDRESS}', 'Discovery rule: {DISCOVERY.RULE.NAME}', NULL);
INSERT INTO opmessage_grp (opmessage_grpid, operationid, usrgrpid) VALUES (90, 90, 7);
INSERT INTO conditions (conditionid, actionid, conditiontype, operator, value, value2) VALUES (90,90,20,0,99003,'');

-- sysmaps
INSERT INTO sysmaps (sysmapid, name, width, height, backgroundid, label_type, label_location, highlight, expandproblem, markelements, show_unack, userid, private) VALUES (10001, 'A', 800, 600, NULL, 0, 0, 1, 1, 1, 2, 1, 0);
INSERT INTO sysmaps (sysmapid, name, width, height, backgroundid, label_type, label_location, highlight, expandproblem, markelements, show_unack, userid, private) VALUES (10002, 'B', 800, 600, NULL, 0, 0, 1, 1, 1, 2, 1, 1);
INSERT INTO sysmaps (sysmapid, name, width, height, backgroundid, label_type, label_location, highlight, expandproblem, markelements, show_unack, userid, private) VALUES (10003, 'C', 800, 600, NULL, 0, 0, 1, 1, 1, 2, 1, 0);
INSERT INTO sysmaps (sysmapid, name, width, height, backgroundid, label_type, label_location, highlight, expandproblem, markelements, show_unack, userid, private) VALUES (10004, 'D', 800, 600, NULL, 0, 0, 1, 1, 1, 2, 1, 0);
INSERT INTO sysmap_user (sysmapuserid, sysmapid, userid, permission) VALUES (1, 10001, 5, 3);
INSERT INTO sysmap_user (sysmapuserid, sysmapid, userid, permission) VALUES (2, 10003, 5, 3);
INSERT INTO sysmap_user (sysmapuserid, sysmapid, userid, permission) VALUES (3, 10004, 5, 3);
INSERT INTO sysmaps_elements (selementid, sysmapid, elementid, elementtype, iconid_off, iconid_on, label, label_location, x, y, iconid_disabled, iconid_maintenance, elementsubtype, areatype, width, height, viewtype, use_iconmap, application) VALUES (7, 10001, 0, 4, 151, NULL, 'New element', -1, 189, 77, NULL, NULL, 0, 0, 200, 200, 0, 1, '');

-- disabled item and LLD rule
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,status,params,description,flags,posts,headers) VALUES (90000, 10084, 1, 0, 3,'Api disabled item','disabled.item','30d','90d',1,'','',0,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,status,params,description,flags,posts,headers) VALUES (90001, 10084, 1, 0, 4,'Api disabled LLD rule','disabled.lld','30d','90d',1,'','',1,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,status,params,description,flags,posts,headers) VALUES (90002, 50013, 50024, 0, 3,'Api item in disabled host','disabled.host.item','30d','90d',0,'','',0,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,status,params,description,flags,posts,headers) VALUES (90003, 50013, 50024, 0, 4,'Api LLD rule in disabled host','disabled.host.lld','30d','90d',0,'','',1,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,status,params,description,flags,posts,headers) VALUES (90004, 10084, 1, 0, 3,'Api item for different item types','types.item','1d','90d',0,'','',0,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,status,params,description,flags,posts,headers) VALUES (90005, 10084, 1, 0, 4,'Api LLD rule for different types','types.lld','1d','90d',0,'','',1,'','');

-- interfaces
INSERT INTO interface (interfaceid,hostid,main,type,useip,ip,dns,port) values (99004,10084,1,2,1,'127.0.0.1','','161');

-- autoregistration action
INSERT INTO usrgrp (usrgrpid, name) VALUES (47, 'User group for action delete');
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (53, 'action-user', '5fce1b3e34b520afeffb37ce08c7cd66', 0, 0, 'en_GB', '30s', 1, 'default', 0, 0, 50);
INSERT INTO users (userid, alias, passwd, autologin, autologout, lang, refresh, type, theme, attempt_failed, attempt_clock, rows_per_page) VALUES (54, 'action-admin', '5fce1b3e34b520afeffb37ce08c7cd66', 0, 0, 'en_GB', '30s', 2, 'default', 0, 0, 50);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (87, 47, 53);
INSERT INTO users_groups (id, usrgrpid, userid) VALUES (88, 47, 54);
INSERT INTO actions (actionid, name, eventsource, evaltype, status, esc_period, def_shortdata, def_longdata, r_shortdata, r_longdata, ack_longdata) VALUES (91,'Api Auto registration action',2,0,0,'1h','API Auto registration: {HOST.HOST}','Host name: {HOST.HOST}\r\nHost IP: {HOST.IP}\r\nAgent port: {HOST.PORT}','','','');
INSERT INTO operations (operationid, actionid, operationtype, esc_period, esc_step_from, esc_step_to, evaltype) VALUES (91, 91, 0, 0, 1, 1, 0);
INSERT INTO opmessage (operationid, default_msg, subject, message, mediatypeid) VALUES (91, 1, 'Auto registration: {HOST.HOST}', 'Host name: {HOST.HOST}', NULL);
INSERT INTO opmessage_grp (opmessage_grpid, operationid, usrgrpid) VALUES (91, 91, 47);
INSERT INTO actions (actionid, name, eventsource, evaltype, status, esc_period, def_shortdata, def_longdata, r_shortdata, r_longdata, ack_longdata) VALUES (92,	'API Action for deleting',0,0,0,'1h','Problem: {EVENT.NAME}','Problem started at {EVENT.TIME} on {EVENT.DATE}\r\nProblem name: {EVENT.NAME}\r\nHost: {HOST.NAME}\r\nSeverity: {TRIGGER.SEVERITY}\r\n\r\nOriginal problem ID: {EVENT.ID}\r\n{TRIGGER.URL}','Resolved: {EVENT.NAME}','Problem has been resolved at {EVENT.RECOVERY.TIME} on {EVENT.RECOVERY.DATE}\r\nProblem name: {EVENT.NAME}\r\nHost: {HOST.NAME}\r\nSeverity: {TRIGGER.SEVERITY}\r\n\r\nOriginal problem ID: {EVENT.ID}\r\n{TRIGGER.URL}','{USER.FULLNAME} acknowledged problem at {ACK.DATE} {ACK.TIME} with the following message:\r\n{ACK.MESSAGE}\r\n\r\nCurrent problem status is {EVENT.STATUS}');
INSERT INTO operations (operationid, actionid, operationtype, esc_period, esc_step_from, esc_step_to, evaltype) VALUES (92, 91, 0, 0, 1, 1, 0);
INSERT INTO opmessage (operationid, default_msg, subject, message, mediatypeid) VALUES (92,1,'Problem: {EVENT.NAME}',	'Problem started at {EVENT.TIME} on {EVENT.DATE}\r\nProblem name: {EVENT.NAME}\r\nHost: {HOST.NAME}\r\nSeverity: {TRIGGER.SEVERITY}\r\n\r\nOriginal problem ID: {EVENT.ID}\r\n{TRIGGER.URL}',NULL);
INSERT INTO opmessage_grp (opmessage_grpid, operationid, usrgrpid) VALUES (92, 92, 47);
INSERT INTO actions (actionid, name, eventsource, evaltype, status, esc_period, def_shortdata, def_longdata, r_shortdata, r_longdata, ack_longdata) VALUES (93,	'API Action for deleting 2',0,0,0,'1h','Problem: {EVENT.NAME}','Problem started at {EVENT.TIME} on {EVENT.DATE}\r\nProblem name: {EVENT.NAME}\r\nHost: {HOST.NAME}\r\nSeverity: {TRIGGER.SEVERITY}\r\n\r\nOriginal problem ID: {EVENT.ID}\r\n{TRIGGER.URL}','Resolved: {EVENT.NAME}',	'Problem has been resolved at {EVENT.RECOVERY.TIME} on {EVENT.RECOVERY.DATE}\r\nProblem name: {EVENT.NAME}\r\nHost: {HOST.NAME}\r\nSeverity: {TRIGGER.SEVERITY}\r\n\r\nOriginal problem ID: {EVENT.ID}\r\n{TRIGGER.URL}','{USER.FULLNAME} acknowledged problem at {ACK.DATE} {ACK.TIME} with the following message:\r\n{ACK.MESSAGE}\r\n\r\nCurrent problem status is {EVENT.STATUS}');
INSERT INTO operations (operationid, actionid, operationtype, esc_period, esc_step_from, esc_step_to, evaltype) VALUES (93, 91, 0, 0, 1, 1, 0);
INSERT INTO opmessage (operationid, default_msg, subject, message, mediatypeid) VALUES (93,1,'Problem: {EVENT.NAME}','Problem started at {EVENT.TIME} on {EVENT.DATE}\r\nProblem name: {EVENT.NAME}\r\nHost: {HOST.NAME}\r\nSeverity: {TRIGGER.SEVERITY}\r\n\r\nOriginal problem ID: {EVENT.ID}\r\n{TRIGGER.URL}',NULL);
INSERT INTO opmessage_grp (opmessage_grpid, operationid, usrgrpid) VALUES (94, 93, 47);

-- event correlation
INSERT INTO correlation (correlationid, name, description, evaltype, status, formula) VALUES (99000, 'Event correlation for delete', 'Test description delete', 0, 0, '');
INSERT INTO corr_condition (corr_conditionid, correlationid, type) VALUES (99000, 99000, 0);
INSERT INTO corr_condition_tag (corr_conditionid, tag) VALUES (99000, 'delete tag');
INSERT INTO corr_operation (corr_operationid, correlationid, type) VALUES (99000, 99000, 0);

INSERT INTO correlation (correlationid, name, description, evaltype, status, formula) VALUES (99001, 'Event correlation for update', 'Test description update', 0, 0, '');
INSERT INTO corr_condition (corr_conditionid, correlationid, type) VALUES (99001, 99001, 0);
INSERT INTO corr_condition_tag (corr_conditionid, tag) VALUES (99001, 'update tag');
INSERT INTO corr_operation (corr_operationid, correlationid, type) VALUES (99001, 99001, 0);

INSERT INTO correlation (correlationid, name, description, evaltype, status, formula) VALUES (99002, 'Event correlation for cancel', 'Test description cancel', 1, 0, '');
INSERT INTO corr_condition (corr_conditionid, correlationid, type) VALUES (99002, 99002, 0);
INSERT INTO corr_condition_tag (corr_conditionid, tag) VALUES (99002, 'cancel tag');
INSERT INTO corr_operation (corr_operationid, correlationid, type) VALUES (99002, 99002, 0);

INSERT INTO correlation (correlationid, name, description, evaltype, status, formula) VALUES (99003, 'Event correlation for clone', 'Test description clone', 0, 0, '');
INSERT INTO corr_condition (corr_conditionid, correlationid, type) VALUES (99003, 99003, 0);
INSERT INTO corr_condition_tag (corr_conditionid, tag) VALUES (99003, 'clone tag');
INSERT INTO corr_operation (corr_operationid, correlationid, type) VALUES (99003, 99003, 0);

-- discovery rules
INSERT INTO hosts (hostid, host, status, description) VALUES (99006, 'Api active proxy for discovery', 5, '');
INSERT INTO drules (druleid, proxy_hostid, name, iprange, delay, nextcheck, status) VALUES (10,NULL,'API discovery rule for delete 1','192.168.0.1-254','1h',0,0);
INSERT INTO dchecks (dcheckid, druleid, type, key_, snmp_community, ports, snmpv3_securityname, snmpv3_securitylevel, snmpv3_authpassphrase, snmpv3_privpassphrase, uniq, snmpv3_authprotocol, snmpv3_privprotocol, snmpv3_contextname) VALUES (10,10,4,'','','80','',0,'','',0,0,0,'');
INSERT INTO drules (druleid, proxy_hostid, name, iprange, delay, nextcheck, status) VALUES (11,99006,'API discovery rule for delete with proxy','192.168.0.1-254','1h',0,0);
INSERT INTO dchecks (dcheckid, druleid, type, key_, snmp_community, ports, snmpv3_securityname, snmpv3_securitylevel, snmpv3_authpassphrase, snmpv3_privpassphrase, uniq, snmpv3_authprotocol, snmpv3_privprotocol, snmpv3_contextname) VALUES (11,11,9,'agent.ping','','10050','',0,'','',0,0,0,'');
INSERT INTO drules (druleid, proxy_hostid, name, iprange, delay, nextcheck, status) VALUES (12,NULL,'API discovery rule for delete 3','192.168.0.1-254','1h',0,0);
INSERT INTO dchecks (dcheckid, druleid, type, key_, snmp_community, ports, snmpv3_securityname, snmpv3_securitylevel, snmpv3_authpassphrase, snmpv3_privpassphrase, uniq, snmpv3_authprotocol, snmpv3_privprotocol, snmpv3_contextname) VALUES (12,12,15,'','','23','',0,'','',0,0,0,'');
INSERT INTO drules (druleid, proxy_hostid, name, iprange, delay, nextcheck, status) VALUES (13,NULL,'API discovery rule for delete 4','192.168.0.1-254','1h',0,0);
INSERT INTO dchecks (dcheckid, druleid, type, key_, snmp_community, ports, snmpv3_securityname, snmpv3_securitylevel, snmpv3_authpassphrase, snmpv3_privpassphrase, uniq, snmpv3_authprotocol, snmpv3_privprotocol, snmpv3_contextname) VALUES (13,13,3,'','','21','',0,'','',0,0,0,'');
INSERT INTO drules (druleid, proxy_hostid, name, iprange, delay, nextcheck, status) VALUES (14,NULL,'API discovery rule for delete 5','192.168.0.1-254','1h',0,0);
INSERT INTO dchecks (dcheckid, druleid, type, key_, snmp_community, ports, snmpv3_securityname, snmpv3_securitylevel, snmpv3_authpassphrase, snmpv3_privpassphrase, uniq, snmpv3_authprotocol, snmpv3_privprotocol, snmpv3_contextname) VALUES (14,14,3,'','','21','',0,'','',0,0,0,'');
INSERT INTO drules (druleid, proxy_hostid, name, iprange, delay, nextcheck, status) VALUES (15,NULL,'API discovery rule used in action','192.168.0.1-254','1h',0,0);
INSERT INTO dchecks (dcheckid, druleid, type, key_, snmp_community, ports, snmpv3_securityname, snmpv3_securitylevel, snmpv3_authpassphrase, snmpv3_privpassphrase, uniq, snmpv3_authprotocol, snmpv3_privprotocol, snmpv3_contextname) VALUES (15,15,3,'','','21','',0,'','',0,0,0,'');
INSERT INTO dchecks (dcheckid, druleid, type, key_, snmp_community, ports, snmpv3_securityname, snmpv3_securitylevel, snmpv3_authpassphrase, snmpv3_privpassphrase, uniq, snmpv3_authprotocol, snmpv3_privprotocol, snmpv3_contextname) VALUES (16,15,9,'agent.ping','','10050','',0,'','',0,0,0,'');
INSERT INTO actions (actionid, name, eventsource, evaltype, status, esc_period, def_shortdata, def_longdata, r_shortdata, r_longdata, ack_longdata) VALUES (95,'API action for Discovery check',1,0,0,'1h','Discovery: {DISCOVERY.DEVICE.STATUS} {DISCOVERY.DEVICE.IPADDRESS}','Discovery rule: {DISCOVERY.RULE.NAME}','','','');
INSERT INTO operations (operationid, actionid, operationtype, esc_period, esc_step_from, esc_step_to, evaltype) VALUES (95, 95, 0, 0, 1, 1, 0);
INSERT INTO opmessage (operationid, default_msg, subject, message, mediatypeid) VALUES (95, 1, 'Discovery: {DISCOVERY.DEVICE.STATUS} {DISCOVERY.DEVICE.IPADDRESS}', 'Discovery rule: {DISCOVERY.RULE.NAME}', NULL);
INSERT INTO opmessage_grp (opmessage_grpid, operationid, usrgrpid) VALUES (95, 95, 47);
INSERT INTO conditions (conditionid, actionid, conditiontype, operator, value, value2) VALUES (95,95,19,0,'16','');

INSERT INTO drules (druleid, proxy_hostid, name, iprange, delay, nextcheck, status) VALUES (16,NULL,'API discovery rule used in action 2','192.168.0.1-254','1h',0,0);
INSERT INTO dchecks (dcheckid, druleid, type, key_, snmp_community, ports, snmpv3_securityname, snmpv3_securitylevel, snmpv3_authpassphrase, snmpv3_privpassphrase, uniq, snmpv3_authprotocol, snmpv3_privprotocol, snmpv3_contextname) VALUES (17,16,0,'','','22','',0,'','',0,0,0,'');
INSERT INTO actions (actionid, name, eventsource, evaltype, status, esc_period, def_shortdata, def_longdata, r_shortdata, r_longdata, ack_longdata) VALUES (96,'API action for Discovery rule',1,0,0,'1h','Discovery: {DISCOVERY.DEVICE.STATUS} {DISCOVERY.DEVICE.IPADDRESS}','Discovery rule: {DISCOVERY.RULE.NAME}','','','');
INSERT INTO operations (operationid, actionid, operationtype, esc_period, esc_step_from, esc_step_to, evaltype) VALUES (96, 96, 0, 0, 1, 1, 0);
INSERT INTO opmessage (operationid, default_msg, subject, message, mediatypeid) VALUES (96, 1, 'Discovery: {DISCOVERY.DEVICE.STATUS} {DISCOVERY.DEVICE.IPADDRESS}', 'Discovery rule: {DISCOVERY.RULE.NAME}', NULL);
INSERT INTO opmessage_grp (opmessage_grpid, operationid, usrgrpid) VALUES (96, 96, 7);
INSERT INTO conditions (conditionid, actionid, conditiontype, operator, value, value2) VALUES (97,96,18,0,'16','');

-- dependent items: BEGIN
INSERT INTO hstgrp (groupid, name) VALUES (1001, 'dependent.items');
INSERT INTO hstgrp (groupid, name) VALUES (1002, 'dependent.items/templates');
INSERT INTO hstgrp (groupid, name) VALUES (1003, 'dependent.items/hosts');

-- dependent items: dependent.items.template.1
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1001, 'dependent.items.template.1', 'dependent.items.template.1', 3, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1001, 1001, 1002);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1001, 1001, 'master.item.1'                 ,  2, 'master.item.1'                 , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1002, 1001, 'dependent.item.1.1'            , 18, 'dependent.item.1.1'            , 1, '90d', 0, 1001, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1003, 1001, 'dependent.item.1.2'            , 18, 'dependent.item.1.2'            , 1, '90d', 0, 1001, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1004, 1001, 'dependent.item.1.1.1'          , 18, 'dependent.item.1.1.1'          , 1, '90d', 0, 1002, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1005, 1001, 'dependent.item.1.1.2'          , 18, 'dependent.item.1.1.2'          , 1, '90d', 0, 1002, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1006, 1001, 'dependent.item.1.2.1'          , 18, 'dependent.item.1.2.1'          , 1, '90d', 0, 1003, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1007, 1001, 'dependent.item.1.2.2'          , 18, 'dependent.item.1.2.2'          , 1, '90d', 0, 1003, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1008, 1001, 'dependent.item.1.1.1.1'        , 18, 'dependent.item.1.1.1.1'        , 1, '90d', 0, 1004, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1009, 1001, 'dependent.item.1.1.1.2'        , 18, 'dependent.item.1.1.1.2'        , 1, '90d', 0, 1004, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1010, 1001, 'dependent.item.1.1.2.1'        , 18, 'dependent.item.1.1.2.1'        , 1, '90d', 0, 1005, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1011, 1001, 'dependent.item.1.1.2.2'        , 18, 'dependent.item.1.1.2.2'        , 1, '90d', 0, 1005, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1012, 1001, 'dependent.item.1.2.1.1'        , 18, 'dependent.item.1.2.1.1'        , 1, '90d', 0, 1006, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1013, 1001, 'dependent.item.1.2.1.2'        , 18, 'dependent.item.1.2.1.2'        , 1, '90d', 0, 1006, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1014, 1001, 'dependent.item.1.2.2.1'        , 18, 'dependent.item.1.2.2.1'        , 1, '90d', 0, 1007, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1015, 1001, 'dependent.item.1.2.2.2'        , 18, 'dependent.item.1.2.2.2'        , 1, '90d', 0, 1007, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1016, 1001, 'trap.1'                        ,  2, 'trap.1'                        , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (1017, 1001, 'discovery.rule.1'              ,  2, 'discovery.rule.1'              , 4,        0,       NULL, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1018, 1001, 'master.item.proto.1'           ,  2, 'master.item.proto.1'           , 1, '90d', 0, NULL, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1019, 1001, 'dependent.item.proto.1.1'      , 18, 'dependent.item.proto.1.1'      , 1, '90d', 0, 1018, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1020, 1001, 'dependent.item.proto.1.2'      , 18, 'dependent.item.proto.1.2'      , 1, '90d', 0, 1018, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1021, 1001, 'dependent.item.proto.1.1.1'    , 18, 'dependent.item.proto.1.1.1'    , 1, '90d', 0, 1019, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1022, 1001, 'dependent.item.proto.1.1.2'    , 18, 'dependent.item.proto.1.1.2'    , 1, '90d', 0, 1019, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1023, 1001, 'dependent.item.proto.1.2.1'    , 18, 'dependent.item.proto.1.2.1'    , 1, '90d', 0, 1020, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1024, 1001, 'dependent.item.proto.1.2.2'    , 18, 'dependent.item.proto.1.2.2'    , 1, '90d', 0, 1020, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1025, 1001, 'dependent.item.proto.1.1.1.1'  , 18, 'dependent.item.proto.1.1.1.1'  , 1, '90d', 0, 1021, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1026, 1001, 'dependent.item.proto.1.1.1.2'  , 18, 'dependent.item.proto.1.1.1.2'  , 1, '90d', 0, 1021, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1027, 1001, 'dependent.item.proto.1.1.2.1'  , 18, 'dependent.item.proto.1.1.2.1'  , 1, '90d', 0, 1022, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1028, 1001, 'dependent.item.proto.1.1.2.2'  , 18, 'dependent.item.proto.1.1.2.2'  , 1, '90d', 0, 1022, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1029, 1001, 'dependent.item.proto.1.2.1.1'  , 18, 'dependent.item.proto.1.2.1.1'  , 1, '90d', 0, 1023, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1030, 1001, 'dependent.item.proto.1.2.1.2'  , 18, 'dependent.item.proto.1.2.1.2'  , 1, '90d', 0, 1023, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1031, 1001, 'dependent.item.proto.1.2.2.1'  , 18, 'dependent.item.proto.1.2.2.1'  , 1, '90d', 0, 1024, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1032, 1001, 'dependent.item.proto.1.2.2.2'  , 18, 'dependent.item.proto.1.2.2.2'  , 1, '90d', 0, 1024, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1033, 1001, 'item.proto.1'                  ,  2, 'item.proto.1'                  , 1, '90d', 0, NULL, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status, master_itemid, templateid, params, description, posts, headers, lifetime, flags) VALUES (1034, 1001, 'dependent.discovery.rule.1.1'  , 18, 'dependent.discovery.rule.1.1'  , 4,        0, 1001, NULL, '', '', '', '', '30d', 1);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15001, 1017, 1018);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15002, 1017, 1019);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15003, 1017, 1020);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15004, 1017, 1021);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15005, 1017, 1022);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15006, 1017, 1023);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15007, 1017, 1024);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15008, 1017, 1025);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15009, 1017, 1026);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15010, 1017, 1027);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15011, 1017, 1028);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15012, 1017, 1029);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15013, 1017, 1030);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15014, 1017, 1031);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15015, 1017, 1032);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (15016, 1017, 1033);

-- dependent items: dependent.items.template.1.1
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1002, 'dependent.items.template.1.1', 'dependent.items.template.1.1', 3, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1002, 1002, 1002);
INSERT INTO hosts_templates (hosttemplateid, hostid, templateid) VALUES (1001, 1002, 1001);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1101, 1002, 'master.item.1'                 ,  2, 'master.item.1'                 , 1, '90d', 0, NULL, 1001, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1102, 1002, 'dependent.item.1.1'            , 18, 'dependent.item.1.1'            , 1, '90d', 0, 1101, 1002, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1103, 1002, 'dependent.item.1.2'            , 18, 'dependent.item.1.2'            , 1, '90d', 0, 1101, 1003, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1104, 1002, 'dependent.item.1.1.1'          , 18, 'dependent.item.1.1.1'          , 1, '90d', 0, 1102, 1004, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1105, 1002, 'dependent.item.1.1.2'          , 18, 'dependent.item.1.1.2'          , 1, '90d', 0, 1102, 1005, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1106, 1002, 'dependent.item.1.2.1'          , 18, 'dependent.item.1.2.1'          , 1, '90d', 0, 1103, 1006, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1107, 1002, 'dependent.item.1.2.2'          , 18, 'dependent.item.1.2.2'          , 1, '90d', 0, 1103, 1007, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1108, 1002, 'dependent.item.1.1.1.1'        , 18, 'dependent.item.1.1.1.1'        , 1, '90d', 0, 1104, 1008, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1109, 1002, 'dependent.item.1.1.1.2'        , 18, 'dependent.item.1.1.1.2'        , 1, '90d', 0, 1104, 1009, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1110, 1002, 'dependent.item.1.1.2.1'        , 18, 'dependent.item.1.1.2.1'        , 1, '90d', 0, 1105, 1010, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1111, 1002, 'dependent.item.1.1.2.2'        , 18, 'dependent.item.1.1.2.2'        , 1, '90d', 0, 1105, 1011, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1112, 1002, 'dependent.item.1.2.1.1'        , 18, 'dependent.item.1.2.1.1'        , 1, '90d', 0, 1106, 1012, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1113, 1002, 'dependent.item.1.2.1.2'        , 18, 'dependent.item.1.2.1.2'        , 1, '90d', 0, 1106, 1013, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1114, 1002, 'dependent.item.1.2.2.1'        , 18, 'dependent.item.1.2.2.1'        , 1, '90d', 0, 1107, 1014, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1115, 1002, 'dependent.item.1.2.2.2'        , 18, 'dependent.item.1.2.2.2'        , 1, '90d', 0, 1107, 1015, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1116, 1002, 'trap.1'                        ,  2, 'trap.1'                        , 1, '90d', 0, NULL, 1016, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (1117, 1002, 'discovery.rule.1'              ,  2, 'discovery.rule.1'              , 4,        0,       1017, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1118, 1002, 'master.item.proto.1'           ,  2, 'master.item.proto.1'           , 1, '90d', 0, NULL, 1018, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1119, 1002, 'dependent.item.proto.1.1'      , 18, 'dependent.item.proto.1.1'      , 1, '90d', 0, 1118, 1019, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1120, 1002, 'dependent.item.proto.1.2'      , 18, 'dependent.item.proto.1.2'      , 1, '90d', 0, 1118, 1020, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1121, 1002, 'dependent.item.proto.1.1.1'    , 18, 'dependent.item.proto.1.1.1'    , 1, '90d', 0, 1119, 1021, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1122, 1002, 'dependent.item.proto.1.1.2'    , 18, 'dependent.item.proto.1.1.2'    , 1, '90d', 0, 1119, 1022, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1123, 1002, 'dependent.item.proto.1.2.1'    , 18, 'dependent.item.proto.1.2.1'    , 1, '90d', 0, 1120, 1023, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1124, 1002, 'dependent.item.proto.1.2.2'    , 18, 'dependent.item.proto.1.2.2'    , 1, '90d', 0, 1120, 1024, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1125, 1002, 'dependent.item.proto.1.1.1.1'  , 18, 'dependent.item.proto.1.1.1.1'  , 1, '90d', 0, 1121, 1025, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1126, 1002, 'dependent.item.proto.1.1.1.2'  , 18, 'dependent.item.proto.1.1.1.2'  , 1, '90d', 0, 1121, 1026, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1127, 1002, 'dependent.item.proto.1.1.2.1'  , 18, 'dependent.item.proto.1.1.2.1'  , 1, '90d', 0, 1122, 1027, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1128, 1002, 'dependent.item.proto.1.1.2.2'  , 18, 'dependent.item.proto.1.1.2.2'  , 1, '90d', 0, 1122, 1028, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1129, 1002, 'dependent.item.proto.1.2.1.1'  , 18, 'dependent.item.proto.1.2.1.1'  , 1, '90d', 0, 1123, 1029, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1130, 1002, 'dependent.item.proto.1.2.1.2'  , 18, 'dependent.item.proto.1.2.1.2'  , 1, '90d', 0, 1123, 1030, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1131, 1002, 'dependent.item.proto.1.2.2.1'  , 18, 'dependent.item.proto.1.2.2.1'  , 1, '90d', 0, 1124, 1031, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1132, 1002, 'dependent.item.proto.1.2.2.2'  , 18, 'dependent.item.proto.1.2.2.2'  , 1, '90d', 0, 1124, 1032, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1133, 1002, 'item.proto.1'                  ,  2, 'item.proto.1'                  , 1, '90d', 0, NULL, 1033, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status, master_itemid, templateid, params, description, posts, headers, lifetime, flags) VALUES (1134, 1002, 'dependent.discovery.rule.1.1'  , 18, 'dependent.discovery.rule.1.1'  , 4,        0, 1101, NULL, '', '', '', '', '30d', 1);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5101, 1117, 1118);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5102, 1117, 1119);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5103, 1117, 1120);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5104, 1117, 1121);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5105, 1117, 1122);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5106, 1117, 1123);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5107, 1117, 1124);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5108, 1117, 1125);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5109, 1117, 1126);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5110, 1117, 1127);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5111, 1117, 1128);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5112, 1117, 1129);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5113, 1117, 1130);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5114, 1117, 1131);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5115, 1117, 1132);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5116, 1117, 1133);

-- dependent items: dependent.items.template.1.2
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1003, 'dependent.items.template.1.2', 'dependent.items.template.1.2', 3, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1003, 1003, 1002);
INSERT INTO hosts_templates (hosttemplateid, hostid, templateid) VALUES (1002, 1003, 1001);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1201, 1003, 'master.item.1'                 ,  2, 'master.item.1'                 , 1, '90d', 0, NULL, 1001, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1202, 1003, 'dependent.item.1.1'            , 18, 'dependent.item.1.1'            , 1, '90d', 0, 1201, 1002, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1203, 1003, 'dependent.item.1.2'            , 18, 'dependent.item.1.2'            , 1, '90d', 0, 1201, 1003, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1204, 1003, 'dependent.item.1.1.1'          , 18, 'dependent.item.1.1.1'          , 1, '90d', 0, 1202, 1004, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1205, 1003, 'dependent.item.1.1.2'          , 18, 'dependent.item.1.1.2'          , 1, '90d', 0, 1202, 1005, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1206, 1003, 'dependent.item.1.2.1'          , 18, 'dependent.item.1.2.1'          , 1, '90d', 0, 1203, 1006, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1207, 1003, 'dependent.item.1.2.2'          , 18, 'dependent.item.1.2.2'          , 1, '90d', 0, 1203, 1007, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1208, 1003, 'dependent.item.1.1.1.1'        , 18, 'dependent.item.1.1.1.1'        , 1, '90d', 0, 1204, 1008, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1209, 1003, 'dependent.item.1.1.1.2'        , 18, 'dependent.item.1.1.1.2'        , 1, '90d', 0, 1204, 1009, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1210, 1003, 'dependent.item.1.1.2.1'        , 18, 'dependent.item.1.1.2.1'        , 1, '90d', 0, 1205, 1010, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1211, 1003, 'dependent.item.1.1.2.2'        , 18, 'dependent.item.1.1.2.2'        , 1, '90d', 0, 1205, 1011, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1212, 1003, 'dependent.item.1.2.1.1'        , 18, 'dependent.item.1.2.1.1'        , 1, '90d', 0, 1206, 1012, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1213, 1003, 'dependent.item.1.2.1.2'        , 18, 'dependent.item.1.2.1.2'        , 1, '90d', 0, 1206, 1013, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1214, 1003, 'dependent.item.1.2.2.1'        , 18, 'dependent.item.1.2.2.1'        , 1, '90d', 0, 1207, 1014, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1215, 1003, 'dependent.item.1.2.2.2'        , 18, 'dependent.item.1.2.2.2'        , 1, '90d', 0, 1207, 1015, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1216, 1003, 'trap.1'                        ,  2, 'trap.1'                        , 1, '90d', 0, NULL, 1016, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (1217, 1003, 'discovery.rule.1'              ,  2, 'discovery.rule.1'              , 4,        0,       1017, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1218, 1003, 'master.item.proto.1'           ,  2, 'master.item.proto.1'           , 1, '90d', 0, NULL, 1018, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1219, 1003, 'dependent.item.proto.1.1'      , 18, 'dependent.item.proto.1.1'      , 1, '90d', 0, 1218, 1019, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1220, 1003, 'dependent.item.proto.1.2'      , 18, 'dependent.item.proto.1.2'      , 1, '90d', 0, 1218, 1020, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1221, 1003, 'dependent.item.proto.1.1.1'    , 18, 'dependent.item.proto.1.1.1'    , 1, '90d', 0, 1219, 1021, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1222, 1003, 'dependent.item.proto.1.1.2'    , 18, 'dependent.item.proto.1.1.2'    , 1, '90d', 0, 1219, 1022, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1223, 1003, 'dependent.item.proto.1.2.1'    , 18, 'dependent.item.proto.1.2.1'    , 1, '90d', 0, 1220, 1023, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1224, 1003, 'dependent.item.proto.1.2.2'    , 18, 'dependent.item.proto.1.2.2'    , 1, '90d', 0, 1220, 1024, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1225, 1003, 'dependent.item.proto.1.1.1.1'  , 18, 'dependent.item.proto.1.1.1.1'  , 1, '90d', 0, 1221, 1025, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1226, 1003, 'dependent.item.proto.1.1.1.2'  , 18, 'dependent.item.proto.1.1.1.2'  , 1, '90d', 0, 1221, 1026, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1227, 1003, 'dependent.item.proto.1.1.2.1'  , 18, 'dependent.item.proto.1.1.2.1'  , 1, '90d', 0, 1222, 1027, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1228, 1003, 'dependent.item.proto.1.1.2.2'  , 18, 'dependent.item.proto.1.1.2.2'  , 1, '90d', 0, 1222, 1028, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1229, 1003, 'dependent.item.proto.1.2.1.1'  , 18, 'dependent.item.proto.1.2.1.1'  , 1, '90d', 0, 1223, 1029, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1230, 1003, 'dependent.item.proto.1.2.1.2'  , 18, 'dependent.item.proto.1.2.1.2'  , 1, '90d', 0, 1223, 1030, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1231, 1003, 'dependent.item.proto.1.2.2.1'  , 18, 'dependent.item.proto.1.2.2.1'  , 1, '90d', 0, 1224, 1031, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1232, 1003, 'dependent.item.proto.1.2.2.2'  , 18, 'dependent.item.proto.1.2.2.2'  , 1, '90d', 0, 1224, 1032, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1233, 1003, 'item.proto.1'                  ,  2, 'item.proto.1'                  , 1, '90d', 0, NULL, 1033, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status, master_itemid, templateid, params, description, posts, headers, lifetime, flags) VALUES (1234, 1003, 'dependent.discovery.rule.1.1'  , 18, 'dependent.discovery.rule.1.1'  , 4,        0, 1201, NULL, '', '', '', '', '30d', 1);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5201, 1217, 1218);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5202, 1217, 1219);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5203, 1217, 1220);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5204, 1217, 1221);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5205, 1217, 1222);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5206, 1217, 1223);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5207, 1217, 1224);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5208, 1217, 1225);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5209, 1217, 1226);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5210, 1217, 1227);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5211, 1217, 1228);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5212, 1217, 1229);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5213, 1217, 1230);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5214, 1217, 1231);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5215, 1217, 1232);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5216, 1217, 1233);

-- dependent items: dependent.items.host.1
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1004, 'dependent.items.host.1', 'dependent.items.host.1', 0, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1004, 1004, 1003);
INSERT INTO interface (interfaceid, hostid, type, ip, useip, port, main) VALUES (1001, 1004, 1, '127.0.0.1', 1, '10050', 1);
INSERT INTO hosts_templates (hosttemplateid, hostid, templateid) VALUES (1003, 1004, 1002);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1301, 1004, 'master.item.1'                 ,  2, 'master.item.1'                 , 1, '90d', 0, NULL, 1101, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1302, 1004, 'dependent.item.1.1'            , 18, 'dependent.item.1.1'            , 1, '90d', 0, 1301, 1102, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1303, 1004, 'dependent.item.1.2'            , 18, 'dependent.item.1.2'            , 1, '90d', 0, 1301, 1103, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1304, 1004, 'dependent.item.1.1.1'          , 18, 'dependent.item.1.1.1'          , 1, '90d', 0, 1302, 1104, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1305, 1004, 'dependent.item.1.1.2'          , 18, 'dependent.item.1.1.2'          , 1, '90d', 0, 1302, 1105, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1306, 1004, 'dependent.item.1.2.1'          , 18, 'dependent.item.1.2.1'          , 1, '90d', 0, 1303, 1106, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1307, 1004, 'dependent.item.1.2.2'          , 18, 'dependent.item.1.2.2'          , 1, '90d', 0, 1303, 1107, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1308, 1004, 'dependent.item.1.1.1.1'        , 18, 'dependent.item.1.1.1.1'        , 1, '90d', 0, 1304, 1108, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1309, 1004, 'dependent.item.1.1.1.2'        , 18, 'dependent.item.1.1.1.2'        , 1, '90d', 0, 1304, 1109, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1310, 1004, 'dependent.item.1.1.2.1'        , 18, 'dependent.item.1.1.2.1'        , 1, '90d', 0, 1305, 1110, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1311, 1004, 'dependent.item.1.1.2.2'        , 18, 'dependent.item.1.1.2.2'        , 1, '90d', 0, 1305, 1111, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1312, 1004, 'dependent.item.1.2.1.1'        , 18, 'dependent.item.1.2.1.1'        , 1, '90d', 0, 1306, 1112, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1313, 1004, 'dependent.item.1.2.1.2'        , 18, 'dependent.item.1.2.1.2'        , 1, '90d', 0, 1306, 1113, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1314, 1004, 'dependent.item.1.2.2.1'        , 18, 'dependent.item.1.2.2.1'        , 1, '90d', 0, 1307, 1114, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1315, 1004, 'dependent.item.1.2.2.2'        , 18, 'dependent.item.1.2.2.2'        , 1, '90d', 0, 1307, 1115, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1316, 1004, 'trap.1'                        ,  2, 'trap.1'                        , 1, '90d', 0, NULL, 1116, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (1317, 1004, 'discovery.rule.1'              ,  2, 'discovery.rule.1'              , 4,        0,       1117, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1318, 1004, 'master.item.proto.1'           ,  2, 'master.item.proto.1'           , 1, '90d', 0, NULL, 1118, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1319, 1004, 'dependent.item.proto.1.1'      , 18, 'dependent.item.proto.1.1'      , 1, '90d', 0, 1318, 1119, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1320, 1004, 'dependent.item.proto.1.2'      , 18, 'dependent.item.proto.1.2'      , 1, '90d', 0, 1318, 1120, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1321, 1004, 'dependent.item.proto.1.1.1'    , 18, 'dependent.item.proto.1.1.1'    , 1, '90d', 0, 1319, 1121, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1322, 1004, 'dependent.item.proto.1.1.2'    , 18, 'dependent.item.proto.1.1.2'    , 1, '90d', 0, 1319, 1122, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1323, 1004, 'dependent.item.proto.1.2.1'    , 18, 'dependent.item.proto.1.2.1'    , 1, '90d', 0, 1320, 1123, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1324, 1004, 'dependent.item.proto.1.2.2'    , 18, 'dependent.item.proto.1.2.2'    , 1, '90d', 0, 1320, 1124, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1325, 1004, 'dependent.item.proto.1.1.1.1'  , 18, 'dependent.item.proto.1.1.1.1'  , 1, '90d', 0, 1321, 1125, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1326, 1004, 'dependent.item.proto.1.1.1.2'  , 18, 'dependent.item.proto.1.1.1.2'  , 1, '90d', 0, 1321, 1126, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1327, 1004, 'dependent.item.proto.1.1.2.1'  , 18, 'dependent.item.proto.1.1.2.1'  , 1, '90d', 0, 1322, 1127, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1328, 1004, 'dependent.item.proto.1.1.2.2'  , 18, 'dependent.item.proto.1.1.2.2'  , 1, '90d', 0, 1322, 1128, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1329, 1004, 'dependent.item.proto.1.2.1.1'  , 18, 'dependent.item.proto.1.2.1.1'  , 1, '90d', 0, 1323, 1129, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1330, 1004, 'dependent.item.proto.1.2.1.2'  , 18, 'dependent.item.proto.1.2.1.2'  , 1, '90d', 0, 1323, 1130, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1331, 1004, 'dependent.item.proto.1.2.2.1'  , 18, 'dependent.item.proto.1.2.2.1'  , 1, '90d', 0, 1324, 1131, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1332, 1004, 'dependent.item.proto.1.2.2.2'  , 18, 'dependent.item.proto.1.2.2.2'  , 1, '90d', 0, 1324, 1132, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1333, 1004, 'item.proto.1'                  ,  2, 'item.proto.1'                  , 1, '90d', 0, NULL, 1133, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status, master_itemid, templateid, params, description, posts, headers, lifetime, flags) VALUES (1334, 1004, 'dependent.discovery.rule.1.1'  , 18, 'dependent.discovery.rule.1.1'  , 4,        0, 1301, NULL, '', '', '', '', '30d', 1);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5301, 1317, 1318);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5302, 1317, 1319);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5303, 1317, 1320);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5304, 1317, 1321);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5305, 1317, 1322);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5306, 1317, 1323);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5307, 1317, 1324);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5308, 1317, 1325);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5309, 1317, 1326);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5310, 1317, 1327);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5311, 1317, 1328);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5312, 1317, 1329);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5313, 1317, 1330);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5314, 1317, 1331);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5315, 1317, 1332);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5316, 1317, 1333);

-- dependent items: dependent.items.template.2
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1005, 'dependent.items.template.2', 'dependent.items.template.2', 3, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1005, 1005, 1002);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1401, 1005, 'master.item.1'                 ,  2, 'master.item.1'                 , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1402, 1005, 'dependent.item.1.1'            , 18, 'dependent.item.1.1'            , 1, '90d', 0, 1401, NULL, '', '', '', '');

-- dependent items: dependent.items.host.2
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1006, 'dependent.items.host.2', 'dependent.items.host.2', 0, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1006, 1006, 1003);
INSERT INTO interface (interfaceid, hostid, type, ip, useip, port, main) VALUES (1002, 1006, 1, '127.0.0.1', 1, '10050', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1501, 1006, 'dependent.item.1.1'            ,  2, 'dependent.item.1.1'            , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1502, 1006, 'dependent.item.1.1.1'          , 18, 'dependent.item.1.1.1'          , 1, '90d', 0, 1501, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1503, 1006, 'dependent.item.1.1.1.1'        , 18, 'dependent.item.1.1.1.1'        , 1, '90d', 0, 1502, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1504, 1006, 'dependent.item.1.1.1.1.1'      , 18, 'dependent.item.1.1.1.1.1'      , 1, '90d', 0, 1503, NULL, '', '', '', '');

-- dependent items: dependent.items.host.3
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1007, 'dependent.items.host.3', 'dependent.items.host.3', 0, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1007, 1007, 1003);
INSERT INTO interface (interfaceid, hostid, type, ip, useip, port, main) VALUES (1003, 1007, 1, '127.0.0.1', 1, '10050', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1601, 1007, 'dependent.item.1.1'            ,  2, 'dependent.item.1.1'            , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (1602, 1007, 'discovery.rule.1'              ,  2, 'discovery.rule.1'              , 4,        0,       NULL, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1603, 1007, 'dependent.item.proto.1.1.1'    , 18, 'dependent.item.proto.1.1.1'    , 1, '90d', 0, 1601, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1604, 1007, 'dependent.item.proto.1.1.1.1'  , 18, 'dependent.item.proto.1.1.1.1'  , 1, '90d', 0, 1603, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (1605, 1007, 'dependent.item.proto.1.1.1.1.1', 18, 'dependent.item.proto.1.1.1.1.1', 1, '90d', 0, 1604, NULL, '', '', '', '',        2);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5601, 1602, 1603);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5602, 1602, 1604);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (5603, 1602, 1605);

-- dependent items: dependent.items.template.4
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1008, 'dependent.items.template.4', 'dependent.items.template.4', 3, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1008, 1008, 1002);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1701, 1008, 'item.1'                        ,  2, 'item.1'                        , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1702, 1008, 'item.2'                        ,  2, 'item.2'                        , 1, '90d', 0, NULL, NULL, '', '', '', '');

-- dependent items: dependent.items.host.4
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1009, 'dependent.items.host.4', 'dependent.items.host.4', 0, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1009, 1009, 1003);
INSERT INTO interface (interfaceid, hostid, type, ip, useip, port, main) VALUES (1004, 1009, 1, '127.0.0.1', 1, '10050', 1);
INSERT INTO hosts_templates (hosttemplateid, hostid, templateid) VALUES (1004, 1009, 1008);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1801, 1009, 'item.1'                        ,  2, 'item.1'                        , 1, '90d', 0, NULL, 1701, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1802, 1009, 'item.2'                        ,  2, 'item.2'                        , 1, '90d', 0, NULL, 1702, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1803, 1009, 'item.3'                        , 18, 'item.3'                        , 1, '90d', 0, 1802, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1804, 1009, 'item.4'                        , 18, 'item.4'                        , 1, '90d', 0, 1803, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1805, 1009, 'item.5'                        , 18, 'item.5'                        , 1, '90d', 0, 1804, NULL, '', '', '', '');

-- dependent items: dependent.items.template.5
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1010, 'dependent.items.template.5', 'dependent.items.template.5', 3, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1010, 1010, 1002);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1901, 1010, 'item.1'                        ,  2, 'item.1'                        , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (1902, 1010, 'item.2'                        ,  2, 'item.2'                        , 1, '90d', 0, NULL, NULL, '', '', '', '');

-- dependent items: dependent.items.host.5
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1011, 'dependent.items.host.5', 'dependent.items.host.5', 0, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1011, 1011, 1003);
INSERT INTO interface (interfaceid, hostid, type, ip, useip, port, main) VALUES (1005, 1011, 1, '127.0.0.1', 1, '10050', 1);
INSERT INTO hosts_templates (hosttemplateid, hostid, templateid) VALUES (1005, 1011, 1010);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2001, 1011, 'item.1'                        ,  2, 'item.1'                        , 1, '90d', 0, NULL, 1901, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2002, 1011, 'item.2'                        ,  2, 'item.2'                        , 1, '90d', 0, NULL, 1902, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (2003, 1011, 'discovery.rule.1'              ,  2, 'discovery.rule.1'              , 4,        0,       NULL, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2004, 1011, 'item.proto.3'                  , 18, 'item.proto.3'                  , 1, '90d', 0, 2002, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2005, 1011, 'item.proto.4'                  , 18, 'item.proto.4'                  , 1, '90d', 0, 2004, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2006, 1011, 'item.proto.5'                  , 18, 'item.proto.5'                  , 1, '90d', 0, 2005, NULL, '', '', '', '',        2);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6001, 2003, 2004);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6002, 2003, 2005);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6003, 2003, 2006);

-- dependent items: dependent.items.template.6
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1012, 'dependent.items.template.6', 'dependent.items.template.6', 3, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1012, 1012, 1002);
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (2101, 1012, 'discovery.rule.1'              ,  2, 'discovery.rule.1'              , 4,        0,       NULL, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2102, 1012, 'item.proto.1'                  ,  2, 'item.proto.1'                  , 1, '90d', 0, NULL, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2103, 1012, 'item.proto.2'                  ,  2, 'item.proto.2'                  , 1, '90d', 0, NULL, NULL, '', '', '', '',        2);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6101, 2101, 2102);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6102, 2101, 2103);

-- dependent items: dependent.items.host.6
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1013, 'dependent.items.host.6', 'dependent.items.host.6', 0, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1013, 1013, 1003);
INSERT INTO interface (interfaceid, hostid, type, ip, useip, port, main) VALUES (1006, 1013, 1, '127.0.0.1', 1, '10050', 1);
INSERT INTO hosts_templates (hosttemplateid, hostid, templateid) VALUES (1006, 1013, 1012);
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (2201, 1013, 'discovery.rule.1'              ,  2, 'discovery.rule.1'              , 4,        0,       2101, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2202, 1013, 'item.proto.1'                  ,  2, 'item.proto.1'                  , 1, '90d', 0, NULL, 2102, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2203, 1013, 'item.proto.2'                  ,  2, 'item.proto.2'                  , 1, '90d', 0, NULL, 2103, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2204, 1013, 'item.proto.3'                  , 18, 'item.proto.3'                  , 1, '90d', 0, 2203, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2205, 1013, 'item.proto.4'                  , 18, 'item.proto.4'                  , 1, '90d', 0, 2204, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2206, 1013, 'item.proto.5'                  , 18, 'item.proto.5'                  , 1, '90d', 0, 2205, NULL, '', '', '', '',        2);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6201, 2201, 2202);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6202, 2201, 2203);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6203, 2201, 2204);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6204, 2201, 2205);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6205, 2201, 2206);

-- dependent items: dependent.items.host.7
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1014, 'dependent.items.host.7', 'dependent.items.host.7', 0, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1014, 1014, 1003);
INSERT INTO interface (interfaceid, hostid, type, ip, useip, port, main) VALUES (1007, 1014, 1, '127.0.0.1', 1, '10050', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (2301, 1014, 'net.if.disvovery'              ,  2, 'net.if.discovery'              , 4,        0,       NULL, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2302, 1014, 'net.if[{$IFNAME}]'             ,  2, 'net.if[{#IFNAME}]'             , 1, '90d', 0, NULL, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2303, 1014, 'net.if.in[{$IFNAME}]'          , 18, 'net.ifi.in[{#IFNAME}]'         , 1, '90d', 0, 2302, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2304, 1014, 'net.if[eth0]'                  ,  2, 'net.if[eth0]'                  , 1, '90d', 0, NULL, NULL, '', '', '', '',        4);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2305, 1014, 'net.if.in[eth0]'               , 18, 'net.ifi.in[eth0]'              , 1, '90d', 0, 2304, NULL, '', '', '', '',        4);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6301, 2301, 2302);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6302, 2301, 2303);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6303, 2302, 2304);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6304, 2303, 2305);

-- dependent items: dependent.items.host.8
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1015, 'dependent.items.host.8', 'dependent.items.host.8', 0, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1015, 1015, 1003);
INSERT INTO interface (interfaceid, hostid, type, ip, useip, port, main) VALUES (1008, 1015, 1, '127.0.0.1', 1, '10050', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2401, 1015, 'master.item.1'                 ,  2, 'master.item.1'                 , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2402, 1015, 'dependent.item.1.1'            , 18, 'dependent.item.1.1'            , 1, '90d', 0, 2401, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (2403, 1015, 'discovery.rule.1'              ,  2, 'discovery.rule.1'              , 4,        0,       NULL, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2404, 1015, 'master.item.proto.1'           ,  2, 'master.item.proto.1'           , 1, '90d', 0, NULL, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2405, 1015, 'dependent.item.proto.1.1'      , 18, 'dependent.item.proto.1.1'      , 1, '90d', 0, 2404, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (2406, 1015, 'discovery.rule.2'              ,  2, 'discovery.rule.2'              , 4,        0,       NULL, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2407, 1015, 'master.item.proto.2'           ,  2, 'master.item.proto.2'           , 1, '90d', 0, NULL, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2408, 1015, 'dependent.item.proto.2.1'      , 18, 'dependent.item.proto.2.1'      , 1, '90d', 0, 2407, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status, master_itemid, templateid, params, description, posts, headers, lifetime, flags) VALUES (2409, 1015, 'dependent.discovery.rule.1.1'  , 18, 'dependent.discovery.rule.1.1'  , 4,        0, 2401, NULL, '', '', '', '', '30d', 1);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6401, 2403, 2404);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6402, 2403, 2405);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6403, 2406, 2407);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6404, 2406, 2408);

-- dependent items: dependent.items.host.9
INSERT INTO hosts (hostid, host, name, status, description) VALUES (1016, 'dependent.items.host.9', 'dependent.items.host.9', 0, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1016, 1016, 1003);
INSERT INTO interface (interfaceid, hostid, type, ip, useip, port, main) VALUES (1009, 1016, 1, '127.0.0.1', 1, '10050', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2501, 1016, 'master.item.1'                 ,  2, 'master.item.1'                 , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2502, 1016, 'dependent.item.1.1'            , 18, 'dependent.item.1.1'            , 1, '90d', 0, 2501, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type,          status,                templateid, params, description, posts, headers, lifetime, flags) VALUES (2503, 1016, 'discovery.rule.1'              ,  2, 'discovery.rule.1'              , 4,        0,       NULL, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2504, 1016, 'master.item.proto.1'           ,  2, 'master.item.proto.1'           , 1, '90d', 0, NULL, NULL, '', '', '', '',        2);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers,           flags) VALUES (2505, 1016, 'dependent.item.proto.1.1'      , 18, 'dependent.item.proto.1.1'      , 1, '90d', 0, 2504, NULL, '', '', '', '',        2);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6501, 2503, 2504);
INSERT INTO item_discovery (itemdiscoveryid, parent_itemid, itemid) VALUES (6502, 2503, 2505);
-- dependent items: END

-- testTaskCreate
INSERT INTO hosts (hostid,host,name,status,description) VALUES (120001,'Has two items','Has two items',0,'');
INSERT INTO items (itemid,type,hostid,name,description,key_,delay,interfaceid,params,formula,url,posts,query_fields,headers) VALUES (110001,7,120001,'Agent-active','','agent.ping[]',30,NULL,'','','','','','');
INSERT INTO items (itemid,type,hostid,name,description,key_,delay,interfaceid,params,formula,url,posts,query_fields,headers) VALUES (110002,0,120001,'Agent-passive','','agent.ping',30,NULL,'','','','','','');
INSERT INTO hosts (hostid,host,name,status,description) VALUES (120003,'Template with item and lld rule','Template with item',3,'');
INSERT INTO items (itemid,type,hostid,name,description,key_,delay,interfaceid,params,formula,url,posts,query_fields,headers) VALUES (110004,0,120003,'templated-item','','agent.ping[]',30,NULL,'','','','','','');
INSERT INTO items (itemid,type,hostid,name,description,key_,delay,interfaceid,params,formula,url,posts,query_fields,headers,flags) VALUES (110005,0,120003,'templated-lld-rule','','agent.ping[-]',30,NULL,'','','','','','',1);

-- testHost_Delete and testHostGroup_Delete maintenance constraint
INSERT INTO hstgrp (groupid, name) VALUES (62001, 'Host group for maintenances');
INSERT INTO hstgrp (groupid, name) VALUES (62002, 'maintenance_has_only_group');
INSERT INTO hstgrp (groupid, name) VALUES (62003, 'maintenance_has_group_and_host');
INSERT INTO hstgrp (groupid, name) VALUES (62004, 'maintenance_group_1');
INSERT INTO hstgrp (groupid, name) VALUES (62005, 'maintenance_group_2');
INSERT INTO hosts (hostid, host, name, status, description) VALUES (61001, 'maintenance_has_only_host', 'maintenance_has_only_host', 0, '');
INSERT INTO hosts (hostid, host, name, status, description) VALUES (61002, 'maintenance_has_only_group', 'maintenance_has_only_group', 0, '');
INSERT INTO hosts (hostid, host, name, status, description) VALUES (61003, 'maintenance_has_group_and_host', 'maintenance_has_group_and_host', 0, '');
INSERT INTO hosts (hostid, host, name, status, description) VALUES (61004, 'maintenance_host_1', 'maintenance_host_1', 0, '');
INSERT INTO hosts (hostid, host, name, status, description) VALUES (61005, 'maintenance_host_2', 'maintenance_host_2', 0, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (50015, 61001, 62001);
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (50016, 61002, 62001);
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (50017, 61003, 62001);
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (50018, 61004, 62001);
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (50019, 61005, 62001);
INSERT INTO maintenances (maintenanceid, name, description, active_since, active_till) VALUES (60001, 'maintenance_has_only_host', '', 1539723600, 1539810000);
INSERT INTO maintenances (maintenanceid, name, description, active_since, active_till) VALUES (60002, 'maintenance_has_only_group', '', 1539723600, 1539810000);
INSERT INTO maintenances (maintenanceid, name, description, active_since, active_till) VALUES (60003, 'maintenance_has_group_and_host', '', 1539723600, 1539810000);
INSERT INTO maintenances (maintenanceid, name, description, active_since, active_till) VALUES (60004, 'maintenance_two_hosts', '', 1539723600, 1539810000);
INSERT INTO maintenances (maintenanceid, name, description, active_since, active_till) VALUES (60005, 'maintenance_two_groups', '', 1539723600, 1539810000);
INSERT INTO maintenances_hosts (maintenance_hostid, maintenanceid, hostid) VALUES (1, 60001, 61001);
INSERT INTO maintenances_hosts (maintenance_hostid, maintenanceid, hostid) VALUES (2, 60003, 61003);
INSERT INTO maintenances_hosts (maintenance_hostid, maintenanceid, hostid) VALUES (3, 60004, 61004);
INSERT INTO maintenances_hosts (maintenance_hostid, maintenanceid, hostid) VALUES (4, 60004, 61005);
INSERT INTO maintenances_groups (maintenance_groupid, maintenanceid, groupid) VALUES (1, 60002, 62002);
INSERT INTO maintenances_groups (maintenance_groupid, maintenanceid, groupid) VALUES (2, 60003, 62003);
INSERT INTO maintenances_groups (maintenance_groupid, maintenanceid, groupid) VALUES (3, 60005, 62004);
INSERT INTO maintenances_groups (maintenance_groupid, maintenanceid, groupid) VALUES (4, 60005, 62005);
INSERT INTO timeperiods (timeperiodid) VALUES (1);
INSERT INTO timeperiods (timeperiodid) VALUES (2);
INSERT INTO timeperiods (timeperiodid) VALUES (3);
INSERT INTO timeperiods (timeperiodid) VALUES (4);
INSERT INTO timeperiods (timeperiodid) VALUES (5);
INSERT INTO maintenances_windows (maintenance_timeperiodid, maintenanceid, timeperiodid) VALUES (1, 60001, 1);
INSERT INTO maintenances_windows (maintenance_timeperiodid, maintenanceid, timeperiodid) VALUES (2, 60002, 2);
INSERT INTO maintenances_windows (maintenance_timeperiodid, maintenanceid, timeperiodid) VALUES (3, 60003, 3);
INSERT INTO maintenances_windows (maintenance_timeperiodid, maintenanceid, timeperiodid) VALUES (4, 60004, 4);
INSERT INTO maintenances_windows (maintenance_timeperiodid, maintenanceid, timeperiodid) VALUES (5, 60005, 5);

-- testItemDelete
INSERT INTO hstgrp (groupid, name) VALUES (50018, 'with_lld_discovery');
INSERT INTO hosts (hostid, host, name, status, description) VALUES (120004, 'with_lld_discovery', 'with_lld_discovery', 0, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (120004, 120004, 50018);
INSERT INTO interface (interfaceid, hostid, main, type, useip, ip, dns, port) VALUES (2004, 120004, 1, 1, 1, '127.0.0.1', '', '10050');

INSERT INTO items (itemid, type, hostid, name, description, key_, delay, interfaceid, params, formula, url, posts, query_fields, headers, flags) VALUES (40070, 2, 120004, 'discovery_rule', '', 'discovery', '0', NULL, '', '', '', '', '', '', 1);
INSERT INTO items (itemid, type, hostid, name, description, key_, delay, interfaceid, params, formula, url, posts, query_fields, headers, value_type, flags) VALUES (40071, 2, 120004, 'Item {#NAME}', '', 'item[{#NAME}]', '0', NULL, '', '', '', '', '', '', 3, 2);
INSERT INTO triggers (triggerid, expression, description, priority, flags, comments) VALUES (30001,'{99000}>0','Trigger {#NAME}', 2, 2, '');
INSERT INTO functions (functionid, itemid, triggerid, name, parameter) VALUES (99000, 40071, 30001, 'last', '');
INSERT INTO item_discovery (itemdiscoveryid, itemid, parent_itemid, key_) VALUES (14045, 40071, 40070, '');

INSERT INTO items (itemid, type, hostid, name, description, key_, delay, interfaceid, params, formula, url, posts, query_fields, headers, value_type, flags) VALUES (40072, 2, 120004,' Item eth0', '', 'item[eth0]', '0', NULL, '', '', '', '', '', '', 3, 4);
INSERT INTO item_discovery (itemdiscoveryid, itemid, parent_itemid, key_) VALUES (14046, 40072, 40071, 'item[{#NAME}]');
INSERT INTO triggers (triggerid, expression, description, priority, flags, comments, value) VALUES (30002,'{99001}>0','Trigger eth0', 2, 4, '', 1);
INSERT INTO functions (functionid, itemid, triggerid, name, parameter) VALUES (99001, 40072, 30002, 'last', '');
INSERT INTO trigger_discovery (triggerid, parent_triggerid) VALUES (30002, 30001);

INSERT INTO items (itemid, type, hostid, name, description, key_, delay, interfaceid, params, formula, url, posts, query_fields, headers, value_type, flags, master_itemid) VALUES (40073, 18, 120004, 'Item_child {#NAME}', '', 'item_child[{#NAME}]', '0', NULL, '', '', '', '', '', '', 3, 2, 40071);
INSERT INTO triggers (triggerid, expression, description, priority, flags, comments) VALUES (30003,'{99002}>0','Trigger {#NAME}', 2, 2, '');
INSERT INTO functions (functionid, itemid, triggerid, name, parameter) VALUES (99002, 40073, 30003, 'last', '');
INSERT INTO item_discovery (itemdiscoveryid, itemid, parent_itemid, key_) VALUES (14047, 40073, 40070, '');

INSERT INTO items (itemid, type, hostid, name, description, key_, delay, interfaceid, params, formula, url, posts, query_fields, headers, value_type, flags, master_itemid) VALUES (40074, 18, 120004,' Item_child eth0', '', 'item_child[eth0]', '0', NULL, '', '', '', '', '', '', 3, 4, 40072);
INSERT INTO item_discovery (itemdiscoveryid, itemid, parent_itemid, key_) VALUES (14048, 40074, 40073, 'item[{#NAME}]');
INSERT INTO triggers (triggerid, expression, description, priority, flags, comments, value) VALUES (30004,'{99003}>0','Trigger eth0', 2, 4, '', 1);
INSERT INTO functions (functionid, itemid, triggerid, name, parameter) VALUES (99003, 40074, 30004, 'last', '');
INSERT INTO trigger_discovery (triggerid, parent_triggerid) VALUES (30004, 30003);

-- LLD rules
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,trends,status,params,description,flags,posts,headers) VALUES (110006,50009,50022,0,4,'API LLD rule 1','apilldrule1','30s','90d',0,0,'','',1,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,trends,status,params,description,flags,posts,headers) VALUES (110007,50009,50022,0,4,'API LLD rule 2','apilldrule2','30s','90d',0,0,'','',1,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,trends,status,params,description,flags,posts,headers) VALUES (110008,50009,50022,0,4,'API LLD rule 3','apilldrule3','30s','90d',0,0,'','',1,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,trends,status,params,description,flags,posts,headers) VALUES (110009,50009,50022,0,4,'API LLD rule 4','apilldrule4','30s','90d',0,0,'','',1,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,trends,status,params,description,flags,posts,headers) VALUES (110010,50010,null,0,4,'API Template LLD rule','apitemplatelldrule','30s','90d',0,0,'','',1,'','');
INSERT INTO items (itemid,hostid,interfaceid,templateid,type,value_type,name,key_,delay,history,trends,status,params,description,flags,posts,headers) VALUES (110011,50009,50022,110010,0,4,'API Template LLD rule','apitemplatelldrule','30s','90d',0,0,'','',1,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,trends,status,params,description,flags,posts,headers) VALUES (110012,50009,50022,0,4,'API LLD rule get LLD macro paths','apilldrulegetlldmacropaths','30s','90d',0,0,'','',1,'','');
INSERT INTO items (itemid,hostid,interfaceid,type,value_type,name,key_,delay,history,trends,status,params,description,flags,posts,headers) VALUES (110013,50009,50022,0,4,'API LLD rule get preprocessing','apilldrulegetpreprocessing','30s','90d',0,0,'','',1,'','');

-- LLD macro paths
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (991,110006,'{#A}','$.list[:1].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (992,110006,'{#B}','$.list[:2].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (993,110006,'{#C}','$.list[:3].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (994,110006,'{#D}','$.list[:4].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (995,110006,'{#E}','$.list[:5].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (996,110007,'{#A}','$.list[:1].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (997,110007,'{#B}','$.list[:2].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (998,110007,'{#C}','$.list[:3].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (999,110008,'{#A}','$.list[:1].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1000,110008,'{#B}','$.list[:2].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1001,110008,'{#C}','$.list[:3].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1002,110010,'{#A}','$.list[:1].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1003,110010,'{#B}','$.list[:2].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1004,110010,'{#C}','$.list[:3].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1005,110011,'{#A}','$.list[:1].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1006,110011,'{#B}','$.list[:2].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1007,110011,'{#C}','$.list[:3].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1008,110012,'{#A}','$.list[:1].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1009,110012,'{#B}','$.list[:2].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1010,110012,'{#C}','$.list[:3].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1011,110012,'{#D}','$.list[:4].type');
INSERT INTO lld_macro_path (lld_macro_pathid,itemid,lld_macro,path) VALUES (1012,110012,'{#E}','$.list[:5].type');

-- LLD preprocessing
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9900,110006,1,5,'^abc$
123',0,'');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9901,110006,2,5,'^def$
123',1,'');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9902,110006,3,5,'^ghi$
123',2,'xxx');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9903,110006,4,5,'^jkl$
123',3,'error');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9904,110010,1,12,'$.path.to.node1',0,'');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9905,110010,2,12,'$.path.to.node2',1,'');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9906,110010,3,12,'$.path.to.node3',2,'xxx');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9907,110010,4,12,'$.path.to.node4',3,'error');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9908,110011,1,12,'$.path.to.node1',0,'');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9909,110011,2,12,'$.path.to.node2',1,'');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9910,110011,3,12,'$.path.to.node3',2,'xxx');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9911,110011,4,12,'$.path.to.node4',3,'error');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9912,110013,1,5,'^abc$
123',0,'');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9913,110013,2,5,'^def$
123',1,'');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9914,110013,3,5,'^ghi$
123',2,'xxx');
INSERT INTO item_preproc (item_preprocid,itemid,step,type,params,error_handler,error_handler_params) VALUES (9915,110013,4,5,'^jkl$
123',3,'error');

-- testtriggerfilter
insert into hstgrp (groupid,name,internal) values (139000,'triggerstester',0);
insert into hosts (hostid,host,name,status,description) values (130000,'triggerstester','triggerstester',0,'');
insert into hosts (hostid,host,name,status,description) values (131000,'triggerstestertmpl','triggerstestertmpl',3,'');
insert into hosts_groups (hostgroupid, hostid, groupid) values (139100, 130000, 139000);
insert into hosts_groups (hostgroupid, hostid, groupid) values (139200, 131000, 139000);
insert into items (itemid,hostid,type,name,key_,params,description,posts,headers) values (132000,130000,2,'triggerstesteritem','triggerstesteritem','','','','');
insert into items (itemid,hostid,type,name,key_,params,description,posts,headers) values (132001,131000,2,'triggerstesteritemtmpl','triggerstesteritemtmpl','','','','');
insert into items (itemid,hostid,type,name,key_,flags,params,description,posts,headers) values (132002,130000,2,'triggerstesteritemlld','triggerstesteritemlld',1,'','','','');
insert into items (itemid,hostid,type,name,key_,flags,params,description,posts,headers) values (132003,131000,2,'triggerstesteritemlldtmpl','triggerstesteritemlldtmpl',1,'','','','');
insert into items (itemid,hostid,type,name,key_,flags,params,description,posts,headers) values (132004,130000,2,'triggerstesteritemproto[{#T}]','triggerstesteritemproto[{#T}]',2,'','','','');
insert into items (itemid,hostid,type,name,key_,flags,params,description,posts,headers) values (132005,131000,2,'triggerstesteritemprototmpl[{#T}]','triggerstesteritemprototmpl[{#T}]',2,'','','','');
insert into item_discovery (itemdiscoveryid,itemid,parent_itemid,key_) values (138000,132004,132002,'triggerstesteritemproto[{#T}]');
insert into item_discovery (itemdiscoveryid,itemid,parent_itemid,key_) values (138001,132005,132003,'triggerstesteritemprototmpl[{#T}]');

insert into triggers (triggerid,expression,description,priority,comments) values (134000,'{135000}=0','triggerstester_t0',0,'');
insert into functions (functionid,itemid,triggerid,name,parameter) values (135000,132000,134000,'now','0');
insert into triggers (triggerid,expression,description,priority,comments) values (134001,'{135001}=0','triggerstester_t1',1,'');
insert into functions (functionid,itemid,triggerid,name,parameter) values (135001,132000,134001,'now','0');
insert into triggers (triggerid,expression,description,priority,comments) values (134002,'{135002}=0','triggerstester_t2',2,'');
insert into functions (functionid,itemid,triggerid,name,parameter) values (135002,132000,134002,'now','0');
insert into triggers (triggerid,expression,description,priority,comments) values (134003,'{135003}=0','triggerstester_t3',3,'');
insert into functions (functionid,itemid,triggerid,name,parameter) values (135003,132000,134003,'now','0');
insert into triggers (triggerid,expression,description,priority,comments) values (134004,'{135004}=0','triggerstester_t4',4,'');
insert into functions (functionid,itemid,triggerid,name,parameter) values (135004,132000,134004,'now','0');
insert into triggers (triggerid,expression,description,priority,comments) values (134005,'{135005}=0','triggerstester_t5',5,'');
insert into functions (functionid,itemid,triggerid,name,parameter) values (135005,132000,134005,'now','0');

insert into triggers (triggerid,expression,description,priority,flags,comments) values (134106,'{135106}=0','triggerstesterlld_t0',0,2,'');
insert into functions (functionid,itemid,triggerid,name,parameter) values (135106,132004,134106,'now','0');
-- discovered
INSERT INTO items (itemid,hostid,type,name,key_,flags,params,description,posts,headers) VALUES (132006,130000,2,'TriggersTesterItemLLDDiscovered[res1]','TriggersTesterItemLLDDiscovered[res1]',4,'','','','');
INSERT INTO triggers (triggerid,expression,description,priority,flags,comments) VALUES (134118,'{135118}=0','TriggersTesterLLDTmpl_T0[res1]',0,4,'');
INSERT INTO functions (functionid,itemid,triggerid,name,parameter) VALUES (135118,132006,134118,'now','0');
INSERT INTO trigger_discovery (triggerid,parent_triggerid) VALUES (134118,134106);
insert into item_discovery (itemdiscoveryid,itemid,parent_itemid,key_) values (138002,132006,132004,'triggerstesteritemprototmpl[{#T}]');
-- T4 depends on T5 depends on T0 (LLD discovered version)
INSERT INTO trigger_depends (triggerdepid,triggerid_down,triggerid_up) VALUES (138888,134004,134005);
INSERT INTO trigger_depends (triggerdepid,triggerid_down,triggerid_up) VALUES (138889,134005,134118);

-- testDiscoveryRule
INSERT INTO hstgrp (groupid, name, internal) values (1004, 'testDiscoveryRule', 0);

INSERT INTO hosts (hostid, host, name, status, description) VALUES (1017, 'test.discovery.rule.host.1', 'test.discovery.rule.host.1', 0, '');
INSERT INTO interface (interfaceid, hostid, main, type, useip, ip, dns, port) values (1010, 1017, 1, 1, 1, '127.0.0.1', '', '10050');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1017, 1017, 1004);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2600, 1017, 'item.1.1.1.1'                  ,  2, 'item.1.1.1.1'                  , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers, lifetime, flags) VALUES (2601, 1017, 'dependent.lld.1'               , 18, 'dependent.lld.1'               , 4, '90d', 0, 2600, NULL, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2602, 1017, 'item.1'                        ,  2, 'item.1'                        , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, trends, status, master_itemid, templateid, params, description, posts, headers, lifetime, flags) VALUES (2603, 1017, 'dependent.lld.2'               , 18, 'dependent.lld.2'               , 4, '90d', 0, 0, 2602, NULL, '', '', '', '', '30d', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2604, 1017, 'item.2'                        ,  2, 'item.2'                        , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers, lifetime, flags) VALUES (2605, 1017, 'dependent.lld.3'               , 18, 'dependent.lld.3'               , 4, '90d', 0, 2604, NULL, '', '', '', '', '30d', 1);

INSERT INTO hosts (hostid, host, name, status, description) VALUES (1018, 'test.discovery.rule.host.2', 'test.discovery.rule.host.2', 0, '');
INSERT INTO interface (interfaceid, hostid, main, type, useip, ip, dns, port) values (1011, 1018, 1, 1, 1, '127.0.0.1', '', '10050');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1018, 1018, 1004);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2606, 1018, 'item.1'                        ,  2, 'item.1'                        , 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2607, 1018, 'item.1.1'                      , 18, 'item.1.1'                      , 1, '90d', 0, 2606, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2608, 1018, 'item.1.1.1'                    , 18, 'item.1.1.1'                    , 1, '90d', 0, 2607, NULL, '', '', '', '');
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers                 ) VALUES (2609, 1018, 'item.1.1.1.1'                  , 18, 'item.1.1.1.1'                  , 1, '90d', 0, 2608, NULL, '', '', '', '');

-- testHistory
INSERT INTO hstgrp (groupid, name) VALUES (1005, 'history.get/hosts');

INSERT INTO hosts (hostid, host, name, status, description) VALUES (120005, 'history.get.host.1', 'history.get.host.1', 1, '');
INSERT INTO hosts_groups (hostgroupid, hostid, groupid) VALUES (1019, 120005, 1005);
INSERT INTO interface (interfaceid, hostid, type, ip, useip, port, main) VALUES (1012, 120005, 1, '127.0.0.1', 1, '10050', 1);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers) VALUES (133758, 120005, 'item1', 2, 'item1', 3, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO history_uint (itemid, clock, value, ns) VALUES
(133758, 1549350893, 1, 885479055),
(133758, 1549350907, 2, 762947342),
(133758, 1549350908, 3, 727124125),
(133758, 1549350909, 4, 710589839),
(133758, 1549350910, 5, 369715624),
(133758, 1549350910, 5, 738923458),
(133758, 1549350917, 5, 257150200),
(133758, 1549350917, 5, 762668985),
(133758, 1549350918, 5, 394517718),
(133758, 1549350922, 6, 347073267),
(133758, 1549350923, 7, 882834269),
(133758, 1549350926, 8, 410826674),
(133758, 1549350927, 9, 938887279),
(133758, 1549350944, 0, 730916425);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers) VALUES (133759, 120005, 'item2', 2, 'item2', 0, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO history (itemid, clock, value, ns) VALUES
(133759, 1549350947, 0.0000, 441606890),
(133759, 1549350948, 0.0000, 544936503),
(133759, 1549350950, 0.0000, 866715049),
(133759, 1549350953, 1.0000, 154942891),
(133759, 1549350955, 1.0000, 719111385),
(133759, 1549350957, 1.0000, 594538048),
(133759, 1549350958, 1.5000, 594538048),
(133759, 1549350959, 1.0001, 594538048),
(133759, 1549350960, 1.5000, 594538048),
(133759, 1549350961, -1.0000, 594538048),
(133759, 1549350962, -1.5000, 594538048);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers) VALUES (133760, 120005, 'item3', 2, 'item3', 1, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO history_str (itemid, clock, value, ns) VALUES
(133760, 1549350960, '1', 754460948),
(133760, 1549350962, '1', 919404393),
(133760, 1549350965, '1', 512878374);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers) VALUES (133761, 120005, 'item4', 2, 'item4', 2, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO history_log (itemid, clock, timestamp, source, severity, value, logeventid, ns) VALUES
(133761, 1549350969, 0, '', 0, '1', 0, 506909535),
(133761, 1549350973, 0, '', 0, '2', 0, 336068358),
(133761, 1549350976, 0, '', 0, '3', 0, 2798098),
(133761, 1549350987, 0, '', 0, '4', 0, 755363307),
(133761, 1549350992, 0, '', 0, '5', 0, 242736233);
INSERT INTO items (itemid, hostid, name, type, key_, value_type, history, status, master_itemid, templateid, params, description, posts, headers) VALUES (133762, 120005, 'item5', 2, 'item5', 4, '90d', 0, NULL, NULL, '', '', '', '');
INSERT INTO history_text (itemid, clock, value, ns) VALUES
(133762, 1549350998, '1', 450920469),
(133762, 1549350999, '2', 882825407),
(133762, 1549351001, '3', 242835912);
