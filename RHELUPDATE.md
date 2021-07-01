# Upgrading from packages for RedHat/CentOS/OracleLinux

[По-русски / In Russian](RHELUPDATE.ru.md)

## Upgrading from packages for RedHat / CentOS / Oracle Linux 6/7/8

### 1. Disable Zabbix repository
~~~~
yum-config-manager --disable zabbix
yum-config-manager --disable zabbix-frontend
yum-config-manager --disable zabbix-debuginfo
yum-config-manager --disable zabbix-non-supported
~~~~

### 2. Install DBService repository

From RHEL/CentOS/OracleLinux 6
~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/6/x86_64/dbs-release-4.4-1.el6.noarch.rpm
~~~~

From RHEL/CentOS/OracleLinux 7
~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/7/x86_64/dbs-release-4.4-1.el7.noarch.rpm
~~~~

From RHEL/CentOS/OracleLinux 8
~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/8/x86_64/dbs-release-4.4-1.el8.noarch.rpm
~~~~

### 3. Upgrade zabbix packages

From RHEL/CentOS/OracleLinux 6/7
~~~~
yum clean all
yum makecache fast
yum update zabbix\*
~~~~

From RHEL/CentOS/OracleLinux 8
~~~~
dnf clean all
dnf makecache
dnf update zabbix\*
~~~~
