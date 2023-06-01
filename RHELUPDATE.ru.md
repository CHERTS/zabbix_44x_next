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

Для RHEL/CentOS/OracleLinux 6
~~~~
rpm -Uvh https://repo.programs74.ru/zabbix/4.4/rhel/6/x86_64/dbs-release-4.4-1.el6.noarch.rpm
~~~~

Для RHEL/CentOS/OracleLinux 7
~~~~
rpm -Uvh https://repo.programs74.ru/zabbix/4.4/rhel/7/x86_64/dbs-release-4.4-1.el7.noarch.rpm
~~~~

Для RHEL/CentOS/OracleLinux 8
~~~~
rpm -Uvh https://repo.programs74.ru/zabbix/4.4/rhel/8/x86_64/dbs-release-4.4-1.el8.noarch.rpm
~~~~

### 3. Обновление пакетов zabbix

Для RHEL/CentOS/OracleLinux 6/7 обновим кэш пакетов и посмотрим список установленных пакетов zabbix:
~~~~
yum clean all
yum makecache fast
yum list installed zabbix\*
~~~~

Для RHEL/CentOS/OracleLinux 6/7 обновим нужные пакеты zabbix (у Вас будет свой список пакетов для обновления):
~~~~
yum update zabbix-agent zabbix-sender zabbix-get zabbix-server
~~~~

Для RHEL/CentOS/OracleLinux 8 обновим кэш пакетов и посмотрим список установленных пакетов zabbix:
~~~~
dnf clean all
dnf makecache
dnf list installed zabbix\*
~~~~

Для RHEL/CentOS/OracleLinux 8 обновим нужные пакеты zabbix (у Вас будет свой список пакетов для обновления):
~~~~
dnf update zabbix-agent zabbix-sender zabbix-get zabbix-server
~~~~
