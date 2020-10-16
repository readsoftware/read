FROM postgres:10.14


EXPOSE 5432

# copy shell script to install postgres extensions
COPY install-hstore.sh /docker-entrypoint-initdb.d/

# One can copy any .sql to initialize startup data
# COPY testdb.sql /docker-entrypoint-initdb.d/