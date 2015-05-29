#!/bin/sh
# https://github.com/facebook/hhvm/wiki/fastcgi

sudo apt-get install apache2 libapache2-mod-fastcgi
sudo a2enmod rewrite actions fastcgi alias

# Configure apache virtual hosts
sudo cp -f tests/ci/travis-ci-apache-hhvm /etc/apache2/sites-available/default
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default
sudo service apache2 restart

# Run HHVM
hhvm -m daemon -vServer.Type=fastcgi -vServer.Port=9000 -vServer.FixPathInfo=true

curl -v http://localhost:8080/symfony.php/cache
sleep 5
curl -v http://localhost:8080/symfony.php/cache
cat /var/log/hhvm/error.lo
cat /var/log/apache2/error.log
cat /var/log/apache2/other_vhosts_access.log
ls /var/log/apache2
