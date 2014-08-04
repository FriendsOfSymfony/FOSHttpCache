#!/bin/sh

sudo apt-get install build-essential libc6 libpcre3 libpcre3-dev libpcrecpp0 libssl-dev zlib1g zlib1g-dev lsb-base
cd /tmp/
mkdir custom_nginx
cd custom_nginx
mkdir build
wget http://nginx.org/download/nginx-1.4.6.tar.gz
wget http://labs.frickle.com/files/ngx_cache_purge-2.1.tar.gz
tar -xvf nginx-1.4.6.tar.gz
tar -xvf ngx_cache_purge-2.1.tar.gz
cd nginx-1.4.6

./configure \
    --with-debug \
    --prefix=/tmp/custom_nginx/build/ \
    --add-module=/tmp/custom_nginx/ngx_cache_purge-2.1

/usr/bin/make
/usr/bin/make install

sudo ln -s /tmp/custom_nginx/build/sbin/nginx /usr/sbin/nginx
