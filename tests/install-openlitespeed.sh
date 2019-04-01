#!/bin/sh

#sudo wget -O - http://rpms.litespeedtech.com/debian/enable_lst_debain_repo.sh | sudo bash
#sudo apt-get -y install openlitespeed

sudo wget https://openlitespeed.org/preuse/openlitespeed-1.4.46.tgz
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
sudo cp ./htpasswd /usr/local/lsws/conf/vhosts/foshttpcachetest/htpasswd

# Make sure it's all owned by travis
sudo chown -R travis /usr/local/lsws
