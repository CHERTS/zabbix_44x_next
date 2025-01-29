# Как собрать Zabbix из данного репозитария

[In English / По-английски](BUILD.md)

[Сборка на Oracle Linux 7 с поддержкой MySQL (MariaDB)](#oracle-linux-7)

[Сборка на Oracle Linux 7 с поддержкой Oracle (RDBMS)](#oracle-linux-7-oracle-rdbms)

[Сборка на Red Hat Enterprise Linux 8 с поддержкой MySQL (MariaDB)](#red-hat-enterprise-linux-8)

[Сборка на Ubuntu 18.04 LTS (Bionic Beaver) с поддержкой MySQL (MariaDB)](#ubuntu)

[Сборка на Debian 10 (Buster) с поддержкой (MariaDB)](#debian)


# Oracle Linux 7
## Сборка на Oracle Linux 7 с поддержкой MySQL (MariaDB)

### 1. Для подготовки к сборки на Oracle Linux 7 нужно установить дополнительные пакеты:

~~~~
yum group install "Development Tools"
yum-config-manager --enable ol7_optional_latest
yum-config-manager --enable ol7_developer
yum install -y wget unzip gettext java-1.8.0-openjdk libxml2-devel openssl-devel libcurl-devel net-snmp-devel libevent-devel sqlite-devel pcre-devel libssh2-devel OpenIPMI-devel unixODBC-devel openldap-devel
yum install -y MariaDB-client MariaDB-devel MariaDB-shared
~~~~

### 2. Скачать и распаковать свежую версию исходного кода:

~~~~
wget https://github.com/CHERTS/zabbix_44x_next/releases/download/v4.4.27/zabbix-4.4.27.tar.gz
tar -zxf zabbix-4.4.27.tar.gz
cd zabbix-4.4.27
~~~~

### 3. Сборка всех компонентов Zabbix с поддержкой (MariaDB) MySQL:

~~~~
./configure --with-libpthread --with-libpcre --with-libcurl --with-libxml2 --with-net-snmp --with-openssl \
--enable-ipv6 --with-ssh2 --with-openipmi --with-unixodbc --with-ldap --enable-server \
--enable-proxy --enable-agent --enable-java --sysconfdir=/etc/zabbix --with-mysql
make
make gettext
~~~~

### 4. После успешной сборки на шаге 3 можно использовать бинарные файлы zabbix, скопируем их в текущий каталог:

~~~~
cp src/zabbix_server/zabbix_server zabbix_server_mysql_v4.4.27
cp src/zabbix_proxy/zabbix_proxy zabbix_proxy_mysql_v4.4.27
cp src/zabbix_agent/zabbix_agentd zabbix_agentd_v4.4.27
cp src/zabbix_sender/zabbix_sender zabbix_sender_v4.4.27
cp src/zabbix_get/zabbix_get zabbix_get_v4.4.27
~~~~

Проверим наличие бинарных файлов:

~~~~
# ls -l | grep 'zabbix_'
-rwxr-xr-x  1 root root  2268672 Aug  7 10:42 zabbix_agentd_v4.4.27
-rwxr-xr-x  1 root root   730264 Aug  7 10:42 zabbix_get_v4.4.27
-rwxr-xr-x  1 root root 10445416 Aug  7 10:42 zabbix_proxy_mysql_v4.4.27
-rwxr-xr-x  1 root root  1149216 Aug  7 10:42 zabbix_sender_v4.4.27
-rwxr-xr-x  1 root root 12096144 Aug  7 10:42 zabbix_server_mysql_v4.4.27
~~~~

Теперь Вы можете остановить свои компоненты zabbix версии 4.4.x и заменить их данной сборкой.

# Oracle Linux 7 Oracle RDBMS
## Сборка на Oracle Linux 7 с поддержкой Oracle 19c RDBMS

### 1. Для подготовки к сборки на Oracle Linux 7 нужно установить дополнительные пакеты:

~~~~
yum group install "Development Tools"
yum-config-manager --enable ol7_optional_latest
yum-config-manager --enable ol7_developer
yum install -y wget unzip gettext java-1.8.0-openjdk libxml2-devel openssl-devel libcurl-devel net-snmp-devel libevent-devel sqlite-devel pcre-devel libssh2-devel OpenIPMI-devel unixODBC-devel openldap-devel
~~~~

Установите нужную версию oracle-instantclient

По командам ниже будет установлена последняя актуальная версия, она может меняться от года к году.

В 2025 году будет установлена 21 версия oracle-instantclient.

Поэтому [посмотрите на этой странице](https://www.oracle.com/database/technologies/instant-client/linux-x86-64-downloads.html) нужную Вам версию и скорректируйте URL адреса.

~~~~
yum localinstall -y https://download.oracle.com/otn_software/linux/instantclient/oracle-instantclient-basic-linuxx64.rpm
yum localinstall -y https://download.oracle.com/otn_software/linux/instantclient/oracle-instantclient-devel-linuxx64.rpm
~~~~

### 2. Скачать и распаковать свежую версию исходного кода:

~~~~
wget https://github.com/CHERTS/zabbix_44x_next/releases/download/v4.4.27/zabbix-4.4.27.tar.gz
tar -zxf zabbix-4.4.27.tar.gz
cd zabbix-4.4.27
~~~~

### 3. Сборка всех компонентов Zabbix с поддержкой Oracle 19c:

По результатам шага 1 проверьте какая версия oracle-instantclient установилась в каталог /usr/lib/oracle и исправьте в команде ниже версию 19.8 на ту, что установилась у Вас.

~~~~
./configure --with-libpthread --with-libpcre --with-libcurl --with-libxml2 --with-net-snmp \
--with-openssl --enable-ipv6 --with-ssh2 --with-openipmi --with-unixodbc --with-ldap \
--enable-server --enable-proxy --enable-agent --enable-java --sysconfdir=/etc/zabbix \
--with-oracle --with-oracle-lib=/usr/lib/oracle/21/client64/lib --with-oracle-include=/usr/include/oracle/21/client64
make
make gettext
~~~~

### 4. После успешной сборки на шаге 3 можно использовать бинарные файлы zabbix, скопируем их в текущий каталог:

~~~~
cp src/zabbix_server/zabbix_server zabbix_server_oracle_v4.4.27
cp src/zabbix_proxy/zabbix_proxy zabbix_proxy_oracle_v4.4.27
cp src/zabbix_agent/zabbix_agentd zabbix_agentd_v4.4.27
cp src/zabbix_sender/zabbix_sender zabbix_sender_v4.4.27
cp src/zabbix_get/zabbix_get zabbix_get_v4.4.27
~~~~

Проверим наличие бинарных файлов:

~~~~
# ls -l | grep 'zabbix_'
-rwxr-xr-x  1 root root 1894312 Aug 10 16:02 zabbix_agentd_v4.4.27
-rwxr-xr-x  1 root root  629984 Aug 10 16:02 zabbix_get_v4.4.27
-rwxr-xr-x  1 root root 8351184 Aug 10 16:02 zabbix_proxy_oracle_v4.4.27
-rwxr-xr-x  1 root root  963680 Aug 10 16:02 zabbix_sender_v4.4.27
-rwxr-xr-x  1 root root 9835920 Aug 10 16:02 zabbix_server_oracle_v4.4.27
~~~~

Теперь Вы можете остановить свои компоненты zabbix версии 4.4.x и заменить их данной сборкой.

# Red Hat Enterprise Linux 8
## Сборка на Red Hat Enterprise Linux 8 с поддержкой MySQL (MariaDB)

### 1. Для подготовки к сборки на Red Hat Enterprise Linux 8 нужно установить дополнительные пакеты:

~~~~
dnf group install "Development Tools"
dnf install -y wget unzip gettext java-1.8.0-openjdk java-1.8.0-openjdk-devel libxml2-devel openssl-devel libcurl-devel net-snmp-devel libevent-devel sqlite-devel pcre-devel unixODBC-devel openldap-devel
dnf install -y mariadb-devel
subscription-manager repos --enable=codeready-builder-for-rhel-8-x86_64-rpms 
yum module enable -y virt-devel 
dnf install -y libssh2-devel OpenIPMI-devel
~~~~

### 2. Скачать и распаковать свежую версию исходного кода:

~~~~
wget https://github.com/CHERTS/zabbix_44x_next/releases/download/v4.4.27/zabbix-4.4.27.tar.gz
tar -zxf zabbix-4.4.27.tar.gz
cd zabbix-4.4.27
~~~~

### 3. Сборка всех компонентов Zabbix с поддержкой (MariaDB) MySQL:

~~~~
./configure --with-libpthread --with-libpcre --with-libcurl --with-libxml2 --with-net-snmp --with-openssl \
--enable-ipv6 --with-ssh2 --with-openipmi --with-unixodbc --with-ldap --enable-server \
--enable-proxy --enable-agent --enable-java --sysconfdir=/etc/zabbix --with-mysql
make
make gettext
~~~~

### 4. После успешной сборки на шаге 3 можно использовать бинарные файлы zabbix, скопируем их в текущий каталог:

~~~~
cp src/zabbix_server/zabbix_server zabbix_server_mysql_v4.4.27
cp src/zabbix_proxy/zabbix_proxy zabbix_proxy_mysql_v4.4.27
cp src/zabbix_agent/zabbix_agentd zabbix_agentd_v4.4.27
cp src/zabbix_sender/zabbix_sender zabbix_sender_v4.4.27
cp src/zabbix_get/zabbix_get zabbix_get_v4.4.27
~~~~

Проверим наличие бинарных файлов:

~~~~
# ls -l | grep 'zabbix_'
-rwxr-xr-x  1 root root  2268672 Aug  7 10:42 zabbix_agentd_v4.4.27
-rwxr-xr-x  1 root root   730264 Aug  7 10:42 zabbix_get_v4.4.27
-rwxr-xr-x  1 root root 10445416 Aug  7 10:42 zabbix_proxy_mysql_v4.4.27
-rwxr-xr-x  1 root root  1149216 Aug  7 10:42 zabbix_sender_v4.4.27
-rwxr-xr-x  1 root root 12096144 Aug  7 10:42 zabbix_server_mysql_v4.4.27
~~~~

Теперь Вы можете остановить свои компоненты zabbix версии 4.4.x и заменить их данной сборкой.

# Ubuntu
## Сборка на Ubuntu 18.04 LTS (Bionic Beaver) с поддержкой MariaDB

### 1. Для подготовки к сборки на Ubuntu 18.04 нужно установить дополнительные пакеты:

~~~~
sudo apt-get update
sudo apt-get install -y autoconf automake gcc make wget unzip gettext default-jdk libxml2-dev libssl-dev libcurl4-openssl-dev libsnmp-dev libevent-dev libsqlite3-dev libpcre2-dev libssh2-1-dev libiksemel-dev libmariadbclient-dev-compat libopenipmi-dev unixodbc-dev libldap2-dev
~~~~

### 2. Скачать и распаковать свежую версию исходного кода:

~~~~
wget https://github.com/CHERTS/zabbix_44x_next/releases/download/v4.4.27/zabbix-4.4.27.tar.gz
tar -zxf zabbix-4.4.27.tar.gz
cd zabbix-4.4.27
~~~~

### 3. Сборка всех компонентов Zabbix с поддержкой (MariaDB) MySQL:

~~~~
./configure --with-libpthread --with-libpcre --with-libcurl --with-libxml2 --with-net-snmp --with-openssl \
--enable-ipv6 --with-ssh2 --with-openipmi --with-unixodbc --with-ldap --enable-server \
--enable-proxy --enable-agent --enable-java --sysconfdir=/etc/zabbix --with-mysql
make
make gettext
~~~~

### 4. После успешной сборки на шаге 3 можно использовать бинарные файлы zabbix, скопируем их в текущий каталог:

~~~~
cp src/zabbix_server/zabbix_server zabbix_server_mysql_v4.4.27
cp src/zabbix_proxy/zabbix_proxy zabbix_proxy_mysql_v4.4.27
cp src/zabbix_agent/zabbix_agentd zabbix_agentd_v4.4.27
cp src/zabbix_sender/zabbix_sender zabbix_sender_v4.4.27
cp src/zabbix_get/zabbix_get zabbix_get_v4.4.27
~~~~

Проверим наличие бинарных файлов:

~~~~
# ls -l | grep 'zabbix_'
-rwxr-xr-x  1 root root  2268672 Aug  7 10:42 zabbix_agentd_v4.4.27
-rwxr-xr-x  1 root root   730264 Aug  7 10:42 zabbix_get_v4.4.27
-rwxr-xr-x  1 root root 10445416 Aug  7 10:42 zabbix_proxy_mysql_v4.4.27
-rwxr-xr-x  1 root root  1149216 Aug  7 10:42 zabbix_sender_v4.4.27
-rwxr-xr-x  1 root root 12096144 Aug  7 10:42 zabbix_server_mysql_v4.4.27
~~~~

Теперь Вы можете остановить свои компоненты zabbix версии 4.4.x и заменить их данной сборкой.


# Debian
## Сборка на Debian 10 (Buster) с поддержкой (MariaDB)

### 1. Для подготовки к сборки на Debian 10 нужно установить дополнительные пакеты:

~~~~
sudo apt-get update
sudo apt-get install -y autoconf automake gcc make wget unzip gettext default-jdk libxml2-dev libssl-dev libcurl4-openssl-dev libsnmp-dev libevent-dev libsqlite3-dev libpcre3-dev libssh2-1-dev libopenipmi-dev unixodbc-dev libldap2-dev libmariadbclient-dev-compat
~~~~

### 2. Скачать и распаковать свежую версию исходного кода:

~~~~
wget https://github.com/CHERTS/zabbix_44x_next/releases/download/v4.4.27/zabbix-4.4.27.tar.gz
tar -zxf zabbix-4.4.27.tar.gz
cd zabbix-4.4.27
~~~~

### 3. Сборка всех компонентов Zabbix с поддержкой (MariaDB) MySQL:

~~~~
./configure --with-libpthread --with-libpcre --with-libcurl --with-libxml2 --with-net-snmp --with-openssl \
--enable-ipv6 --with-ssh2 --with-openipmi --with-unixodbc --with-ldap --enable-server \
--enable-proxy --enable-agent --enable-java --sysconfdir=/etc/zabbix --with-mysql
make
make gettext
~~~~

### 4. После успешной сборки на шаге 3 можно использовать бинарные файлы zabbix, скопируем их в текущий каталог:

~~~~
cp src/zabbix_server/zabbix_server zabbix_server_mysql_v4.4.27
cp src/zabbix_proxy/zabbix_proxy zabbix_proxy_mysql_v4.4.27
cp src/zabbix_agent/zabbix_agentd zabbix_agentd_v4.4.27
cp src/zabbix_sender/zabbix_sender zabbix_sender_v4.4.27
cp src/zabbix_get/zabbix_get zabbix_get_v4.4.27
~~~~

Проверим наличие бинарных файлов:

~~~~
# ls -l | grep 'zabbix_'
-rwxr-xr-x  1 root root  2268672 Aug  7 10:42 zabbix_agentd_v4.4.27
-rwxr-xr-x  1 root root   730264 Aug  7 10:42 zabbix_get_v4.4.27
-rwxr-xr-x  1 root root 10445416 Aug  7 10:42 zabbix_proxy_mysql_v4.4.27
-rwxr-xr-x  1 root root  1149216 Aug  7 10:42 zabbix_sender_v4.4.27
-rwxr-xr-x  1 root root 12096144 Aug  7 10:42 zabbix_server_mysql_v4.4.27
~~~~

Теперь Вы можете остановить свои компоненты zabbix версии 4.4.x и заменить их данной сборкой.

