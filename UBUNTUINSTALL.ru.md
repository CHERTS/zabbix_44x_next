# Новая устанока из пакетов для Ubuntu

[In English / По-английски](UBUNTUINSTALL.md)

## Новая установка из пакетов для Ubuntu 20.04

### 1. Установка репозитория DBService

Устанавливаем GPG ключ
~~~~
wget -q -O - https://repo.dbservice.tech/dbservice-official-repo.key | apt-key add -
~~~~

Установка репозитария
~~~~
echo "deb [arch=$(dpkg --print-architecture)] https://repo.dbservice.tech/zabbix/4.4/ubuntu $(lsb_release -c -s) main" > /etc/apt/sources.list.d/dbs.list
~~~~

### 2. Обновление кэша пакетов

Обновите кэш пакетов
~~~~
apt-get update
~~~~

### 3. Установка Zabbix server (с поддержкой MySQL) и agent

~~~~
apt-get install zabbix-server-mysql zabbix-agent
~~~~

### 4. Установка web-интерфейса

Если будет использоваться веб-сервер Apache, то выполните
~~~~
apt-get install apache2
apt-get install zabbix-apache-conf
systemctl enable apache2
systemctl restart apache2
~~~~

Если будет использоваться веб-сервер Nginx с PHP-FPM, то выполните
~~~~
apt-get install nginx
apt-get install zabbix-nginx-conf php-fpm
systemctl enable nginx
systemctl restart nginx
~~~~

### 5. Создание и инициализация базы данных Zabbix (У Вас уже должен быть установлен MySQL)

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
zcat /usr/share/doc/zabbix-server-mysql/create.sql.gz | mysql -uzabbix -ppassword zabbix
~~~~

### 6. Редактирование конфигурации Zabbix server

Отредактируйте файл /etc/zabbix/zabbix_server.conf (установите пароль пользователя zabbix из предыдущего пункта)
~~~~
DBPassword=password
~~~~

### 7. Настройка PHP для работы веб-интерфейса Zabbix

Если будет использоваться веб-сервер Apache, то

а) Отредактируйте файл /etc/apache2/conf-enabled/zabbix.conf (раскоментируйте параметр отвечающий за временную зону, укажите свою зону)
~~~~
php_value date.timezone Europe/Riga
~~~~

б) Переапустите веб-сервер Apache
~~~~
systemctl restart apache2
~~~~

Если будет использоваться веб-сервер Nginx с PHP-FPM, то

а) Отредактируйте файл /etc/php/7.4/fpm/pool.d/zabbix-php-fpm.conf (раскоментируйте параметр отвечающий за временную зону, укажите свою зону)
~~~~
php_value[date.timezone] = Europe/Riga
~~~~

б) Запустите php-fpm, выполните
~~~~
systemctl enable php7.4-fpm
systemctl restart php7.4-fpm
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

### 8. Запуск Zabbix server и agent

Запустите процессы сервера и агента, выполните
~~~~
systemctl enable zabbix-server zabbix-agent
systemctl restart zabbix-server zabbix-agent
~~~~

### 9. Настройте веб-интерфейс Zabbix

Откройте в веб-браузере интерфейс Zabbix:

Если был настроен Apache: http://server_ip_or_name/zabbix

Если был настроен Nginx: http://server_ip_or_name

Настройте веб-интерфейс по официальной документации: [Установка веб-интерфейса](https://www.zabbix.com/documentation/4.4/ru/manual/installation/install#%D1%83%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0_%D0%B2%D0%B5%D0%B1-%D0%B8%D0%BD%D1%82%D0%B5%D1%80%D1%84%D0%B5%D0%B9%D1%81%D0%B0_zabbix)

