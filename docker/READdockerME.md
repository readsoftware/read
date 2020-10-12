# READ Docker Setup for Development

READ (Research Environment for Ancient Documents) uses a multicontainer setup for development attempting to be seamless when coding, testing, debugging or using. READ is a SAP written with PHP, Javascript, and XSLT. It uses jQuery, jqWidgets, HTML5 with Canvas, D3.js and CSS2 to provide a multi-layer editing approach to text research. The setup for READ requires a Web Server, PHP server, and PostgreSQL server. This docker setup is customized for running VSCode with GIT Source Control that uses Windows 10 with a browser as the front end user interface and a Docker Container Swarm as a backend server. The Docker swarm is organized into 3 services 'read', 'db' and 'pgadmin'. 

## READ Swarm 
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
## Using READ's Swarm:
In a terminal, navigate to the docker directory and type **docker-compose up**. The first time will take a shile as Docker will down load and install all the needed modules. This starts the READ Docker swarm. It will adjust for any changes that affect the containers and will start posting status information about the running containers. Pressing **ctrl+c** will shut down/stop the containers. Note that the images of the containers are cached and the next **docker-compose up** will restart the READ swarm without rebuilding. If you need to ensure a rebuild you can use **docker-compose up --build** . Also using **docker-compose down** will purge the images and rebuild using cache modules. VSCode has a docker interface so that you can remove any cached image you would like to update as well as volumes that you would like to flush. These are rebuilt on the next start of the swarm.




