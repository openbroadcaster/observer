#!/usr/bin/bash

# Copyright 2012-2021 OpenBroadcaster Inc.

# This file is part of OpenBroadcaster Server.

# OpenBroadcaster Server is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# OpenBroadcaster Server is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU Affero General Public License for more details.

# You should have received a copy of the GNU Affero General Public License
# along with OpenBroadcaster Server. If not, see <http://www.gnu.org/licenses/>.

function get_password() {
 PASSWORD=$(cat /dev/urandom | tr -dc 'a-zA-Z0-9' | fold -w 8 | head -n 1)
}

if [ "$(id -u)" != "0" ]; then
	echo ""
	echo "!!! this script must be run as root.  Please su to root before running. !!!"
	echo ""
	exit 0
fi

get_password

OBCONF_PASS=$PASSWORD
OBCONF_URL="http://localhost"
OBCONF_EMAIL="admin@localhost.localhost"

MYSQL_PASS=""
MYSQL_ROOTPASS=""	# leave blank to be prompted for password during installation

# if using the default FQDN value leave this as "_".
FQDN="_"
STMP_SERVER=""

OBSERVER_BRANCH="main"

# set the user being used for things like the home folder.
USER="obsuser"

# based on code from: https://brianchildress.co/named-parameters-in-bash/

branch=${branch:-master}
os=${os:-ubuntu}

if [ $branch == "master" ]; then
	# Handle all the repos using main instead of master.
	branch="main"
fi
while [ $# -gt 0 ]; do

   if [[ $1 == *"--"* ]]; then
        param="${1/--/}"
        declare $param="$2"
   fi

  shift
done

OBSERVER_BRANCH=$branch

# Check what OS is being used.

if [[ $os == "debian" ]]; then
	apt install sudo
	sudo apt install software-properties-common ca-certificates lsb-release apt-transport-https
	sudo sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
	wget -qO - https://packages.sury.org/php/apt.gpg | sudo apt-key add -
	PKGS="ufw tree nginx apg mariadb-server php7.4-fpm php7.4-mysql php7.4-mysql php7.4-mbstring php7.4-xml php7.4-gd php7.4-curl php7.4-imagick imagemagick ffmpeg vorbis-tools festival libavcodec-extra libavfilter-extra exim4 cifs-utils"
elif [[ $os == "ubuntu" ]]; then
	PKGS="ufw tree nginx apg mariadb-server php7.4-fpm php7.4-mysql php7.4-mysql php7.4-mbstring php7.4-xml php7.4-gd php7.4-curl php7.4-imagick imagemagick ffmpeg vorbis-tools festival libavcodec-extra libavfilter-extra exim4 cifs-utils"
else
	log_message "Sorry but $os isn't supported! Please select 'ubuntu', or 'debian'."
	log_message "Exiting..."
	exit 1
fi

echo ""
echo "*** Installing server dependencies ***"
echo ""
apt-get install -yy $PKGS

echo ""
echo "*** Setting up Nginx config, and UFW rules ***"
echo ""
ufw allow 'Nginx HTTP'
touch /etc/nginx/sites-available/observer.conf
cat > /etc/nginx/sites-available/observer.conf <<EOF
server {
	listen 80;
	root /var/www/observer/;
	# Add index.php to the list if you are using PHP
	index index.html index.htm index.nginx-debian.html index.php;
	server_name $FQDN;
	# pass PHP scripts to FastCGI server
	#
	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		# With php-fpm (or other unix sockets):
		fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
	#	# With php-cgi (or other tcp sockets):
	#	fastcgi_pass 127.0.0.1:9000;
	}
	location ~ /\.ht {
		deny all;
	}
}
EOF

ln -s /etc/nginx/sites-available/observer.conf /etc/nginx/sites-enabled/
unlink /etc/nginx/sites-enabled/default > /dev/null 2>&1

echo ""
echo "*** Loading new Nginx config ***"
echo ""

systemctl reload nginx

echo ""
echo "*** Downloading OBServer ***"
echo ""
if [ -e /tmp/observer ]; then
	rm -R /tmp/observer
fi
if [ -e /var/www/observer ]; then
	rm -R /var/www/observer
fi

cd /tmp && sudo -u $USER git clone https://github.com/openbroadcaster/observer.git -b $OBSERVER_BRANCH /tmp/observer

mv /tmp/observer /var/www/observer

if [ ! -e /var/www/observer ]; then
	echo ""
	echo "!!! Failed while installing observer. !!!"
	echo ""
	exit 1
fi

echo ""
echo "*** Setting up OB cron task. ***"
echo ""

echo "*/5 * * * * $USER /usr/bin/php /var/www/observer/cron.php" > /etc/cron.d/observer

OBCONF_SALT=$(apg -m 16 -x 20 -a 1 -n 1 -M NCL)

updates_password=$(php -r "echo password_hash('updates',PASSWORD_DEFAULT).\"\n\";")

cat > /var/www/observer/config.php <<EOF
<?php
const OB_HASH_SALT = '$OBCONF_SALT';
const OB_DB_USER = '$USER';
const OB_DB_PASS = '$MYSQL_PASS';
const OB_DB_HOST = 'localhost';
const OB_DB_NAME = 'obdb';
const OB_MEDIA = '/home/media';
const OB_MEDIA_UPLOADS = '/home/media/uploads';
const OB_MEDIA_ARCHIVE = '/home/media/archive';
const OB_CACHE = '/home/media/cache';
const OB_SITE = '$OBCONF_URL';
const OB_EMAIL_REPLY = '$OBCONF_EMAIL';
const OB_EMAIL_FROM = 'OpenBroadcaster';
const OB_UPDATES_USER = 'updates';
const OB_UPDATES_PW = '$updates_password';
?>
EOF

chown $USER:$USER /var/www/observer/config.php

echo ""
echo "*** Setting up mysql ***"
echo ""

sudo mysqladmin create obdb -p$MYSQL_ROOTPASS
mysql -e "CREATE USER obsuser@localhost IDENTIFIED BY '$MYSQL_PASS'; GRANT ALL PRIVILEGES ON obdb. * TO 'obsuser'@'localhost'; FLUSH PRIVILEGES;" -p$MYSQL_ROOTPASS
sudo mysql -p$MYSQL_PASS obdb < /var/www/observer/db/dbclean.sql

echo ""
echo "*** Setting up ob manager and media directory ***"
echo ""

mkdir -p /home/media/{archive,uploads,cache}
mkdir -p /var/www/observer/assets/uploads
chown -R www-data /home/media /var/www/observer/assets
find /home/media/ -type d -exec chmod 0775 {} \;
find /home/media/ -type f -exec chmod 0664 {} \;

echo ""
echo "OBServer software is installed"
version=$(cat /var/www/observer/VERSION)
echo "Version: $version"
echo "Branch: $branch"
echo "Your mysql login : $USER/$MYSQL_PASS"
echo "Your admin password: $OBCONF_PASS"
echo "Your updates login: updates/updates"
echo " Log into OpenBroadcaster with user credentials that were set in config.php OB_UPDATES_USER and OB_UPDATES_PW and then run https://YOUR_IP/updates to verify your installation and run any required updates."
echo ""
echo "Thank you for choosing OpenBroadcaster!"
