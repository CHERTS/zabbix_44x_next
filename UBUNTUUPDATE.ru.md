# Обновление из пакетов для Ubuntu

[In English / По-английски](UBUNTUUPDATE.md)

## Обновление из пакетов для Ubuntu 16.04/18.04/20.04

### 1. Удаление официального репозитария Zabbix

~~~~
apt-get --purge -y remove zabbix-release
~~~~

### 2. Установка репозитория DBService

Устанавливаем GPG ключ
~~~~
wget -q -O - https://repo.dbservice.tech/dbservice-official-repo.key | apt-key add -
~~~~

Установка репозитария
~~~~
echo "deb [arch=$(dpkg --print-architecture)] https://repo.dbservice.tech/zabbix/4.4/ubuntu $(lsb_release -c -s) main" > /etc/apt/sources.list.d/dbs.list
~~~~

### 3. Обновление пакетов zabbix

Обновите кэш пакетов
~~~~
apt-get update
~~~~

В зависимости от того, какие компоненты Вам нужно обновить выполните:

Для обновления zabbix-agent, zabbix-get и zabbix-sender
~~~~
apt-get install zabbix-agent zabbix-get zabbix-sender
~~~~

Для обновления zabbix-proxy-sqlite3
~~~~
apt-get install zabbix-proxy-sqlite3
~~~~

Для обновления zabbix-proxy-mysql
~~~~
apt-get install zabbix-proxy-mysql
~~~~

Для обновления zabbix-proxy-pgsql
~~~~
apt-get install zabbix-proxy-pgsql
~~~~

Для обновления zabbix-server-mysql
~~~~
apt-get install zabbix-server-mysql
~~~~

Для обновления zabbix-server-pgsql
~~~~
apt-get install zabbix-server-pgsql
~~~~

Для обновления Web-интерфейса
~~~~
apt-get install zabbix-frontend-php
~~~~
