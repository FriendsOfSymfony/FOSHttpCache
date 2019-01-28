#!/bin/sh

wget https://openlitespeed.org/packages/openlitespeed-1.4.42.tgz
tar -zxvf openlitespeed-1.4.42.tgz
cd openlitespeed
sudo ./install.sh

# Remove examples
sudo rm -r /usr/local/lsws/conf/vhosts/Example
sudo rm -r /usr/local/lsws/Example

# Copy config
cd $TRAVIS_BUILD_DIR/tests/ci/openlitespeed
sudo cp ./httpd_config.conf /usr/local/lsws/conf/httpd_config.conf
sudo mkdir /usr/local/lsws/conf/vhosts/foshttpcachetest
sudo cp ./vhconf.conf /usr/local/lsws/conf/vhosts/foshttpcachetest/vhconf.conf

