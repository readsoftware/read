# READ Installation Instructions 
## Server Setup - (Feb 2019  Instructions in NEED of UPDATE)
The READ software runs on GNU/Linux as an SPA site {{explain}} that runs on an LAPP setup (Linux, Apache, PHP and PostgreSQL). It  uses jQuery and jQWidgets for the frontend library. These instructions are for installing Apache, PHP, and PostgreSQL with phpPgAdmin on an Ubuntu 18.04.2 LTS virtual machine installation, followed by installing READ itself, configuring READ, and setting up directory structures for various READ services.

## Assumptions
* a Ubuntu 18.04.2 LTS virtual machine
* root access to this machine via SSH

### Verify System Information
#### Connect with SSH
{{use user@example.com or other generic URL}}
  ```bash
  localhost:~$ ssh username@readvm.myCloudSP.com
  Welcome to Ubuntu 18.04.2 LTS (GNU/Linux 4.15.0-1037-azure x86_64)

   * Documentation:  https://help.ubuntu.com
   * Management:     https://landscape.canonical.com
   * Support:        https://ubuntu.com/advantage

    System information as of Mon Feb 11 15:35:59 UTC 2019

    System load:  0.0               Processes:           120
    Usage of /:   5.2% of 28.90GB   Users logged in:     0
    Memory usage: 4%                IP address for eth0: 10.0.0.4
    Swap usage:   0%

  0 packages can be updated.
  0 updates are security updates.

  Last login: Mon Feb 11 14:38:06 2019 from xxx.xxx.xxx.xxx
  username@readVM:~$
  ```
#### Check Release
  ```bash
  username@readVM:~$ lsb_release -a
  No LSB modules are available.
  Distributor ID: Ubuntu
  Description:    Ubuntu 18.04.2 LTS
  Release:        18.04
  Codename:       bionic
  username@readVM:~$
  ```
#### Check Hardware Resources
  ```bash
  username@readVM:~$ sudo lshw -html >  system_vm_hwinfo.html
  username@readVM:~$ df -ha > df_info.txt
  username@readVM:~$ ls -lst
  total 196
    4 -rw-rw-r-- 1 username username   1970 Feb 11 14:50 df_info.txt
  192 -rw-rw-r-- 1 username username 195141 Feb 11 14:49 system_vm_hwinfo.html
  username@readVM:~$
  ```
#### Check Software Sources
  ```bash
  username@readVM:~$ less /etc/apt/sources.list

  ## Note, this file is written by cloud-init on first boot of an instance
  ## modifications made here will not survive a re-bundle.
  ## if you wish to make changes you can:
  ## a.) add 'apt_preserve_sources_list: true' to /etc/cloud/cloud.cfg
  ##     or do the same in user-data
  ## b.) add sources in /etc/apt/sources.list.d
  ## c.) make changes to template file /etc/cloud/templates/sources.list.tmpl

  # See http://help.ubuntu.com/community/UpgradeNotes for how to upgrade to
  # newer versions of the distribution.
  deb http://azure.archive.ubuntu.com/ubuntu/ bionic main restricted
  deb-src http://azure.archive.ubuntu.com/ubuntu/ bionic main restricted

  ## Major bug fix updates produced after the final release of the
  ## distribution.
  deb http://azure.archive.ubuntu.com/ubuntu/ bionic-updates main restricted
  deb-src http://azure.archive.ubuntu.com/ubuntu/ bionic-updates main restricted

  ## N.B. software from this repository is ENTIRELY UNSUPPORTED by the Ubuntu
  ## team. Also, please note that software in universe WILL NOT receive any
  ## review or updates from the Ubuntu security team.
  deb http://azure.archive.ubuntu.com/ubuntu/ bionic universe
  deb-src http://azure.archive.ubuntu.com/ubuntu/ bionic universe
  deb http://azure.archive.ubuntu.com/ubuntu/ bionic-updates universe
  deb-src http://azure.archive.ubuntu.com/ubuntu/ bionic-updates universe

  ## N.B. software from this repository is ENTIRELY UNSUPPORTED by the Ubuntu
  ## team, and may not be under a free licence. Please satisfy yourself as to
  ## your rights to use the software. Also, please note that software in
  ## multiverse WILL NOT receive any review or updates from the Ubuntu
  ## security team.
  deb http://azure.archive.ubuntu.com/ubuntu/ bionic multiverse
  deb-src http://azure.archive.ubuntu.com/ubuntu/ bionic multiverse
  deb http://azure.archive.ubuntu.com/ubuntu/ bionic-updates multiverse
  deb-src http://azure.archive.ubuntu.com/ubuntu/ bionic-updates multiverse

  ## N.B. software from this repository may not have been tested as
  ## extensively as that contained in the main release, although it includes
  ## newer versions of some applications which may provide useful features.
  ## Also, please note that software in backports WILL NOT receive any review
  ## or updates from the Ubuntu security team.
  deb http://azure.archive.ubuntu.com/ubuntu/ bionic-backports main restricted universe multiverse
  deb-src http://azure.archive.ubuntu.com/ubuntu/ bionic-backports main restricted universe multiverse

  deb http://security.ubuntu.com/ubuntu bionic-security main restricted
  deb-src http://security.ubuntu.com/ubuntu bionic-security main restricted
  deb http://security.ubuntu.com/ubuntu bionic-security universe
  deb-src http://security.ubuntu.com/ubuntu bionic-security universe
  deb http://security.ubuntu.com/ubuntu bionic-security multiverse
  deb-src http://security.ubuntu.com/ubuntu bionic-security multiverse
  /etc/apt/sources.list
  ```
#### Check Installed Software Packages
  ```
  username@readVM:~$ apt list --installed
  Listing... Done
  accountsservice/bionic,now 0.6.45-1ubuntu1 amd64 [installed]
  acl/bionic,now 2.2.52-3build1 amd64 [installed]
  acpid/bionic,now 1:2.0.28-1ubuntu1 amd64 [installed]
  adduser/bionic,now 3.116ubuntu1 all [installed]
  apparmor/bionic-updates,bionic-security,now 2.12-4ubuntu5.1 amd64 [installed]
  apport/bionic-updates,now 2.20.9-0ubuntu7.5 all [installed]
  apport-symptoms/bionic,now 0.20 all [installed]
  apt/bionic-updates,now 1.6.8 amd64 [installed]
  apt-utils/bionic-updates,now 1.6.8 amd64 [installed]
  at/bionic,now 3.1.20-3.1ubuntu2 amd64 [installed]
  base-files/bionic-updates,now 10.1ubuntu2.4 amd64 [installed]
  base-passwd/bionic,now 3.5.44 amd64 [installed]
  bash/bionic,now 4.4.18-2ubuntu1 amd64 [installed]
  bash-completion/bionic,now 1:2.8-1ubuntu1 all [installed]
  bc/bionic,now 1.07.1-2 amd64 [installed]
  bcache-tools/bionic,now 1.0.8-2build1 amd64 [installed]
  bind9-host/bionic-updates,now 1:9.11.3+dfsg-1ubuntu1.3 amd64 [installed]
  bsdmainutils/bionic,now 11.1.2ubuntu1 amd64 [installed]
  bsdutils/bionic-updates,now 1:2.31.1-0.4ubuntu3.3 amd64 [installed]
  btrfs-progs/bionic,now 4.15.1-1build1 amd64 [installed]
  btrfs-tools/bionic,now 4.15.1-1build1 amd64 [installed]
  busybox-initramfs/bionic,now 1:1.27.2-2ubuntu3 amd64 [installed]
  busybox-static/bionic,now 1:1.27.2-2ubuntu3 amd64 [installed]
  byobu/bionic,now 5.125-0ubuntu1 all [installed]
  bzip2/bionic,now 1.0.6-8.1 amd64 [installed]
  ...
  gzip/bionic,now 1.6-5ubuntu1 amd64 [installed]
  ...
  nano/bionic,now 2.9.3-2 amd64 [installed]
  ...
  openssh-server/bionic-updates,bionic-security,now 1:7.6p1-4ubuntu0.2 amd64 [installed]
  openssh-sftp-server/bionic-updates,bionic-security,now 1:7.6p1-4ubuntu0.2 amd64 [installed]
  openssl/bionic-updates,bionic-security,now 1.1.0g-2ubuntu4.3 amd64 [installed]
  os-prober/bionic,now 1.74ubuntu1 amd64 [installed,automatic]
  ...
  passwd/bionic,now 1:4.5-1ubuntu1 amd64 [installed]
  pastebinit/bionic,now 1.5-2 all [installed]
  patch/bionic,now 2.7.6-2ubuntu1 amd64 [installed]
  pciutils/bionic,now 1:3.5.2-1ubuntu1 amd64 [installed]
  perl/bionic-updates,bionic-security,now 5.26.1-6ubuntu0.3 amd64 [installed]
  perl-base/bionic-updates,bionic-security,now 5.26.1-6ubuntu0.3 amd64 [installed]
  perl-modules-5.26/bionic-updates,bionic-security,now 5.26.1-6ubuntu0.3 all [installed]
  ...
  python/bionic,now 2.7.15~rc1-1 amd64 [installed,automatic]
  python-apt-common/bionic-updates,now 1.6.3ubuntu1 all [installed]
  python-crypto/bionic,now 2.6.1-8ubuntu2 amd64 [installed,automatic]
  python-ldb/bionic,now 2:1.2.3-1 amd64 [installed,automatic]
  python-minimal/bionic,now 2.7.15~rc1-1 amd64 [installed,automatic]
  python-samba/bionic-updates,now 2:4.7.6+dfsg~ubuntu-0ubuntu2.6 amd64 [installed,automatic]
  python-talloc/bionic,now 2.1.10-2ubuntu1 amd64 [installed,automatic]
  python-tdb/bionic,now 1.3.15-2 amd64 [installed,automatic]
  python2.7/bionic-updates,bionic-security,now 2.7.15~rc1-1ubuntu0.1 amd64 [installed,automatic]
  python2.7-minimal/bionic-updates,bionic-security,now 2.7.15~rc1-1ubuntu0.1 amd64 [installed,automatic]
  python3/bionic-updates,now 3.6.7-1~18.04 amd64 [installed]
  python3-apport/bionic-updates,now 2.20.9-0ubuntu7.5 all [installed]
  python3-apt/bionic-updates,now 1.6.3ubuntu1 amd64 [installed]
  python3-asn1crypto/bionic,now 0.24.0-1 all [installed]
  python3-attr/bionic,now 17.4.0-2 all [installed]
  python3-automat/bionic,now 0.6.0-1 all [installed]
  python3-blinker/bionic,now 1.4+dfsg1-0.1 all [installed]
  python3-certifi/bionic,now 2018.1.18-2 all [installed]
  python3-cffi-backend/bionic,now 1.11.5-1 amd64 [installed]
  python3-chardet/bionic,now 3.0.4-1 all [installed]
  python3-click/bionic,now 6.7-3 all [installed]
  python3-colorama/bionic,now 0.3.7-1 all [installed]
  python3-commandnotfound/bionic-updates,now 18.04.5 all [installed]
  python3-configobj/bionic,now 5.0.6-2 all [installed]
  python3-constantly/bionic,now 15.1.0-1 all [installed]
  ...
  python3-update-manager/bionic-updates,now 1:18.04.11.9 all [installed]
  python3-urllib3/bionic,now 1.22-1 all [installed]
  python3-yaml/bionic,now 3.12-1build2 amd64 [installed]
  python3-zope.interface/bionic,now 4.3.2-1build2 amd64 [installed]
  python3.6/bionic-updates,now 3.6.7-1~18.04 amd64 [installed]
  python3.6-minimal/bionic-updates,now 3.6.7-1~18.04 amd64 [installed]
  readline-common/bionic,now 7.0-3 all [installed]
  rsync/bionic,now 3.1.2-2.1ubuntu1 amd64 [installed]
  rsyslog/bionic,now 8.32.0-1ubuntu4 amd64 [installed]
  run-one/bionic,now 1.17-0ubuntu1 all [installed]
  samba-common/bionic-updates,now 2:4.7.6+dfsg~ubuntu-0ubuntu2.6 all [installed,automatic]
  samba-common-bin/bionic-updates,now 2:4.7.6+dfsg~ubuntu-0ubuntu2.6 amd64 [installed,automatic]
  samba-libs/bionic-updates,now 2:4.7.6+dfsg~ubuntu-0ubuntu2.6 amd64 [installed,automatic]
  sbsigntool/bionic,now 0.6-3.2ubuntu2 amd64 [installed]
  screen/bionic-updates,now 4.6.2-1ubuntu1 amd64 [installed]
  scx/now 1.6.3.659 amd64 [installed,local]
  secureboot-db/bionic-updates,now 1.4~ubuntu0.18.04.1 amd64 [installed]
  sed/bionic,now 4.4-2 amd64 [installed]
  sensible-utils/bionic,now 0.0.12 all [installed]
  shared-mime-info/bionic,now 1.9-2 amd64 [installed]
  ...
  ssh-import-id/bionic-updates,now 5.7-0ubuntu1.1 all [installed]
  strace/bionic,now 4.21-1ubuntu1 amd64 [installed]
  sudo/bionic,now 1.8.21p2-3ubuntu1 amd64 [installed]
  systemd/bionic-updates,now 237-3ubuntu10.12 amd64 [installed]
  systemd-sysv/bionic-updates,now 237-3ubuntu10.12 amd64 [installed]
  sysvinit-utils/bionic,now 2.88dsf-59.10ubuntu1 amd64 [installed]
  tar/bionic-updates,now 1.29b-2ubuntu0.1 amd64 [installed]
  tcpdump/bionic,now 4.9.2-3 amd64 [installed]
  telnet/bionic,now 0.17-41 amd64 [installed]
  time/bionic,now 1.7-25.1build1 amd64 [installed]
  tmux/bionic-updates,now 2.6-3ubuntu0.1 amd64 [installed]
  tzdata/bionic-updates,bionic-security,now 2018i-0ubuntu0.18.04 all [installed]
  ubuntu-advantage-tools/bionic,now 17 all [installed]
  ubuntu-keyring/bionic-updates,now 2018.09.18.1~18.04.0 all [installed]
  ubuntu-minimal/bionic,now 1.417 amd64 [installed]
  ubuntu-release-upgrader-core/bionic-updates,now 1:18.04.30 all [installed]
  ubuntu-server/bionic,now 1.417 amd64 [installed]
  ubuntu-standard/bionic,now 1.417 amd64 [installed]
  ucf/bionic,now 3.0038 all [installed]
  udev/bionic-updates,now 237-3ubuntu10.12 amd64 [installed]
  ufw/bionic,now 0.35-5 all [installed]
  uidmap/bionic,now 1:4.5-1ubuntu1 amd64 [installed]
  unattended-upgrades/bionic-updates,now 1.1ubuntu1.18.04.8 all [installed]
  update-manager-core/bionic-updates,now 1:18.04.11.9 all [installed]
  update-notifier-common/bionic-updates,now 3.192.1.5 all [installed]
  ureadahead/bionic,now 0.100.0-20 amd64 [installed]
  usbutils/bionic,now 1:007-4build1 amd64 [installed]
  util-linux/bionic-updates,now 2.31.1-0.4ubuntu3.3 amd64 [installed]
  uuid-runtime/bionic-updates,now 2.31.1-0.4ubuntu3.3 amd64 [installed]
  vim/bionic,now 2:8.0.1453-1ubuntu1 amd64 [installed]
  vim-common/bionic,now 2:8.0.1453-1ubuntu1 all [installed]
  vim-runtime/bionic,now 2:8.0.1453-1ubuntu1 all [installed]
  vim-tiny/bionic,now 2:8.0.1453-1ubuntu1 amd64 [installed]
  walinuxagent/bionic-updates,now 2.2.32-0ubuntu1~18.04.1 amd64 [installed]
  wget/bionic-updates,bionic-security,now 1.19.4-1ubuntu2.1 amd64 [installed]
  whiptail/bionic,now 0.52.20-1ubuntu1 amd64 [installed]
  xauth/bionic,now 1:1.0.10-1 amd64 [installed]
  xdelta3/bionic,now 3.0.11-dfsg-1ubuntu1 amd64 [installed]
  xdg-user-dirs/bionic,now 0.17-1ubuntu1 amd64 [installed]
  xfsprogs/bionic,now 4.9.0+nmu1ubuntu2 amd64 [installed]
  xkb-data/bionic,now 2.23.1-1ubuntu1 all [installed]
  xxd/bionic,now 2:8.0.1453-1ubuntu1 amd64 [installed]
  xz-utils/bionic,now 5.2.2-1.3 amd64 [installed]
  zerofree/bionic,now 1.0.4-1 amd64 [installed]
  zlib1g/bionic,now 1:1.2.11.dfsg-0ubuntu2 amd64 [installed]
  username@readVM:~$
  ```
---  
## Install Apache Web Server
###  Update Package Index
  ```bash
  username@readVM:~$ sudo apt update
  ```
### Install apache2 Package
  ```bash
  username@readVM:~$ sudo apt install apache2
  
  Preparing to unpack .../7-apache2-data_2.4.29-1ubuntu4.5_all.deb ...
  Unpacking apache2-data (2.4.29-1ubuntu4.5) ...
  Selecting previously unselected package apache2.
  Preparing to unpack .../8-apache2_2.4.29-1ubuntu4.5_amd64.deb ...
  Unpacking apache2 (2.4.29-1ubuntu4.5) ...
  Selecting previously unselected package ssl-cert.
  Preparing to unpack .../9-ssl-cert_1.0.39_all.deb ...
  Unpacking ssl-cert (1.0.39) ...
  Setting up libapr1:amd64 (1.6.3-2) ...
  Processing triggers for ufw (0.35-5) ...
  Processing triggers for ureadahead (0.100.0-20) ...
  Setting up apache2-data (2.4.29-1ubuntu4.5) ...
  Setting up ssl-cert (1.0.39) ...
  Processing triggers for libc-bin (2.27-3ubuntu1) ...
  Setting up libaprutil1:amd64 (1.6.1-2) ...
  Processing triggers for systemd (237-3ubuntu10.12) ...
  Processing triggers for man-db (2.8.3-2ubuntu0.1) ...
  Setting up liblua5.2-0:amd64 (5.2.4-1.1build1) ...
  Setting up libaprutil1-ldap:amd64 (1.6.1-2) ...
  Setting up libaprutil1-dbd-sqlite3:amd64 (1.6.1-2) ...
  Setting up apache2-utils (2.4.29-1ubuntu4.5) ...
  Setting up apache2-bin (2.4.29-1ubuntu4.5) ...S
  etting up apache2 (2.4.29-1ubuntu4.5) ...
  Enabling module mpm_event.
  Enabling module authz_core.
  Enabling module authz_host.
  Enabling module authn_core.
  Enabling module auth_basic.
  Enabling module access_compat.
  Enabling module authn_file.
  Enabling module authz_user.
  Enabling module alias.
  Enabling module dir.
  Enabling module autoindex.
  Enabling module env.
  Enabling module mime.
  Enabling module negotiation.
  Enabling module setenvif.
  Enabling module filter.
  Enabling module deflate.
  Enabling module status.
  Enabling module reqtimeout.
  Enabling conf charset.
  Enabling conf localized-error-pages.
  Enabling conf other-vhosts-access-log.
  Enabling conf security.
  Enabling conf serve-cgi-bin.
  Enabling site 000-default.
  Created symlink /etc/systemd/system/multi-user.target.wants/apache2.service → /lib/systemd/system/apache2.service.96%] [############################################################################....]
  Created symlink /etc/systemd/system/multi-user.target.wants/apache-htcacheclean.service → /lib/systemd/system/apache-htcacheclean.service.
  Processing triggers for libc-bin (2.27-3ubuntu1) ...############################################..]
  Processing triggers for ureadahead (0.100.0-20) ...
  Processing triggers for systemd (237-3ubuntu10.12) ...
  Processing triggers for ufw (0.35-5) ...
  username@readVM:~$
  ```
### Check Apache Service
  ```bash
  username@readVM:~$ sudo systemctl status apache2
  
  ● apache2.service - The Apache HTTP Server
     Loaded: loaded (/lib/systemd/system/apache2.service; enabled; vendor preset: enabled)
    Drop-In: /lib/systemd/system/apache2.service.d
             └─apache2-systemd.conf
     Active: active (running) since Mon 2019-02-11 17:03:22 UTC; 9min ago
   Main PID: 7305 (apache2)
      Tasks: 55 (limit: 4915)
     CGroup: /system.slice/apache2.service
             ├─7305 /usr/sbin/apache2 -k start
             ├─7307 /usr/sbin/apache2 -k start
             └─7308 /usr/sbin/apache2 -k start
  ```
### Check Apache Site Access
Access your site as http://readVM.myCloudSP.com {{http://example.com}}
### Setup Firewall
You can find more detailed information about the ufw firewall at https://help.ubuntu.com/lts/serverguide/firewall.html.en, https://linuxize.com/post/how-to-setup-a-firewall-with-ufw-on-ubuntu-18-04/ and https://www.digitalocean.com/community/tutorials/how-to-set-up-a-firewall-with-ufw-on-ubuntu-18-04

#### Check Status of Firewall
  ```bash
  username@readVM:~$ sudo ufw status # verbose is optional
  Status: inactive #default is inactive
  ```
#### Enable SSH Access in Firewall
  ```bash
  username@readVM:~$ sudo ufw allow ssh
  Rules updated
  Rules updated (v6)
  ```
#### Activate ufw
  ```bash
  username@readVM:~$ sudo ufw enable
  Command may disrupt existing ssh connections. Proceed with operation (y|n)? y
  Firewall is active and enabled on system startup
  ```
#### Recheck Status of Firewall
  ```bash
  username@readVM:~$ sudo ufw status
  To                         Action      From
  --                         ------      ----
  Apache                     ALLOW       Anywhere
  22/tcp                     ALLOW       Anywhere
  Apache (v6)                ALLOW       Anywhere (v6)
  22/tcp (v6)                ALLOW       Anywhere (v6)
  ```
  ---
  
## Install PostgreSQL
You can find more details here https://www.digitalocean.com/community/tutorials/how-to-install-and-use-postgresql-on-ubuntu-18-04
  ```bash
  username@readVM:~$ sudo apt install postgresql postgresql-contrib
  ```

## Configure PostgreSQL
### Run psql as User postgrs
  ```bash
  username@readVM:~$ sudo -u postgres psql
  psql (10.6 (Ubuntu 10.6-0ubuntu0.18.04.1))
  Type "help" for help.

  postgres=#
  ```
#### List Databases
  ```bash
  postgres=# \l
                                List of databases
     Name    |  Owner   | Encoding | Collate |  Ctype  |   Access privileges
  -----------+----------+----------+---------+---------+-----------------------
   postgres  | postgres | UTF8     | C.UTF-8 | C.UTF-8 |
   template0 | postgres | UTF8     | C.UTF-8 | C.UTF-8 | =c/postgres          +
             |          |          |         |         | postgres=CTc/postgres
   template1 | postgres | UTF8     | C.UTF-8 | C.UTF-8 | =c/postgres          +
             |          |          |         |         | postgres=CTc/postgres
  (3 rows)

  postgres=#
  ```
#### Set the postgres User’s Password
  ```bash
  postgres=# \password
  Enter new password: #type in password for postgres user account
  Enter it again:
  postgres=#
  ```
#### Exit psql
  ```bash
  postgres=# \q
  ```
---
## Install PHP with Extensions and phpPgAdmin
You can find mosre information here https://www.howtoforge.com/tutorial/ubuntu-postgresql-installation/
### Install PHP for Apache
  ```bash
  username@readVM:~$ sudo apt install php libapache2-mod-php
  . . .
  The following NEW packages will be installed:
  libapache2-mod-php libapache2-mod-php7.2 libsodium23 php php-common php7.2 php7.2-cli php7.2-common php7.2-json php7.2-opcache php7.2-readline
  . . .
  ```
### Install PHP Extensions and phpPgAdmin for READ
  ```bash
  username@readVM:~$ sudo apt install php-curl php-gd php-mbstring php-pgsql php-xml php-xsl php-zip phppgadmin
  The following NEW packages will be installed:
  fontconfig-config fonts-dejavu-core javascript-common libfontconfig1 libgd3 libjbig0 libjpeg-turbo8 libjpeg8 libjs-jquery libphp-adodb libtiff5 libwebp6 libxpm4 libzip4 php-curl php-gd php-mbstring
  php-pgsql php-xml php-zip php7.2-curl php7.2-gd php7.2-mbstring php7.2-pgsql php7.2-xml php7.2-zip phppgadmin
  ```
### Restart Apache
  ```bash
  username@readVM:~$ sudo systemctl restart apache2
  ```
### Create PHP Information File in Web Root
  ```bash
  username@readVM:~$ sudo nano /var/www/html/myinf.php
  
      GNU nano 2.9.3                                                                                     myinf.php

  <?php
    phpinfo();
  ?>
  ^O # ctrl O to write out
  ^X # ctrl X exit
  ```
### View PHP Information File
Access your PHP information file as http://readVM.myCloudSP.com/myinf.php. Compare https://linuxize.com/post/how-to-install-php-on-ubuntu-18-04/ for an image of the information page if you are not sure your page is correct.

### Remove PHP Information File and index.html from Web Root
  ```bash
  username@readVM:~$ cd /var/www/html
  username@readVM:~$ sudo rm myinf.php
  username@readVM:~$ sudo rm index.html
  ```

### Adjust php.ini for READ
  ```bash
  username@readVM:~$ sudo nano /etc/php/7.2/apache2/php.ini
  
        GNU nano 2.9.3                                                                                     php.ini

  ```
#### Adjust Maximum Script Execution Time
  ```bash
  ; Maximum execution time of each script, in seconds
  ; http://php.net/max-execution-time
  ; Note: This directive is hardcoded to 0 for the CLI SAPI
  max_execution_time = 300
  ```
#### Adjust Maximum Script Memory Usage
  ```bash
  ; Maximum amount of memory a script may consume (128MB)
  ; http://php.net/memory-limit
  ; READ calculates many structures for output.
  memory_limit = 1024M
  ```
#### Adjust Maximum Size of Post Data
  ```bash
  ; Maximum size of POST data that PHP will accept.
  ; Its value may be 0 to disable the limit. It is ignored if POST data reading
  ; is disabled through enable_post_data_reading.
  ; http://php.net/post-max-size
  ; READ uses this to define upload max filesize taking the smaller of post_max_size and upload_max_filesize
  post_max_size = 30000000
  ```
#### Adjust Maximum File Upload Size
  ```bash
  ; Maximum allowed size for uploaded files.
  ; http://php.net/upload-max-filesize
  ; READ uses this to define upload max filesize taking the smaller of post_max_size and upload_max_filesize
  upload_max_filesize = 30000000
  
^O # ctrl O to write out
^X # ctrl X exit
  ```

### Turn Off Extra Security for phpPgAdmin
  ```bash
  username@readVM:~$ sudo nano /etc/phppgadmin/config.inc.php
  
      GNU nano 2.9.3                                                                                     config.inc.php

        // If extra login security is true, then logins via phpPgAdmin with no
        // password or certain usernames (pgsql, postgres, root, administrator)
        // will be denied. Only set this false once you have read the FAQ and
        // understand how to change PostgreSQL's pg_hba.conf to enable
        // passworded local connections.
        $conf['extra_login_security'] = false;
  ^O # ctrl O to write out
  ^X # ctrl X exit
  ```
### Grant Access to All for phpPgAdmin
You will need to edit the phppgadmin.conf configuration file in your favorite editor, by commenting out 'Require local' and adding 'Require all granted'
  ```bash
  username@readVM:~$ sudo nano /etc/apache2/conf-available/phppgadmin.conf
  
      GNU nano 2.9.3                                                                                     phppgadmin.conf

  # Only allow connections from localhost:
  #Require local  #commented out to ease local only
  Require all granted  # added to enable access to all

  ^O # ctrl O to write out
  ^X # ctrl X exit
  ```

### Change PostgreSQL Authentication Method to md5
When you run READ and it saves data, an error failed peer authentication indicates that you need to edit the pg_hba.conf file. You will need to change the suthentication form the default (peer) to md5
  ```bash
  username@readVM:~$ sudo nano /etc/postgresql/10/main/pg_hba.conf
  
      GNU nano 2.9.3                                                                                     pg_hba.conf

# Database administrative login by Unix domain socket
local   all             postgres                                md5

  ^O # ctrl O to write out
  ^X # ctrl X exit
  ```

### Restart PostgreSQL and Apache
  ```bash
  username@readVM:~$ sudo systemctl restart postgresql
  username@readVM:~$ sudo systemctl restart apache2
  ```
### Call phpPgAdmin from Browser
Access your PHP information page as http://readVM.myCloudSP.com/phppgadmin. Login in as user postgres with the password you set above.

---

## Install Git
  ```bash
  username@readVM:~$ sudo apt install git
  ```
---
## Install Unzip
  ```bash
  username@readVM:~$ sudo apt install unzip
  ```
---
## Install READ Software
### Clone READ Repository
  ```bash
  username@readVM:~$ cd /var/www/html
  username@readVM:~$ sudo git clone https://github.com/readsoftware/read
  ```
### Look Up Apache User Group
READ relies on the ability of apache2 having appropriate access to various directories in READ's configuration and code. You will need to know the name of the APACHE_RUN_GROUP in order to adjust the owner of the READE system files.
  ```bash
  username@readVM:~$ less /etc/apache2/envvars
  
  # Since there is no sane way to get the parsed apache2 config in scripts, some
  # settings are defined via environment variables and then used in apache2ctl,
  # /etc/init.d/apache2, /etc/logrotate.d/apache2, etc.
  export APACHE_RUN_USER=www-data
  export APACHE_RUN_GROUP=www-data  
  ```
### Adjust Owner and Group for Web Directory
  ```bash
  username@readVM:~$ sudo chown -R username:www-data /var/www/html/read
  ```
### Adjust Permissions for Web Directory
  ```bash
  username@readVM:~$ sudo chmod -R 774 /var/www/html/read
  ```
---
## Install READ Dependencies
### Clone the Dependencies Repository
  ```bash
  username@readVM:~$ cd /var/www/html
  username@readVM:~$ sudo git clone https://github.com/readsoftware/dependencies
  ```
### Unzip jQWidgets and Move to Document Root
  ```bash
  username@readVM:~$ cd /var/www/html/dependencies/jqwidget
  username@readVM:~$ sudo unzip jqwidget.zip
  username@readVM:~$ sudo mv jqwidget /var/www/html
  ```
### Move jQuery to Document Root
  ```bash
  username@readVM:~$ cd /var/www/html/dependencies
  username@readVM:~$ sudo mv jquery /var/www/html
  username@readVM:~$ cd /var/www/html
  username@readVM:~$ sudo rm -fr dependencies/
  ```
---
## Set Up READ Upload Image Storage
### Create Directory for Uploaded Images
  ```bash
  username@readVM:~$ sudo mkdir /var/www/html/images
  ```
### Create Directory for Clipped Segment Image Cache
  ```bash
  username@readVM:~$ sudo mkdir /var/www/html/images/segment_cache
  ```
### Adjust Owner and Group
  ```bash
  username@readVM:~$ sudo chown -R username:www-data /var/www/html/images
  ```
### Adjust Permissions
  ```bash
  username@readVM:~$ sudo chmod -R 774 /var/www/html/images
  ```

---
## Setup READ File Store

READ has a utility service manageDBUsingSQL.php for creating, taking snapshots of, and restoring READ databases. The file‐store directory should not be under the document root and should be accessible by the admin (username) and from apache run group with a mode of rwx (read,wrtie,execute) 770 . Any .sql files for READ databases should have access of 660. {{ this is unclear, clean up }}
### Create Snapshots Directory
  ```bash
  username@readVM:~$ sudo mkdir /var/www/readfilestore
  ```
### Copy startup.sql from read/dev
The file startup.sql is template database‐setup file for a READ database that has the minimal information needed for READ to launch and to allow a user to login to default user accounts and adjust them.
  ```bash
  username@readVM:~$ sudo cp /var/www/html/read/dev/startup.sql /var/www/readfilestore
  ```
### Adjust Owner and Group
  ```bash
  username@readVM:~$ sudo chown -R username:www-data /var/www/readfilestore
  ```
### Adjust Permissions
  ```bash
  username@readVM:~$ sudo chmod 770 /var/www/readfilestore
  username@readVM:~$ sudo chmod 660 /var/www/readfilestore/*
  ```
  
---
## Configure READ
READ needs a configuration file to run. In the base directory of the READ install, there is a file config-sample.php that is a template for creating a config.php configuration file.
### Copy config-sample.php to config.php
  ```bash
  username@readVM:~$ sudo cp /var/www/html/read/config-sample.php /var/www/html/read/config.php
  ```
### Edit config.php
  ```bash
  username@readVM:~$ sudo chown username:www-data /var/www/html/read/config.php
  ### adjust config.php for server install
  ```bash
  username@readVM:~$ nano /var/www/html/read/config.php
  
      GNU nano 2.9.3                                                                                     config.php
      
 ```
#### Set Project Title
Find the define statement for "PROJECT_TITLE" and change the value string to the name of your project.
  ```bash
  if(!defined("PROJECT_TITLE")) define("PROJECT_TITLE","My Read Test Project");
  ```
#### Set Root Directory
Find the define statement for "READ_DIR" and change the value string to "/dir_beneath_documentroot_where_READ_was_installed". Since these instructions cloned the READ repository into /var/www/html/read (with /var/www/html being the document root for the website) we use "/read".
  ```bash
  if(!defined("READ_DIR")) define("READ_DIR","/read");
  ```
#### Set Database Name
Find the define statement for "DBNAME" and change the value string to the name of your default database. We will create a "testdb" database below, so we will set this to testdb. This is used to automatically supply the database name in service calls to READ. Most service calls will use a parameter "db=nameoftargetdb" in the URL to specifiy the database to use. When this is absent, READ will use the default.
  ```bash
  if(!defined("DBNAME")) define("DBNAME","testdb");
  ```
#### Set Superuser Name
Find the define statement for "USERNAME" and change the value string to the name of the super user (postgres) name you setup while installing PostgreSQL.
  ```bash
  if(!defined("USERNAME")) define("USERNAME","postgres");
  ```
#### Set Superuser Password
Find the define statement for "PASSWORD" and change the value string to the password you set while installing PostgreSQL
  ```bash
  if(!defined("PASSWORD")) define("PASSWORD","mySUpassword");
  ```
#### Set READ File Store Path
Find the define statement for "READ_FILE_STORE", remove // from beginning of line and change the value string to the path you created above
  ```bash
  if(!defined("READ_FILE_STORE")) define("READ_FILE_STORE",'/var/www/readfilestore');
 
  ^O # ctrl O to write out
  ^X # ctrl X exit
  ```

---
## Create New Database Using Local .sql File
* log into phpdbadmin
* create a database with name *mynewdbname*, encoding utf8
* select the new database
* select the SQL? tab
* select the browse button and navigate to the *localReadDB.sql*
* select the execute button and wait for completion
* view the results and ensure there are no errors

---
## Create New Database Using *manageDBUsingSQL.php* Service
The manageDBUsingSQL service allows you to create, take snapshots of, and restore READ databases using a URL call like http://readVM.myCloudSP.com/read/dev/manageDBUsingSQL.php?dbname=startdb&cmd=create&sqlfilename=startup.sql. This service takes 3 parameters:
 * dbname – the name of the database to manage.
 * cmd – the command to perform, where: *create* will use sqlfilename to find the template file in the READ filestore for creating the new database dbname; *snapshot* will prepend "snapshot" to the sqlfilename to create a snapshot of the database dbname storing it in the READ file store and downloading it to the local browser; *restore* will use sqlfilename to find the template file in the READ filestore for restoring the existing dbname database.
 * sqlfilename – filename of the file to read or create in a given command. It is assumed that this file exists in the READ filestore. An error will occur if it does not exist.
