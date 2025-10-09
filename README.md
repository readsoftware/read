# READ Software

The Research Environment for Ancient Documents (READ) is a software system supporting the scholarly study of ancient texts on their physical carriers. It provides facilities for linking images of an inscribed object with transcriptions, for handling multiple transcriptions of the same object in parallel, for linking original-language texts with translations, and for producing glossaries and paleographic charts. The initial focus of READ development has been on supporting documents in South Asian writing systems, but the software is intended to be general and useable for documents from any part of the ancient world. READ can be used offline on a scholar’s personal computer, or it can be installed on a server in a multi‐user setup. It uses PostgreSQL for storage, HTML/PHP/JavaScript for display and interaction and TEI P5 XML for export and import, allowing for integration of READ with existing TEI-based workflows.

READ is being developed by a core team consisting of [Stefan Baums](mailto:baums@lmu.de) (Munich, academic lead), [Andrew Glass](mailto:xadxura@live.com) (Seattle, system design), [Ian McCrabb](mailto:ian@prakas.org) (Sydney, project management) and [Stephen White](mailto:stephenawhite57@gmail.com) (Venice, programming). Other contributors include Arlo Griffiths, Yang Li and Andrea Schlosser. READ development has received generous funding from the Bavarian Academy of Sciences and Humanities, the University of Lausanne, the University of Washington, the Prakaś Foundation, the École française d’Extrême‐Orient and the University of Sydney. READ is open‐source software under the terms of the GNU General Public License version 3.

The following screenshots (courtesy of Andrea Schlosser) illustrate some of the functionality of READ.

Linking images to texts:

![Image-text linking](http://130.223.29.184/screenshots/read_image_linking.png)

Linking texts to translations:

![Text-text linking](http://130.223.29.184/screenshots/read_text_linking.png)

Creating glossaries for texts:

![Glossary](http://130.223.29.184/screenshots/read_glossary.png)

Creating paleographic tables:

![Paleography](http://130.223.29.184/screenshots/read_paleography.png)


## Dependencies
For this application to run, you should have installed and configured the following

1- **Docker**

2- **Apache2**


## Installation 

This section explains the installation of the software in a server using the Ubuntu operating system. Clone the repository

```bash
git clone https://github.com/readsoftware/read.git
```

Go to the directory and switch to the branch readlgch

```bash
cd read
git checkout -b readlgch
```

### Build
To build the application on the server you first need to config READ according to your server. 

```bash
cd read
cp config-sample.php config.php
```
In config.php you need to define $READ_DIR (line 9: the name of the directory where read is located), $DBNAME (line 11: the name of your database), $HOSTNAME (line 13: the domain of your host), PORT (line 44: the port where your database is running (default 5432)), DBSERVERNAME (line 46: the name of your database), USERNAME (line 48: the username to access the database), PASSWORD (line 50: password to access the database). After having configured all the variables above, you can start the application with the following commands:

```bash
cd read/docker
docker-compose up
```

Docker will now install all the packages needed for the software to run. It will create one container for pgadmin, read, flask admin, database.

**Note:** You should be careful of the permissions. Docker should run under a user that has the permission to access the directory where you have cloned the repository (Otherwise permission errors will appear). Another thing to be checked carefully is that while configuring in apache2, the user of apache2 (default www-data) should also have access to the READ directory, so it can then serve READ on the domain.

### Directories
READ requires some directories to be existing while trying to work with it. The structure of the directories should be as following.

    .
    ├── read                      # the main directory of read
    ├── d3                        # containing the d3.js file
       ├── d3.js 
    ├── export                    # contains all the exported xml
    ├── images                    # contains all images of all databases configured and the segments caches
       ├── db_name
          ├── txt1
          ├── .. 
       ├── segment_cache
    ├── jquery                    # contains all jquery files
    ├── jqwidget                  # contains all the jqwidgets (.js files)
       ├── jqwidgets
d3, export, jquery, jqwidget, and jqwidgets  need to be created in order READ to work. 


## APACHE configuration

An example configuration for apache can be found on `example/read.conf`. You can adjust it according to your specifications.

