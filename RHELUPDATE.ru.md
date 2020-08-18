# Обновление из пакетов для RedHat/CentOS/OracleLinux

[In English / По-английски](RHELUPDATE.md)

## Обновление из пакетов для RedHat / CentOS / Oracle Linux 6/7/8

### 1. Отключение официального репозитария Zabbix
~~~~
yum-config-manager --disable zabbix
yum-config-manager --disable zabbix-frontend
yum-config-manager --disable zabbix-debuginfo
yum-config-manager --disable zabbix-non-supported
~~~~

### 2. Установка репозитория DBService

Для RHEL/CentOS/OracleLinux 7
~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/7/x86_64/dbs-release-4.4-1.el7.noarch.rpm
~~~~

Для RHEL/CentOS/OracleLinux 8
~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/8/x86_64/dbs-release-4.4-1.el8.noarch.rpm
~~~~

### 3. Обновление пакетов zabbix
~~~~
yum clean all
yum makecache fast
yum update zabbix*
~~~~
