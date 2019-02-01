#!/bin/sh

sudo apt-get -y install build-essential
sudo apt-get -y install rcs libpcre3-dev libexpat1-dev libssl-dev libgeoip-dev libudns-dev zlib1g-dev libxml2 libxml2-dev libpng-dev openssl
sudo apt-get -y install wget

wget https://openlitespeed.org/packages/openlitespeed-1.4.43.src.tgz
tar -zxvf openlitespeed-1.4.43.src.tgz
cd ./openlitespeed-1.4.43
./configure --with-user travis --with-group travis
make && make install

# Remove examples
sudo rm -r /usr/local/lsws/conf/vhosts/Example
sudo rm -r /usr/local/lsws/Example

# Copy config
cd $TRAVIS_BUILD_DIR/tests/ci/openlitespeed
sudo cp ./httpd_config.conf /usr/local/lsws/conf/httpd_config.conf
sudo mkdir /usr/local/lsws/conf/vhosts/foshttpcachetest
sudo cp ./vhconf.conf /usr/local/lsws/conf/vhosts/foshttpcachetest/vhconf.conf
