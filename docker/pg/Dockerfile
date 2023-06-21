FROM postgres:10.14


EXPOSE 5432

# copy shell script to install postgres extensions
COPY install-hstore.sh /docker-entrypoint-initdb.d/

# One can copy any .sql to initialize startup data
# COPY testdb.sql /docker-entrypoint-initdb.d/

RUN apt-get -y update
RUN apt-get install -y sudo
RUN sudo apt install -y git
RUN sudo apt-get -y install cron
RUN sudo service cron start
RUN sudo apt-get install -y nano
RUN mkdir /var/protect
RUN mkdir /var/protect/backup
RUN echo 'sudo -i -u postgres pg_dump --no-privileges --no-owner homer2 > /var/protect/backup/homer2_$(date +%d-%m-%y-[%H:%M]).sql' > /etc/cron.hourly/backup.sh
RUN chmod 777 /etc/cron.hourly/backup.sh
RUN sudo crontab -l | { cat; echo "* * * * * /etc/cron.hourly/backup.sh\n# An empty line is required at the end of this file for a valid cron file.";} | crontab -
# CMD '/bin/sh' '-c' '/usr/local/startup.sh; bash'> output.log
# CMD '/bin/sh' '-c' 'sleep 10 && /usr/local/startup.sh; bash' > output.log 2>&1 &
# CMD ["echo", "/usr/local/startup.sh"]