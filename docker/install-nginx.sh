#!/bin/sh

mkdir -p /tmp/custom_nginx
cd /tmp/custom_nginx
wget https://nginx.org/download/nginx-${NGINX_VERSION}.tar.gz
wget https://github.com/FRiCKLE/ngx_cache_purge/archive/${NGINX_CACHE_PURGE_VERSION}.tar.gz
tar -xf nginx-${NGINX_VERSION}.tar.gz && rm nginx-${NGINX_VERSION}.tar.gz
tar -xf ${NGINX_CACHE_PURGE_VERSION}.tar.gz && rm ${NGINX_CACHE_PURGE_VERSION}.tar.gz
cd nginx-${NGINX_VERSION}

mkdir build

./configure \
    --with-debug \
    --prefix=/tmp/custom_nginx/build/ \
    --add-module=/tmp/custom_nginx/ngx_cache_purge-${NGINX_CACHE_PURGE_VERSION}

make
make install

ln -s /tmp/custom_nginx/build/sbin/nginx /usr/sbin/nginx
