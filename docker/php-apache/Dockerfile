FROM php:7.3-apache

RUN apt-get update -yqq \
    && apt-get install git nano libpq-dev libzip-dev zlib1g-dev -y \
    && docker-php-ext-install json opcache \
    && docker-php-ext-install pgsql exif zip

# Install XDebug to debug php files 
RUN pecl install xdebug

# compose will create a shared folder to postgresql tools like pg_dump and psql
# so they can be used in this container
# add the directory to the PATH environment variable
ENV PATH $PATH:/usr/lib/postgresql/bin

# Note to document that this container "read" is exposed to the host on port 8081
# this will not actually do the mapping which is done in the ports: sections of the
# docker-compose.yml
EXPOSE 80

# create a php.ini file from the development version change to copy php.ini-production => php.ini for production
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

# enable xdebug
RUN docker-php-ext-enable xdebug

# precreate log file for xdebug
RUN echo " " >> /usr/local/etc/php/xdebug.log \
&& chown www-data:www-data /usr/local/etc/php/xdebug.log \
&& chmod 774 /usr/local/etc/php/xdebug.log

# create and move xdebug.ini initialization file to start up dir
# Add Xdebug to PHP configuration
RUN echo "" >> xdebug.ini \
 && echo "[xdebug]" >> xdebug.ini \
 && echo "zend_extension = /usr/local/lib/php/extensions/no-debug-non-zts-20180731/xdebug.so" >> xdebug.ini \
 && echo "xdebug.remote_enable = 1" >> xdebug.ini \
 && echo "xdebug.remote_autostart = 1" >> xdebug.ini \
 && echo "xdebug.remote_host = host.docker.internal" >> xdebug.ini\
 && echo "xdebug.default_enable=1" >> xdebug.ini\
 && echo "xdebug.remote_port=9000" >> xdebug.ini\
 && echo "xdebug.remote_connect_back=0" >> xdebug.ini\
 && echo "xdebug.idekey=VSCODE" >> xdebug.ini\
 && echo "xdebug.remote_log=/usr/local/etc/php/xdebug.log" >> xdebug.ini\
 && mv xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
