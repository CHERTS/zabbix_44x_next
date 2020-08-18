# New installation from packages for RedHat/CentOS/OracleLinux

[По-русски / In Russian](RHELINSTALL.ru.md)

[Installing from packages for RedHat/CentOS/OracleLinux 6](#redhat-6)

[Installing from packages for RedHat/CentOS/OracleLinux 7](#redhat-7)

[Installing from packages for RedHat/CentOS/OracleLinux 8](#redhat-8)

# RedHat 6
## Installing from packages for RedHat / CentOS / Oracle Linux 6

### 1. Install DBService repository

~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/6/x86_64/dbs-release-4.4-1.el6.noarch.rpm
yum clean all
~~~~

### 2. Install Zabbix server, frontend, agent

~~~~
yum install zabbix-server-mysql zabbix-web-mysql zabbix-agent
~~~~

### 3. Create and initial Zabbix database

Run the following on your database host:

~~~~
# mysql -uroot -p
password: *******
mysql> create database zabbix character set utf8 collate utf8_bin;
mysql> create user zabbix@localhost identified by 'password';
mysql> grant all privileges on zabbix.* to zabbix@localhost;
mysql> quit;
~~~~

On Zabbix server host import initial schema and data. You will be prompted to enter your newly created password.

~~~~
# zcat /usr/share/doc/zabbix-server-mysql*/create.sql.gz | mysql -uzabbix -p zabbix
~~~~

### 4. Configure the database for Zabbix server

Edit file /etc/zabbix/zabbix_server.conf
~~~~
DBPassword=password
~~~~

### 5. Configure PHP for Zabbix frontend

Edit file /etc/httpd/conf.d/zabbix.conf, uncomment and set the right timezone for you.
~~~~
# php_value date.timezone Europe/Riga
~~~~

### 6. Start Zabbix server and agent processes

Start Zabbix server and agent processes and make it start at system boot.

~~~~
# systemctl enable zabbix-server zabbix-agent httpd
# systemctl restart zabbix-server zabbix-agent httpd
~~~~

### 7. Configure Zabbix frontend

Connect to your newly installed Zabbix frontend: http://server_ip_or_name/zabbix

Follow steps described in official Zabbix documentation: [Installing frontend](https://www.zabbix.com/documentation/4.4/manual/installation/install#installing_frontend)


# RedHat 7
## Installing from packages for RedHat / CentOS / Oracle Linux 7

### 1. Install DBService repository

~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/7/x86_64/dbs-release-4.4-1.el7.noarch.rpm
yum clean all
~~~~

### 2. Install Zabbix server, frontend, agent

~~~~
yum install zabbix-server-mysql zabbix-web-mysql zabbix-agent
~~~~

### 3. Create and initial Zabbix database

Run the following on your database host:

~~~~
# mysql -uroot -p
password: *******
mysql> create database zabbix character set utf8 collate utf8_bin;
mysql> create user zabbix@localhost identified by 'password';
mysql> grant all privileges on zabbix.* to zabbix@localhost;
mysql> quit;
~~~~

On Zabbix server host import initial schema and data. You will be prompted to enter your newly created password.

~~~~
# zcat /usr/share/doc/zabbix-server-mysql*/create.sql.gz | mysql -uzabbix -p zabbix
~~~~

### 4. Configure the database for Zabbix server

Edit file /etc/zabbix/zabbix_server.conf
~~~~
DBPassword=password
~~~~

### 5. Configure PHP for Zabbix frontend

Edit file /etc/httpd/conf.d/zabbix.conf, uncomment and set the right timezone for you.
~~~~
# php_value date.timezone Europe/Riga
~~~~

### 6. Start Zabbix server and agent processes

Start Zabbix server and agent processes and make it start at system boot.

~~~~
# systemctl enable zabbix-server zabbix-agent httpd
# systemctl restart zabbix-server zabbix-agent httpd
~~~~

### 7. Configure Zabbix frontend

Connect to your newly installed Zabbix frontend: http://server_ip_or_name/zabbix

Follow steps described in official Zabbix documentation: [Installing frontend](https://www.zabbix.com/documentation/4.4/manual/installation/install#installing_frontend)

# RedHat 8
## Installing from packages for RedHat / CentOS / Oracle Linux 8

### 1. Install DBService repository

~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/8/x86_64/dbs-release-4.4-1.el8.noarch.rpm
dnf clean all
~~~~

### 2. Install Zabbix server, frontend, agent

~~~~
yum install zabbix-server-mysql zabbix-web-mysql zabbix-agent
~~~~

### 3. Create and initial Zabbix database

Run the following on your database host:

~~~~
# mysql -uroot -p
password: *******
mysql> create database zabbix character set utf8 collate utf8_bin;
mysql> create user zabbix@localhost identified by 'password';
mysql> grant all privileges on zabbix.* to zabbix@localhost;
mysql> quit;
~~~~

On Zabbix server host import initial schema and data. You will be prompted to enter your newly created password.

~~~~
# zcat /usr/share/doc/zabbix-server-mysql*/create.sql.gz | mysql -uzabbix -p zabbix
~~~~

### 4. Configure the database for Zabbix server

Edit file /etc/zabbix/zabbix_server.conf
~~~~
DBPassword=password
~~~~

### 5. Configure PHP for Zabbix frontend

Edit file /etc/php-fpm.d/zabbix.conf, uncomment and set the right timezone for you.

~~~~
; php_value[date.timezone] = Europe/Riga
~~~~

### 6. Start Zabbix server and agent processes

Start Zabbix server and agent processes and make it start at system boot.

~~~~
# systemctl enable zabbix-server zabbix-agent httpd php-fpm
# systemctl restart zabbix-server zabbix-agent httpd php-fpm
~~~~

### 7. Configure Zabbix frontend

Connect to your newly installed Zabbix frontend: http://server_ip_or_name/zabbix

Follow steps described in official Zabbix documentation: [Installing frontend](https://www.zabbix.com/documentation/4.4/manual/installation/install#installing_frontend)
