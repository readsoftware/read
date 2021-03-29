# READ Docker Setup for Development

READ (Research Environment for Ancient Documents) uses a multicontainer setup for development attempting to be seamless when coding, testing, debugging or using. READ is a SAP written with PHP, Javascript, and XSLT. It uses jQuery, jqWidgets, HTML5 with Canvas, D3.js and CSS2 to provide a multi-layer editing approach to text research. The setup for READ requires a Web Server, PHP server, and PostgreSQL server. This docker setup is customized for running VSCode with GIT Source Control that uses Windows 10 with a browser as the front end user interface and a Docker Milticontainer as a backend server. The Docker Milticontainer is organized into 3 services 'read', 'db' and 'pgadmin'. This container is intend to develop on a local machine and maps the Docker's host machine's source directory into the **read** container which allows direct code editing. It should be adjust for production and for Mac. It also assumes that the READ app/code is installed in a subdirectory from what is equivalent to the webservice document root. This allows to work on different language versions of READ from the same development machine. The framework dependencies are installed in this document root and shared between all READ versions.  

## READ Multicontainer 
  - 'read' contains:
    - PHP 7.3 service with XDebug
    - Apache2 web server
    - 3 volumes:
      - 'sharebin' a named volume that links to the container's */usr/lib/postgresql/bin* sharing access to postgreSQL tools used by some of the READ services
      - */var/www/readfilestore* bound volume used for persisted storage of READ database snapshots and is linked to the host's directory containing the developers .sql files
      - /var/www/html/*readdir* a bound volume to share code across host and docker container which is linked to the host's VSCode workspace directory for READ source code. Note that the name of the *readdir* can be anything and **must** match that used in READ's *config.php* file. Since the start of multi-ligual READ, a naming convention has started to emerge of the form the name *read* followed by the *Language* such **readMayan**, **readLatin**, etc. The reasonning for this is that this directory name naturally becomes part of the URL which ensures researchers are using the correct version.
      ```yml
      read:
        build: ./php-apache # relative directory to Dockerfile
        ports:
          - "80:80" # Map container port 80 to host port 80.
                    # This will allow the use of localhost directly
        volumes: 
          - sharebin:/usr/lib/postgresql/bin # share postgres tools with php server
                                             # to help maintain databases
          - ../..:/var/www/html/ # path_to_workspaceroot:absolute path in apache container
                                 # for document root
          - ../../../readfilestore:/var/www/readfilestore # path_to_database_sql's:absolute path
                                                          # to apache container of readfilestore
        depends_on: 
          - db #ensure db server is built first
      ```
  - 'db' contains:
    - PostgreSQL 10
    - 3 volumes:
      - 'sharebin' a named volume that links to postgreSQL tools used by some of the READ services
      - 'db-data' a named volume used to persist the database data across Docker Builds and is set to */var/lib/postgresql/data*
      - */var/readfilestore* bound volume used for READ db snapshots which is linked to the host directory containing the developers .sql files. By binding this directory the user can save .sql files from the pgAdmin interface.
      ```yml
      db:
        build: ./pg
        restart: always
        environment: 
          POSTGRES_DB: postgres
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: gandhari
          PGDATA: /var/lib/postgresql/data
        volumes:
          - sharebin:/usr/lib/postgresql/10/bin # make postgres tools available to other services
          - db-data:/var/lib/postgresql/data # create named volume and add to volumes
                                             # to persist container data
          - ../../../readfilestore:/var/readfilestore # using relative path to bind host dir
                                                      # to container's /var/readfilestore
        ports:
          - "5432:5432"
      ```
  - 'pgadmin' contains:
    - PgAdmin 4
    - 1 volume:
      - 'pgadmin-data' a named volume used to persist pgAdmin data across builds
    ```yml
      pgadmin:
        image: dpage/pgadmin4:4.18
        restart: always
        environment: 
          PGADMIN_DEFAULT_EMAIL: xx@xx.uu.com # login id for browser interface
          PGADMIN_DEFAULT_PASSWORD: gandhari # password for the same
          PGADMIN_LISTEN_PORT: 80
        ports:
          - "81:80" # "host_port:container_port". pgadmin can be found in host browser as localhost:81
        volumes: 
          - pgadmin-data:/var/lib/pgadmin # create named volume to persist container data
        links: 
          - "db:pgsql-server"
        depends_on: 
          - db
    ```  
     A *Volume* section is added to save the named volumes in Docker reserved area for persistance across runs and builds.
     ```yml
    volumes: #these volumes will be saved in docker storage until deleted
      sharebin:
      db-data:
      pgadmin-data:
     ```
---
## READ Docker Files
The files used to Dockerize READ are 2 *Dockerfile* files and a *docker-compose.yml* file. The yml file is written with the file structure in mind by using relative paths where needed. These may need to be customized for the developers file structure or if the docker directory is moved.

The 2 Dockerfiles specify a custom build of the 'php-apache' and 'db' containers. The 'php-apache' has instruction to add needed extensions to PHP for READ and to install and configure XDebug. It also adds the 'sharebin' local directory' to the environment PATH variable. The 'db' file copy a start up .sh script so postgres installation will run commands that enable extensions for postgres used by READ.
```bash
    set -e

    psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-EOSQL
    create extension hstore;
    create extension dblink;
    EOSQL
```


---
## Using READ's Multicontainer:
In a terminal, navigate to the docker directory and type **docker-compose up**. The first time will take a shile as Docker will down load and install all the needed modules. This starts the READ Docker Multicontainer. It will adjust for any changes that affect the containers and will start posting status information about the running containers. Pressing **ctrl+c** will shut down/stop the containers. Note that the images of the containers are cached and the next **docker-compose up** will restart the READ Multicontainer without rebuilding. If you need to ensure a rebuild you can use **docker-compose up --build** . Also using **docker-compose down** will purge the images and rebuild using cache modules. VSCode has a docker interface so that you can remove any cached image you would like to update as well as volumes that you would like to flush. These are rebuilt on the next start of the Multicontainer.


### Development XDEBUG

In the ./php-apache directory you will find the Dockerfile for webserver container with PHP.
for debugging it creates a xdebug.ini file and places it in the /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
where PHP reads initializations parameters.
XDEBUG is configured (see: https://xdebug.org/docs/all_settings) to trigger a profile or a trace from using a URL
parameter XDEBUG_PROFILE or XDEBUG_TRACE. This is usefull to debug services in isolation such as 
http://localhost/readV1RC/services/getEditionSyntaxData.php?db=gandhari_staging&ednID=2&XDEBUG_PROFILE
or
http://localhost/readV1RC/services/getEditionSyntaxData.php?db=gandhari_staging&ednID=2&XDEBUG_TRACE

When these services finish XDEBUG completes writing to the output file in the configured directories.

Since the READ code dir is mapped into this container and assuming you have created a trace and profile directory under
READ's  'dev' directory you can use the following commands from a command window to move the output to dev/profile or dev/trace

```Bash
docker exec -t docker_read_1 bash -c "mv /usr/local/etc/php/profiles/*.xt /var/
www/html/readV1RC/dev/profiles/"

docker exec -t docker_read_1 bash -c "mv /usr/local/etc/php/traces/*.xt /var/
www/html/readV1RC/dev/traces/"
```
The output can be inspected on Windows using https://sourceforge.net/projects/qcachegrindwin/  

```DOckerfile
    # enable xdebug
    RUN docker-php-ext-enable xdebug

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
    && echo "xdebug.default_enable=1" >> xdebug.ini\
    && echo "xdebug.remote_port=9000" >> xdebug.ini\
    && echo "xdebug.remote_connect_back=0" >> xdebug.ini\
    && echo "xdebug.idekey=VSCODE" >> xdebug.ini\
    && echo "xdebug.remote_log=/usr/local/etc/php/xdebug.log" >> xdebug.ini\
    && mv xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
```
