#!/bin/sh

wget https://openlitespeed.org/packages/openlitespeed-1.4.42.tgz
tar -zxvf openlitespeed-1.4.42.tgz
cd openlitespeed

# Ensure correct owners for travis
sed -i s#SERVERROOT=/usr/local/lsws#SERVERROOT=/tmp/openlitespeed#g ols.conf
sed -i s/OPENLSWS_USER=nobody/OPENLSWS_USER=travis/g ols.conf
sed -i s/OPENLSWS_GROUP=nobody/OPENLSWS_GROUP=travis/g ols.conf

sudo ./install.sh

# Remove examples
sudo rm -r /tmp/openlitespeed/conf/vhosts/Example
sudo rm -r /tmp/openlitespeed/Example

# Copy config
cd $TRAVIS_BUILD_DIR/tests/ci/openlitespeed
sudo cp ./httpd_config.conf /tmp/openlitespeed/conf/httpd_config.conf
sudo mkdir /tmp/openlitespeed/conf/vhosts/foshttpcachetest
sudo cp ./vhconf.conf /tmp/openlitespeed/conf/vhosts/foshttpcachetest/vhconf.conf

sudo ln -s /tmp/openlitespeed/bin/lswsctrl /usr/sbin/lswsctrl
