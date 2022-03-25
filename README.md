Symfony API
===========

#### Технические требования: ####

* PHP версии не ниже 7.3
* MySQL/PerconaDB/MariaDB
* php extensions (помимо стандартных):
    * posix для нормального вывода логов и информации
    * intl для валидации 
    * mcrypt и openssl для хэширования и шифрования
    * curl для запросов во внешние API
    * mbstring для решения задач мультиязычности и кодировок
    * mysqli / mysqlnd, pdo, pdo_mysql для работы с БД
    * opcache для оптимизации скорости работы
    * redis
    * apcu

#### Используемые бандлы: ####

* _FOSRestBundle_

    Бандл для роутинга и имплементации REST API

* _NelmioCorsBundle_

    Бандл для обработки CORS-запросов

* _MigrationsBundle_
    
    Миграции в БД