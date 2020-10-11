FROM postgres:10.14

# setup ENV for entry point scripts 
#RUN apt-get update -yqq && apt-get install postgresql-contrib
#ENV POSTGRES_PASSWORD postgres 
#ENV POSTGRES_DB testdb

EXPOSE 5432

# copy shell script to install postgres extensions
COPY install-hstore.sh /docker-entrypoint-initdb.d/
#COPY 2662test.sql /docker-entrypoint-initdb.d/