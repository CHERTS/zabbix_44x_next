# New installation from packages for RedHat/CentOS/OracleLinux

[По-русски / In Russian](RHELINSTALL.ru.md)

[Installing from packages for RedHat/CentOS/OracleLinux 7](#redhat-7)

[Installing from packages for RedHat/CentOS/OracleLinux 8](#redhat-8)

# RedHat 7
## Installing from packages for RedHat / CentOS / Oracle Linux 7

### 1. Install DBService repository

If you are using Oracle Linux 7, then enable ol7_optional_latest and ol7_developer_EPEL repository

~~~~
yum-config-manager --enable ol7_optional_latest
yum-config-manager --enable ol7_developer_EPEL
~~~~

If you are using RedHat 7, then enable rhel-7-server-optional-rpms repository

~~~~
yum-config-manager --enable rhel-7-server-optional-rpms
~~~~

Install DBService repository

~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/7/x86_64/dbs-release-4.4-1.el7.noarch.rpm
yum clean all
yum makecache fast
~~~~


### 2. Install Zabbix server (with MySQL support), agent

~~~~
yum install zabbix-server-mysql zabbix-agent
~~~~

### 3. Install web-frontend

with Apache support
~~~~
yum install httpd httpd-tools
yum install zabbix-web-mysql
systemctl enable httpd.service
systemctl start httpd.service
~~~~

with Nginx + PHP-FPM support
~~~~
yum install nginx
yum install zabbix-web-mysql zabbix-nginx-conf php-fpm
systemctl enable nginx.service
systemctl start nginx.service
~~~~

You need to execute these commands, If you have enabled SELinux in "enforcing" mode

~~~~
setsebool -P httpd_can_connect_zabbix on
setsebool -P httpd_can_network_connect_db on
~~~~

Create SELinux rules for Zabbix-server:

~~~~
dnf install policycoreutils-devel

(cat <<-EOF
module zabbixserver 1.0;

require {
        type mysqld_db_t;
        type zabbix_t;
        type zabbix_var_run_t;
        class capability dac_override;
        class sock_file { create write unlink };
	class unix_stream_socket connectto;
}

#============= zabbix_t ==============

#!!!! This avc is allowed in the current policy
allow zabbix_t self:capability dac_override;

#!!!! This avc is allowed in the current policy
allow zabbix_t mysqld_db_t:sock_file write;

#!!!! This avc can be allowed using the boolean 'daemons_enable_cluster_mode'
allow zabbix_t self:unix_stream_socket connectto;

#!!!! This avc is allowed in the current policy
allow zabbix_t zabbix_var_run_t:sock_file create;
allow zabbix_t zabbix_var_run_t:sock_file write;
allow zabbix_t zabbix_var_run_t:sock_file unlink;
EOF
)>/root/zabbixserver.te

semodule -r zabbixserver
rm -f /root/zabbixserver.mod /root/zabbixserver.pp
checkmodule -M -m -o /root/zabbixserver.mod /root/zabbixserver.te
semodule_package -o /root/zabbixserver.pp -m /root/zabbixserver.mod
semodule -i /root/zabbixserver.pp
~~~~

Allow port 80 for Apache/Nginx service and allow port 10050/10051 for Zabbix agent and Zabbix server through out the system firewall

~~~~
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --permanent --add-port=443/tcp
firewall-cmd --permanent --add-port=10050/tcp
firewall-cmd --permanent --add-port=10051/tcp
firewall-cmd --reload
~~~~

### 4. Create and initial Zabbix database

Run the following on your database host:

~~~~
# mysql -uroot -p
password: *******
mysql> create database zabbix character set utf8 collate utf8_bin;
mysql> create user zabbix@localhost identified by 'password';
mysql> grant all privileges on zabbix.* to zabbix@localhost;
mysql> flush privileges;
mysql> quit;
~~~~

On Zabbix server host import initial schema and data.

~~~~
# zcat /usr/share/doc/zabbix-server-mysql*/create.sql.gz | mysql -uzabbix -ppassword zabbix
~~~~

### 5. Configure the database for Zabbix server

Edit file /etc/zabbix/zabbix_server.conf
~~~~
DBPassword=password
~~~~

### 6. Configure PHP for Zabbix frontend

If use Apache web-server:

Edit file /etc/httpd/conf.d/zabbix.conf, uncomment and set the right timezone for you.

~~~~
# php_value date.timezone Europe/Riga
~~~~

Restart Apache web-server

~~~~
systemctl restart httpd
~~~~

If use Nginx + PHP-FPM:

Edit file /etc/php-fpm.d/zabbix.conf, uncomment and set the right timezone for you.

~~~~
; php_value[date.timezone] = Europe/Riga
~~~~

Start php-fpm processes and make it start at system boot.

~~~~
systemctl enable php-fpm
systemctl start php-fpm
~~~~

Edit Nginx config file /etc/nginx/conf.d/zabbix.conf and change listen and server_name settings

~~~~
listen          80;
server_name     <enter your domain>;
~~~~

Restart Nginx web-server

~~~~
nginx -t && nginx -s reload
~~~~

### 7. Start Zabbix server and agent processes

Start Zabbix server and agent processes and make it start at system boot.

~~~~
systemctl enable zabbix-server zabbix-agent
systemctl restart zabbix-server zabbix-agent
~~~~

### 8. Configure Zabbix frontend

Connect to your newly installed Zabbix frontend: http://server_ip_or_name/zabbix

Follow steps described in official Zabbix documentation: [Installing frontend](https://www.zabbix.com/documentation/4.4/manual/installation/install#installing_frontend)

# RedHat 8
## Installing from packages for RedHat / CentOS / Oracle Linux 8

### 1. Install DBService repository

~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/8/x86_64/dbs-release-4.4-1.el8.noarch.rpm
dnf clean all
dnf makecache
~~~~

### 2. Install Zabbix server (with MySQL support), agent

~~~~
dnf install zabbix-server-mysql zabbix-agent
~~~~

### 3. Install web-frontend

with Apache support
~~~~
dnf install httpd httpd-tools
dnf install zabbix-web-mysql zabbix-apache-conf
systemctl enable httpd.service
systemctl start httpd.service
sed -i 's/listen.owner = nginx/listen.owner = apache/g' /etc/php-fpm.d/zabbix.conf
~~~~

with Nginx support
~~~~
dnf install epel-release
dnf install nginx
dnf install zabbix-web-mysql zabbix-nginx-conf
systemctl enable nginx.service
systemctl start nginx.service
~~~~

You need to execute these commands, If you have enabled SELinux in "enforcing" mode

~~~~
setsebool -P httpd_can_connect_zabbix on
setsebool -P httpd_can_network_connect_db on
~~~~

Create SELinux rules for Zabbix-server:

~~~~
dnf install policycoreutils-devel

(cat <<-EOF
module zabbixserver 1.0;

require {
        type mysqld_db_t;
        type zabbix_t;
        type zabbix_var_run_t;
        class capability dac_override;
        class sock_file { create write unlink };
	class unix_stream_socket connectto;
}

#============= zabbix_t ==============

#!!!! This avc is allowed in the current policy
allow zabbix_t self:capability dac_override;

#!!!! This avc is allowed in the current policy
allow zabbix_t mysqld_db_t:sock_file write;

#!!!! This avc can be allowed using the boolean 'daemons_enable_cluster_mode'
allow zabbix_t self:unix_stream_socket connectto;

#!!!! This avc is allowed in the current policy
allow zabbix_t zabbix_var_run_t:sock_file create;
allow zabbix_t zabbix_var_run_t:sock_file write;
allow zabbix_t zabbix_var_run_t:sock_file unlink;
EOF
)>/root/zabbixserver.te

semodule -r zabbixserver
rm -f /root/zabbixserver.mod /root/zabbixserver.pp
checkmodule -M -m -o /root/zabbixserver.mod /root/zabbixserver.te
semodule_package -o /root/zabbixserver.pp -m /root/zabbixserver.mod
semodule -i /root/zabbixserver.pp
~~~~

Allow port 80 for Apache/Nginx service and allow port 10050/10051 for Zabbix agent and Zabbix server through out the system firewall

~~~~
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --permanent --add-port=443/tcp
firewall-cmd --permanent --add-port=10050/tcp
firewall-cmd --permanent --add-port=10051/tcp
firewall-cmd --reload
~~~~

### 4. Create and initial Zabbix database

Run the following on your database host:

~~~~
# mysql -uroot -p
password: *******
mysql> create database zabbix character set utf8 collate utf8_bin;
mysql> create user zabbix@localhost identified by 'password';
mysql> grant all privileges on zabbix.* to zabbix@localhost;
mysql> flush privileges;
mysql> quit;
~~~~

On Zabbix server host import initial schema and data.

~~~~
# zcat /usr/share/doc/zabbix-server-mysql*/create.sql.gz | mysql -uzabbix -ppassword zabbix
~~~~

### 5. Configure the database for Zabbix server

Edit file /etc/zabbix/zabbix_server.conf
~~~~
DBPassword=password
~~~~

### 6. Configure PHP for Zabbix frontend

Edit file /etc/php-fpm.d/zabbix.conf, uncomment and set the right timezone for you.

~~~~
; php_value[date.timezone] = Europe/Riga
~~~~

Start php-fpm processes and make it start at system boot.

~~~~
systemctl enable php-fpm
systemctl start php-fpm
~~~~

### 7. Start Zabbix server and agent processes

Start Zabbix server and agent processes and make it start at system boot.

~~~~
# systemctl enable zabbix-server zabbix-agent
# systemctl restart zabbix-server zabbix-agent
~~~~

### 8. Configure Zabbix frontend

Connect to your newly installed Zabbix frontend: http://server_ip_or_name/zabbix

Follow steps described in official Zabbix documentation: [Installing frontend](https://www.zabbix.com/documentation/4.4/manual/installation/install#installing_frontend)
