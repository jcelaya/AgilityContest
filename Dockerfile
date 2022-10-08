FROM php:7.2-apache
ARG DEBIAN_FRONTEND=noninteractive
RUN docker-php-ext-install mysqli
# Include alternative DB driver
# RUN docker-php-ext-install pdo
# RUN docker-php-ext-install pdo_mysql
RUN apt-get update && apt-get install -y sendmail libpng-dev libzip-dev zlib1g-dev libonig-dev ssl-cert gettext locales-all default-mysql-client \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install zip \
    && docker-php-ext-install mbstring \
    && docker-php-ext-install gettext \
    && docker-php-ext-install bcmath \
    && docker-php-ext-install gd

RUN a2enmod rewrite \
    && a2enmod ssl

COPY extras/AgilityContest_docker.conf /etc/apache2/conf-enabled/
COPY ChangeLog /var/www/html/
COPY .htaccess /var/www/html/
RUN sed -i -E 's;__AC_WEBNAME__(.?)/;;' /var/www/html/.htaccess

RUN adduser --uid 1000 --gecos 'My Apache User' --disabled-password agility \
    && chown -R agility:agility /var/lock/apache2 /var/run/apache2 /var/www/html

ENV CONTAINER=true
