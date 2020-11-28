FROM php:7.3-apache

RUN apt-get update -y --fix-missing \
    && apt-get upgrade -y \
    && apt-get install -y --fix-missing --no-install-recommends \
    git \
    nano \
    unzip \
    zip \
    libpq-dev \
    libzip-dev \
    zlib1g-dev -y \
    libmagick++-dev \
    libmagickwand-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev

# Install php extension like XDebug to debug php files 
RUN pecl install xdebug-2.7.2 \
  && docker-php-ext-enable xdebug

RUN docker-php-ext-configure gd \
    --with-jpeg-dir=/usr/include/ \
    --with-freetype-dir=/usr/include/ \
  && docker-php-ext-configure gd \
  && docker-php-ext-configure zip \
  && docker-php-ext-install zip

RUN docker-php-ext-install mbstring \
  && docker-php-ext-install opcache \
  && docker-php-ext-install pgsql \
  && docker-php-ext-install exif


# compose will create a shared folder to postgresql tools like pg_dump and psql
# so they can be used in this container
# add the directory to the PATH environment variable
ENV PATH $PATH:/usr/lib/postgresql/bin

# Note to document that this container "read" is exposed to the host on port 8081
# this will not actually do the mapping which is done in the ports: sections of the
# docker-compose.yml
EXPOSE 80

WORKDIR /usr/local/etc/php
# create a php.ini file from the development version change to copy php.ini-production => php.ini for production
RUN cp php.ini-development php.ini
# Update Php Settings
RUN sed -E -i -e 's/max_execution_time = 30/max_execution_time = 200/' php.ini \
 && sed -E -i -e 's/memory_limit = 128M/memory_limit = 512M/' php.ini \
 && sed -E -i -e 's/post_max_size = 8M/post_max_size = 32000000/' php.ini \
 && sed -E -i -e 's/upload_max_filesize = 2M/upload_max_filesize = 32000000/' php.ini

# enable xdebug

# precreate log file for xdebug
RUN echo " " >> xdebug.log \
&& chown www-data:www-data xdebug.log \
&& chmod 774 xdebug.log \
# precreate directory for xdebug profiler
&& mkdir profiles \
&& chown www-data:www-data profiles \
&& chmod 774 profiles\
# precreate directory for xdebug tracer
&& mkdir traces \
&& chown www-data:www-data traces \
&& chmod 774 traces

# create and move xdebug.ini initialization file to start up dir
# Add Xdebug to PHP configuration
# See https://xdebug.org/docs/all_settings
RUN echo "" >> xdebug.ini \
 && echo "[xdebug]" >> xdebug.ini \
 && echo "zend_extension = /usr/local/lib/php/extensions/no-debug-non-zts-20180731/xdebug.so" >> xdebug.ini \
 && echo "xdebug.remote_enable = 1" >> xdebug.ini \
#profile setting
 && echo "xdebug.profiler_enable = 0" >> xdebug.ini \
#               use url param XDEBUG_PROFILE nothing or secret found in profile_enable_trigger_value
 && echo "xdebug.profiler_enable_trigger = 1" >> xdebug.ini \
 && echo "xdebug.profiler_output_name = readxdebug.out.%t.pro" >> xdebug.ini \
 && echo "xdebug.profiler_output_dir = /usr/local/etc/php/profiles" >> xdebug.ini \
#trace setting
 && echo "xdebug.trace_enable_trigger = 1" >> xdebug.ini \
#               use url param XDEBUG_TRACE nothing or secret found in trace_enable_trigger_value
 && echo "xdebug.trace_output_name = readtrace.%c" >> xdebug.ini \
 && echo "xdebug.trace_output_dir = /usr/local/etc/php/traces" >> xdebug.ini \
#
 && echo "xdebug.remote_autostart = 1" >> xdebug.ini \
 && echo "xdebug.remote_host = host.docker.internal" >> xdebug.ini\
 && echo "xdebug.default_enable=0" >> xdebug.ini\
 && echo "xdebug.remote_port=9000" >> xdebug.ini\
 && echo "xdebug.remote_connect_back=0" >> xdebug.ini\
 && echo "xdebug.idekey=VSCODE" >> xdebug.ini\
 && echo "xdebug.remote_log=/usr/local/etc/php/xdebug.log" >> xdebug.ini\
 && mv xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www/html
