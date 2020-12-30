# New installation from packages for Ubuntu

[По-русски / In Russian](UBUNTUINSTALL.ru.md)

## New installation from packages for Ubuntu 20.04

### 1. Install DBService repository

Installing GPG key
~~~~
wget -q -O - https://repo.dbservice.tech/dbservice-official-repo.key | apt-key add -
~~~~

Installing repo
~~~~
echo "deb [arch=$(dpkg --print-architecture)] https://repo.dbservice.tech/zabbix/4.4/ubuntu $(lsb_release -c -s) main" > /etc/apt/sources.list.d/dbs.list
~~~~

### 2. Update apt cache

Update apt cache
~~~~
apt-get update
~~~~

### 3. Install Zabbix server (with MySQL support), agent

~~~~
apt-get install zabbix-server-mysql zabbix-agent
~~~~

### 4. Install web-frontend

with Apache support
~~~~
apt-get install apache2
apt-get install zabbix-apache-conf
systemctl enable apache2
systemctl restart apache2
~~~~

with Nginx + PHP-FPM support
~~~~
apt-get install nginx
apt-get install zabbix-nginx-conf php-fpm
systemctl enable nginx
systemctl restart nginx
~~~~

### 5. Create and initial Zabbix database

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

On Zabbix server host import initial schema and data:
~~~~
zcat /usr/share/doc/zabbix-server-mysql/create.sql.gz | mysql -uzabbix -ppassword zabbix
~~~~

### 6. Configure the database for Zabbix server

Edit file /etc/zabbix/zabbix_server.conf
~~~~
DBPassword=password
~~~~

### 7. Configure PHP for Zabbix frontend

If use Apache web-server:

a) Edit file /etc/apache2/conf-enabled/zabbix.conf, uncomment and set the right timezone for you.
~~~~
php_value date.timezone Europe/Riga
~~~~

b) Restart Apache web-server
~~~~
systemctl restart apache2
~~~~

If use Nginx + PHP-FPM:

c) Edit file /etc/php/7.4/fpm/pool.d/zabbix-php-fpm.conf, uncomment and set the right timezone for you
~~~~
php_value[date.timezone] = Europe/Riga
~~~~

d) Start php-fpm processes and make it start at system boot
~~~~
systemctl enable php7.4-fpm
systemctl restart php7.4-fpm
~~~~

i) Edit Nginx config file /etc/nginx/conf.d/zabbix.conf and change listen and server_name settings
~~~~
listen          80;
server_name     <enter your domain>;
~~~~

f) Restart Nginx web-server
~~~~
nginx -t && nginx -s reload
~~~~

### 8. Start Zabbix server and agent processes

Start Zabbix server and agent processes and make it start at system boot
~~~~
systemctl enable zabbix-server zabbix-agent
systemctl restart zabbix-server zabbix-agent
~~~~

### 9. Configure Zabbix frontend

Connect to your newly installed Zabbix frontend: 

If use Apache: http://server_ip_or_name/zabbix

If use Nginx: http://server_ip_or_name

Follow steps described in official Zabbix documentation: [Installing frontend](https://www.zabbix.com/documentation/4.4/manual/installation/install#installing_frontend)
