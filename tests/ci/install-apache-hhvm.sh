#!/bin/sh
# https://github.com/facebook/hhvm/wiki/fastcgi

sudo apt-get install apache2 libapache2-mod-fastcgi
sudo a2enmod rewrite actions fastcgi alias

# Configure apache virtual hosts
sudo cp -f tests/ci/travis-ci-apache-hhvm /etc/apache2/sites-available/default
sudo sed -e "s?%TRAVIS_BUILD_DIR%?$(pwd)?g" --in-place /etc/apache2/sites-available/default
sudo service apache2 restart

cat /etc/hhvm/php.ini
echo "hhvm.log.always_log_unhandled_exceptions = true" >> /etc/hhvm/php.ini

# Run HHVM
hhvm -m daemon -vServer.Type=fastcgi -vServer.Port=9000 -vServer.FixPathInfo=true

sleep 5
curl -v http://localhost:8080/cache.php
curl -v http://localhost:8080/symfony.php/cache
echo hhvm log
sudo cat /var/log/hhvm/error.log
echo apache log
sudo cat /var/log/apache2/error.log
sudo cat /var/log/apache2/other_vhosts_access.log
