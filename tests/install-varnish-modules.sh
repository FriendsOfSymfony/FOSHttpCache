#!/bin/sh

sudo apt-get install -qq varnish-dev

curl -A "FOS Travis" -o /tmp/varnish-modules.tar.gz -D - -L -s https://download.varnish-software.com/varnish-modules/varnish-modules-${VARNISH_MODULES_VERSION}.tar.gz
tar zxpf /tmp/varnish-modules.tar.gz -C /tmp/
cd /tmp/varnish-modules-${VARNISH_MODULES_VERSION}
./configure
make
# make check
sudo make install
rm -f /tmp/varnish-modules.tar.gz && rm -Rf /tmp/varnish-modules
