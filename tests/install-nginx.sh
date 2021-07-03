#!/bin/sh

set -e

export NGINX_VERSION=1.21.1
export NGX_CACHE_PURGE_VERSION=2.3

sudo apt-get install build-essential libc6 libpcre3 libpcre3-dev libpcrecpp0v5 libssl-dev zlib1g zlib1g-dev lsb-base
cd /tmp/
mkdir custom_nginx
cd custom_nginx
mkdir build
wget --no-verbose http://nginx.org/download/nginx-${NGINX_VERSION}.tar.gz
wget --no-verbose http://labs.frickle.com/files/ngx_cache_purge-${NGX_CACHE_PURGE_VERSION}.tar.gz
tar -xf nginx-${NGINX_VERSION}.tar.gz
tar -xf ngx_cache_purge-${NGX_CACHE_PURGE_VERSION}.tar.gz
cd nginx-${NGINX_VERSION}

./configure \
    --with-debug \
    --prefix=/tmp/custom_nginx/build/ \
    --add-module=/tmp/custom_nginx/ngx_cache_purge-${NGX_CACHE_PURGE_VERSION}

/usr/bin/make
/usr/bin/make install

sudo ln -s /tmp/custom_nginx/build/sbin/nginx /usr/sbin/nginx
