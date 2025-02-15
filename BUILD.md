# How to build Zabbix from this repository

[По-русски / In Russian](BUILD.ru.md)

[Build on Oracle Linux 7 with MySQL (MariaDB) support](#oracle-linux-7)

[Build on Oracle Linux 7 with Oracle (RDBMS)](#oracle-linux-7-oracle-rdbms)

[Build on Red Hat Enterprise Linux 8 with MySQL (MariaDB) support](#red-hat-enterprise-linux-8)

[Build on Ubuntu 18.04 LTS (Bionic Beaver) with MySQL (MariaDB) support](#ubuntu)

[Build on Debian 10 (Buster) with MySQL (MariaDB) support](#debian)

# Oracle Linux 7
## Build on Oracle Linux 7 with MySQL (MariaDB) support

### 1. To prepare for build on Oracle Linux 7, you need to install additional packages:

~~~~
yum group install "Development Tools"
yum-config-manager --enable ol7_optional_latest
yum-config-manager --enable ol7_developer
yum install -y wget unzip gettext java-1.8.0-openjdk libxml2-devel openssl-devel libcurl-devel net-snmp-devel libevent-devel sqlite-devel pcre-devel libssh2-devel OpenIPMI-devel unixODBC-devel openldap-devel
yum install -y MariaDB-client MariaDB-devel MariaDB-shared
~~~~

### 2. Download and unzip the latest version of the source code:

~~~~
wget https://github.com/CHERTS/zabbix_44x_next/releases/download/v4.4.27/zabbix-4.4.27.tar.gz
tar -zxf zabbix-4.4.27.tar.gz
cd zabbix-4.4.27
~~~~

### 3. Build all Zabbix components with MySQL (MariaDB) support:

~~~~
./configure --with-libpthread --with-libpcre --with-libcurl --with-libxml2 --with-net-snmp --with-openssl \
--enable-ipv6 --with-ssh2 --with-openipmi --with-unixodbc --with-ldap --enable-server \
--enable-proxy --enable-agent --enable-java --sysconfdir=/etc/zabbix --with-mysql
make
make gettext
~~~~

### 4. After successful build, in step 3 you can use zabbix binaries, copy them to the current directory:

~~~~
cp src/zabbix_server/zabbix_server zabbix_server_mysql_v4.4.27
cp src/zabbix_proxy/zabbix_proxy zabbix_proxy_mysql_v4.4.27
cp src/zabbix_agent/zabbix_agentd zabbix_agentd_v4.4.27
cp src/zabbix_sender/zabbix_sender zabbix_sender_v4.4.27
cp src/zabbix_get/zabbix_get zabbix_get_v4.4.27
~~~~

Check for the presence of binary files:

~~~~
# ls -l | grep 'zabbix_'
-rwxr-xr-x  1 root root  2268672 Aug  7 10:42 zabbix_agentd_v4.4.27
-rwxr-xr-x  1 root root   730264 Aug  7 10:42 zabbix_get_v4.4.27
-rwxr-xr-x  1 root root 10445416 Aug  7 10:42 zabbix_proxy_mysql_v4.4.27
-rwxr-xr-x  1 root root  1149216 Aug  7 10:42 zabbix_sender_v4.4.27
-rwxr-xr-x  1 root root 12096144 Aug  7 10:42 zabbix_server_mysql_v4.4.27
~~~~

Congratulations! Now you can stop your zabbix components version 4.4.x and replace them with this build.



# Oracle Linux 7 Oracle RDBMS
## Build on Oracle Linux 7 with Oracle 19c RDBMS support

### 1. To prepare for build on Oracle Linux 7, you need to install additional packages:

~~~~
yum group install "Development Tools"
yum-config-manager --enable ol7_optional_latest
yum-config-manager --enable ol7_developer
yum install -y wget unzip gettext java-1.8.0-openjdk libxml2-devel openssl-devel libcurl-devel net-snmp-devel \
libevent-devel sqlite-devel pcre-devel libssh2-devel OpenIPMI-devel unixODBC-devel openldap-devel
~~~~

Install the required version of oracle-instantclient

The commands below will install the latest current version, it may change from year to year.

In 2025 year, version 21 of oracle-instantclient will be installed.

[Look at this page](https://www.oracle.com/database/technologies/instant-client/linux-x86-64-downloads.html) for the version you need and adjust the URL addresses.

~~~~
yum localinstall -y https://download.oracle.com/otn_software/linux/instantclient/oracle-instantclient-basic-linuxx64.rpm
yum localinstall -y https://download.oracle.com/otn_software/linux/instantclient/oracle-instantclient-devel-linuxx64.rpm
~~~~

### 2. Download and unzip the latest version of the source code:

~~~~
wget https://github.com/CHERTS/zabbix_44x_next/releases/download/v4.4.27/zabbix-4.4.27.tar.gz
tar -zxf zabbix-4.4.27.tar.gz
cd zabbix-4.4.27
~~~~

### 3. Build all Zabbix components with Oracle 19c support:

~~~~
./configure --with-libpthread --with-libpcre --with-libcurl --with-libxml2 --with-net-snmp --with-openssl \
--enable-ipv6 --with-ssh2 --with-openipmi --with-unixodbc --with-ldap --enable-server --enable-proxy \
--enable-agent --enable-java --sysconfdir=/etc/zabbix \
--with-oracle --with-oracle-lib=/usr/lib/oracle/21/client64/lib --with-oracle-include=/usr/include/oracle/21/client64
make
make gettext
~~~~

### 4. After successful build, in step 3 you can use zabbix binaries, copy them to the current directory:

~~~~
cp src/zabbix_server/zabbix_server zabbix_server_oracle_v4.4.27
cp src/zabbix_proxy/zabbix_proxy zabbix_proxy_oracle_v4.4.27
cp src/zabbix_agent/zabbix_agentd zabbix_agentd_v4.4.27
cp src/zabbix_sender/zabbix_sender zabbix_sender_v4.4.27
cp src/zabbix_get/zabbix_get zabbix_get_v4.4.27
~~~~

Check for the presence of binary files:

~~~~
# ls -l | grep 'zabbix_'
-rwxr-xr-x  1 root root 1894312 Aug 10 16:02 zabbix_agentd_v4.4.27
-rwxr-xr-x  1 root root  629984 Aug 10 16:02 zabbix_get_v4.4.27
-rwxr-xr-x  1 root root 8351184 Aug 10 16:02 zabbix_proxy_oracle_v4.4.27
-rwxr-xr-x  1 root root  963680 Aug 10 16:02 zabbix_sender_v4.4.27
-rwxr-xr-x  1 root root 9835920 Aug 10 16:02 zabbix_server_oracle_v4.4.27
~~~~

Congratulations! Now you can stop your zabbix components version 4.4.27 and replace them with this build.

# Red Hat Enterprise Linux 8
## Build on Red Hat Enterprise Linux 8 with MySQL (MariaDB) support

### 1. To prepare for build on Red Hat Enterprise Linux 8, you need to install additional packages:

~~~~
dnf group install "Development Tools"
dnf install -y wget unzip gettext java-1.8.0-openjdk java-1.8.0-openjdk-devel libxml2-devel openssl-devel \
libcurl-devel net-snmp-devel libevent-devel sqlite-devel pcre-devel unixODBC-devel openldap-devel
dnf install -y mariadb-devel
subscription-manager repos --enable=codeready-builder-for-rhel-8-x86_64-rpms 
yum module enable -y virt-devel 
dnf install -y libssh2-devel OpenIPMI-devel
~~~~

### 2. Download and unzip the latest version of the source code:

~~~~
wget https://github.com/CHERTS/zabbix_44x_next/releases/download/v4.4.27/zabbix-4.4.27.tar.gz
tar -zxf zabbix-4.4.27.tar.gz
cd zabbix-4.4.27
~~~~

### 3. Build all Zabbix components with MySQL (MariaDB) support:

~~~~
./configure --with-libpthread --with-libpcre --with-libcurl --with-libxml2 --with-net-snmp --with-openssl \
--enable-ipv6 --with-ssh2 --with-openipmi --with-unixodbc --with-ldap --enable-server \
--enable-proxy --enable-agent --enable-java --sysconfdir=/etc/zabbix --with-mysql
make
make gettext
~~~~

### 4. After successful build, in step 3 you can use zabbix binaries, copy them to the current directory:

~~~~
cp src/zabbix_server/zabbix_server zabbix_server_mysql_v4.4.27
cp src/zabbix_proxy/zabbix_proxy zabbix_proxy_mysql_v4.4.27
cp src/zabbix_agent/zabbix_agentd zabbix_agentd_v4.4.27
cp src/zabbix_sender/zabbix_sender zabbix_sender_v4.4.27
cp src/zabbix_get/zabbix_get zabbix_get_v4.4.27
~~~~

Check for the presence of binary files:

~~~~
# ls -l | grep 'zabbix_'
-rwxr-xr-x  1 root root  2268672 Aug  7 10:42 zabbix_agentd_v4.4.27
-rwxr-xr-x  1 root root   730264 Aug  7 10:42 zabbix_get_v4.4.27
-rwxr-xr-x  1 root root 10445416 Aug  7 10:42 zabbix_proxy_mysql_v4.4.27
-rwxr-xr-x  1 root root  1149216 Aug  7 10:42 zabbix_sender_v4.4.27
-rwxr-xr-x  1 root root 12096144 Aug  7 10:42 zabbix_server_mysql_v4.4.27
~~~~

Congratulations! Now you can stop your zabbix components version 4.4.x and replace them with this build.

# Ubuntu
## Build on Ubuntu 18.04 LTS (Bionic Beaver) with MySQL (MariaDB) support

### 1. To prepare for build on Ubuntu 18.04, you need to install additional packages:

~~~~
sudo apt-get update
sudo apt-get install -y autoconf automake gcc make wget unzip gettext default-jdk libxml2-dev libssl-dev \
libcurl4-openssl-dev libsnmp-dev libevent-dev libsqlite3-dev libpcre2-dev libssh2-1-dev libopenipmi-dev \
unixodbc-dev libldap2-dev libmariadbclient-dev-compat
~~~~

### 2. Download and unzip the latest version of the source code:

~~~~
wget https://github.com/CHERTS/zabbix_44x_next/releases/download/v4.4.27/zabbix-4.4.27.tar.gz
tar -zxf zabbix-4.4.27.tar.gz
cd zabbix-4.4.27
~~~~

### 3. Build all Zabbix components with MySQL (MariaDB) support:

~~~~
./configure --with-libpthread --with-libpcre --with-libcurl --with-libxml2 --with-net-snmp --with-openssl \
--enable-ipv6 --with-ssh2 --with-openipmi --with-unixodbc --with-ldap --enable-server \
--enable-proxy --enable-agent --enable-java --sysconfdir=/etc/zabbix --with-mysql
make
make gettext
~~~~

### 4. After successful build, in step 3 you can use zabbix binaries, copy them to the current directory:

~~~~
cp src/zabbix_server/zabbix_server zabbix_server_mysql_v4.4.27
cp src/zabbix_proxy/zabbix_proxy zabbix_proxy_mysql_v4.4.27
cp src/zabbix_agent/zabbix_agentd zabbix_agentd_v4.4.27
cp src/zabbix_sender/zabbix_sender zabbix_sender_v4.4.27
cp src/zabbix_get/zabbix_get zabbix_get_v4.4.27
~~~~

Check for the presence of binary files:

~~~~
# ls -l | grep 'zabbix_'
-rwxr-xr-x  1 root root  2268672 Aug  7 10:42 zabbix_agentd_v4.4.27
-rwxr-xr-x  1 root root   730264 Aug  7 10:42 zabbix_get_v4.4.27
-rwxr-xr-x  1 root root 10445416 Aug  7 10:42 zabbix_proxy_mysql_v4.4.27
-rwxr-xr-x  1 root root  1149216 Aug  7 10:42 zabbix_sender_v4.4.27
-rwxr-xr-x  1 root root 12096144 Aug  7 10:42 zabbix_server_mysql_v4.4.27
~~~~

Congratulations! Now you can stop your zabbix components version 4.4.x and replace them with this build.

# Debian
## Build on Debian 10 (Buster) with MySQL (MariaDB) support

### 1. To prepare for build on Debian 10, you need to install additional packages:

~~~~
sudo apt-get update
sudo apt-get install -y autoconf automake gcc make wget unzip gettext default-jdk libxml2-dev libssl-dev \
libcurl4-openssl-dev libsnmp-dev libevent-dev libsqlite3-dev libpcre3-dev libssh2-1-dev libopenipmi-dev \
unixodbc-dev libldap2-dev libmariadbclient-dev-compat
~~~~

### 2. Download and unzip the latest version of the source code:

~~~~
wget https://github.com/CHERTS/zabbix_44x_next/releases/download/v4.4.27/zabbix-4.4.27.tar.gz
tar -zxf zabbix-4.4.27.tar.gz
cd zabbix-4.4.27
~~~~

### 3. Build all Zabbix components with MySQL (MariaDB) support:

~~~~
./configure --with-libpthread --with-libpcre --with-libcurl --with-libxml2 --with-net-snmp --with-openssl \
--enable-ipv6 --with-ssh2 --with-openipmi --with-unixodbc --with-ldap --enable-server \
--enable-proxy --enable-agent --enable-java --sysconfdir=/etc/zabbix --with-mysql
make
make gettext
~~~~

### 4. After successful build, in step 3 you can use zabbix binaries, copy them to the current directory:

~~~~
cp src/zabbix_server/zabbix_server zabbix_server_mysql_v4.4.27
cp src/zabbix_proxy/zabbix_proxy zabbix_proxy_mysql_v4.4.27
cp src/zabbix_agent/zabbix_agentd zabbix_agentd_v4.4.27
cp src/zabbix_sender/zabbix_sender zabbix_sender_v4.4.27
cp src/zabbix_get/zabbix_get zabbix_get_v4.4.27
~~~~

Check for the presence of binary files:

~~~~
# ls -l | grep 'zabbix_'
-rwxr-xr-x  1 root root  2268672 Aug  7 10:42 zabbix_agentd_v4.4.27
-rwxr-xr-x  1 root root   730264 Aug  7 10:42 zabbix_get_v4.4.27
-rwxr-xr-x  1 root root 10445416 Aug  7 10:42 zabbix_proxy_mysql_v4.4.27
-rwxr-xr-x  1 root root  1149216 Aug  7 10:42 zabbix_sender_v4.4.27
-rwxr-xr-x  1 root root 12096144 Aug  7 10:42 zabbix_server_mysql_v4.4.27
~~~~

Congratulations! Now you can stop your zabbix components version 4.4.x and replace them with this build.
