# Upgrading from packages for Ubuntu

[По-русски / In Russian](UBUNTUUPDATE.ru.md)

## Upgrading from packages for Ubuntu 16.04/18.04/20.04

### 1. Remove Zabbix official repository

~~~~
apt-get --purge -y remove zabbix-release
~~~~

### 2. Install DBService repository

Install GPG key
~~~~
wget -q -O - https://repo.dbservice.tech/dbservice-official-repo.key | apt-key add -
~~~~

Install repository
~~~~
echo "deb [arch=$(dpkg --print-architecture)] https://repo.dbservice.tech/zabbix/4.4/ubuntu $(lsb_release -c -s) main" > /etc/apt/sources.list.d/dbs.list
~~~~

### 3. Upgrade zabbix packages

Update apt cache
~~~~
apt-get update
~~~~

Depending on which components you need to update, do:

To update zabbix-agent, zabbix-get and zabbix-sender
~~~~
apt-get install zabbix-agent zabbix-get zabbix-sender
~~~~

To update zabbix-proxy-sqlite3
~~~~
apt-get install zabbix-proxy-sqlite3
~~~~

To update zabbix-proxy-mysql
~~~~
apt-get install zabbix-proxy-mysql
~~~~

To update zabbix-proxy-pgsql
~~~~
apt-get install zabbix-proxy-pgsql
~~~~

To update zabbix-server-mysql
~~~~
apt-get install zabbix-server-mysql
~~~~

To update zabbix-server-pgsql
~~~~
apt-get install zabbix-server-pgsql
~~~~

