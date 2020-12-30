# Новая устанока из пакетов для RedHat/CentOS/OracleLinux

[In English / По-английски](RHELINSTALL.md)

[Новая установка из пакетов для RedHat/CentOS/OracleLinux 7](#redhat-7)

[Новая установка из пакетов для RedHat/CentOS/OracleLinux 8](#redhat-8)

# RedHat 7
## Новая устанока из пакетов для RedHat / CentOS / Oracle Linux 7

### 1. Установка репозитория DBService

Если Вы используете Oracle Linux 7, то включаем репозитории ol7_optional_latest и ol7_developer_EPEL

~~~~
yum-config-manager --enable ol7_optional_latest
yum-config-manager --enable ol7_developer_EPEL
~~~~

Если Вы используете RedHat 7, то включаем репозиторий rhel-7-server-optional-rpms

~~~~
yum-config-manager --enable rhel-7-server-optional-rpms
~~~~

Установка репозитория DBService

~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/7/x86_64/dbs-release-4.4-1.el7.noarch.rpm
yum clean all
yum makecache fast
~~~~


### 2. Установка Zabbix server (с поддержкой MySQL) и agent

~~~~
yum install zabbix-server-mysql zabbix-agent
~~~~

### 3. Установка web-интерфейса

Если будет использоваться веб-сервер Apache, то выполните
~~~~
yum install httpd httpd-tools
yum install zabbix-web-mysql
systemctl enable httpd.service
systemctl start httpd.service
~~~~

Если будет использоваться веб-сервер Nginx с PHP-FPM, то выполните
~~~~
yum install nginx
yum install zabbix-web-mysql zabbix-nginx-conf php-fpm
systemctl enable nginx.service
systemctl start nginx.service
~~~~

Если у Вас активирован SELinux в режиме "enforcing", то выполните
~~~~
setsebool -P httpd_can_connect_zabbix on
setsebool -P httpd_can_network_connect_db on
~~~~

Если у Вас активирован SELinux в режиме "enforcing", то создайте правило для Zabbix-server:
~~~~
yum install policycoreutils-devel

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

Если включен firewalld, то разрешим правила для порта 80/443 (Apache/Nginx service) и для порта 10050/10051 (Zabbix agent и Zabbix server)
~~~~
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --permanent --add-port=443/tcp
firewall-cmd --permanent --add-port=10050/tcp
firewall-cmd --permanent --add-port=10051/tcp
firewall-cmd --reload
~~~~

### 4. Создание и инициализация базы данных Zabbix (У Вас уже должен быть установлен MySQL)

Войдите в консоль mysql, создайте БД и пользователя:
~~~~
# mysql -uroot -p
password: *******
mysql> create database zabbix character set utf8 collate utf8_bin;
mysql> create user zabbix@localhost identified by 'password';
mysql> grant all privileges on zabbix.* to zabbix@localhost;
mysql> flush privileges;
mysql> quit;
~~~~

Импортируйте схему и данные:
~~~~
zcat /usr/share/doc/zabbix-server-mysql*/create.sql.gz | mysql -uzabbix -ppassword zabbix
~~~~

### 5. Редактирование конфигурации Zabbix server

Отредактируйте файл /etc/zabbix/zabbix_server.conf (установите пароль пользователя zabbix из предыдущего пункта)
~~~~
DBPassword=password
~~~~

### 6. Настройка PHP для работы веб-интерфейса Zabbix

Если будет использоваться веб-сервер Apache, то

а) Отредактируйте файл /etc/httpd/conf.d/zabbix.conf (раскоментируйте параметр отвечающий за временную зону, укажите свою зону)
~~~~
php_value date.timezone Europe/Riga
~~~~

б) Переапустите веб-сервер Apache
~~~~
systemctl restart httpd
~~~~

Если будет использоваться веб-сервер Nginx с PHP-FPM, то

а) Отредактируйте файл /etc/php-fpm.d/zabbix.conf (раскоментируйте параметр отвечающий за временную зону, укажите свою зону)
~~~~
php_value[date.timezone] = Europe/Riga
~~~~

б) Запустите php-fpm, выполните
~~~~
systemctl enable php-fpm
systemctl start php-fpm
~~~~

в) Отредактируйте файл /etc/nginx/conf.d/zabbix.conf (уберите коментарий из директив listen и server_name, введите свои данные)
~~~~
listen          80;
server_name     <enter your domain>;
~~~~

г) Перезапустите Nginx
~~~~
nginx -t && nginx -s reload
~~~~

### 7. Запуск Zabbix server и agent

Запустите процессы сервера и агента, выполните
~~~~
systemctl enable zabbix-server zabbix-agent
systemctl restart zabbix-server zabbix-agent
~~~~

### 8. Настройте веб-интерфейс Zabbix

Откройте в веб-браузере интерфейс Zabbix:

Если был настроен Apache: http://server_ip_or_name/zabbix

Если был настроен Nginx: http://server_ip_or_name

Настройте веб-интерфейс по официальной документации: [Установка веб-интерфейса](https://www.zabbix.com/documentation/4.4/ru/manual/installation/install#%D1%83%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0_%D0%B2%D0%B5%D0%B1-%D0%B8%D0%BD%D1%82%D0%B5%D1%80%D1%84%D0%B5%D0%B9%D1%81%D0%B0_zabbix)

# RedHat 8
## Новая устанока из пакетов для RedHat / CentOS / Oracle Linux 8

### 1. Установка репозитория DBService

~~~~
rpm -Uvh https://repo.dbservice.tech/zabbix/4.4/rhel/8/x86_64/dbs-release-4.4-1.el8.noarch.rpm
dnf clean all
dnf makecache
~~~~

### 2. Установка Zabbix server (с поддержкой MySQL) и agent

~~~~
dnf install zabbix-server-mysql zabbix-agent
~~~~

### 3. Установка web-интерфейса

Если будет использоваться веб-сервер Apache, то выполните
~~~~
dnf install httpd httpd-tools
dnf install zabbix-web-mysql zabbix-apache-conf
sed -i 's/listen.owner = nginx/listen.owner = apache/g' /etc/php-fpm.d/zabbix.conf
systemctl enable httpd.service
systemctl start httpd.service
~~~~

Если будет использоваться веб-сервер Nginx + PHP-FPM, то выполните
~~~~
dnf install epel-release
dnf install nginx
dnf install zabbix-web-mysql zabbix-nginx-conf
systemctl enable nginx.service
systemctl start nginx.service
~~~~

Если у Вас активирован SELinux в режиме "enforcing", то выполните
~~~~
setsebool -P httpd_can_connect_zabbix on
setsebool -P httpd_can_network_connect_db on
~~~~

Если у Вас активирован SELinux в режиме "enforcing", то создайте правило для Zabbix-server
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

Если включен firewalld, то разрешим правила для порта 80/443 (Apache/Nginx service) и для порта 10050/10051 (Zabbix agent и Zabbix server)
~~~~
firewall-cmd --permanent --add-service=http
firewall-cmd --permanent --add-service=https
firewall-cmd --permanent --add-port=80/tcp
firewall-cmd --permanent --add-port=443/tcp
firewall-cmd --permanent --add-port=10050/tcp
firewall-cmd --permanent --add-port=10051/tcp
firewall-cmd --reload
~~~~

### 4. Создание и инициализация базы данных Zabbix (У Вас уже должен быть установлен MySQL)

Войдите в консоль mysql, создайте БД и пользователя:
~~~~
# mysql -uroot -p
password: *******
mysql> create database zabbix character set utf8 collate utf8_bin;
mysql> create user zabbix@localhost identified by 'password';
mysql> grant all privileges on zabbix.* to zabbix@localhost;
mysql> flush privileges;
mysql> quit;
~~~~

Импортируйте схему и данные:
~~~~
zcat /usr/share/doc/zabbix-server-mysql*/create.sql.gz | mysql -uzabbix -ppassword zabbix
~~~~

### 5. Редактирование конфигурации Zabbix server

Отредактируйте файл /etc/zabbix/zabbix_server.conf (установите пароль пользователя zabbix из предыдущего пункта)
~~~~
DBPassword=password
~~~~

### 6. Настройка PHP для работы веб-интерфейса Zabbix

Отредактируйте файл /etc/php-fpm.d/zabbix.conf (раскоментируйте параметр отвечающий за временную зону, укажите свою зону)
~~~~
php_value[date.timezone] = Europe/Riga
~~~~

Запустите php-fpm, выполните
~~~~
systemctl enable php-fpm
systemctl start php-fpm
~~~~

### 7. Запуск Zabbix server и agent

Запустите процессы сервера и агента, выполните
~~~~
systemctl enable zabbix-server zabbix-agent
systemctl restart zabbix-server zabbix-agent
~~~~

### 8. Настройте веб-интерфейс Zabbix

Откройте в веб-браузере интерфейс Zabbix: http://server_ip_or_name/zabbix

Настройте веб-интерфейс по официальной документации: [Установка веб-интерфейса](https://www.zabbix.com/documentation/4.4/ru/manual/installation/install#%D1%83%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0_%D0%B2%D0%B5%D0%B1-%D0%B8%D0%BD%D1%82%D0%B5%D1%80%D1%84%D0%B5%D0%B9%D1%81%D0%B0_zabbix)
