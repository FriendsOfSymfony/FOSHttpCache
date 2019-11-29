#!/bin/sh

sudo wget https://openlitespeed.org/packages/openlitespeed-1.6.4.tgz
tar -zxvf openlitespeed-*.tgz
cd openlitespeed
sudo ./install.sh

# Remove examples
sudo rm -r /usr/local/lsws/conf/vhosts/Example
sudo rm -r /usr/local/lsws/Example

# Copy config
cd $TRAVIS_BUILD_DIR/tests/Functional/Fixtures/openlitespeed
sudo cp ./httpd_config.conf /usr/local/lsws/conf/httpd_config.conf
sudo sed -i "s#%TRAVIS_BUILD_DIR%#$TRAVIS_BUILD_DIR#g" /usr/local/lsws/conf/httpd_config.conf
sudo sed -i "s#%USER%#$USER#g" /usr/local/lsws/conf/httpd_config.conf
sudo sed -i "s#%GROUP%#travis#g" /usr/local/lsws/conf/httpd_config.conf
sudo mkdir -p /usr/local/lsws/conf/vhosts/foshttpcachetest
sudo cp ./vhconf.conf /usr/local/lsws/conf/vhosts/foshttpcachetest/vhconf.conf

# Make sure it's all owned by travis
sudo chown -R travis /usr/local/lsws
