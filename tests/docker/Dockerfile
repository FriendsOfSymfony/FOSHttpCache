# PHP Version number as set by offical php docker image tags: https://hub.docker.com/r/library/php/tags/
# Apache is setup as http server, varnish + nginx is installed for use as reverse proxies
ARG PHP_VERSION=7.1
FROM php:${PHP_VERSION}-apache

ENV VARNISH_VERSION=5.1 \
    VARNISH_MODULES_VERSION=0.12.1 \
    NGINX_VERSION=1.9.15 \
    NGINX_CACHE_PURGE_VERSION=2.3 \
    VARNISH_MALLOC_SIZE="256M" \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBIAN_FRONTEND=noninteractive

# Use official packages from Varnish and build with varnish-modules mainly for xkey
# see: https://github.com/varnish/varnish-modules/tree/master/docs
RUN set -xe \
        && buildDeps=" \
            make \
            automake \
            autotools-dev \
            libedit-dev \
            libjemalloc-dev \
            libncurses-dev \
            libpcre3-dev \
            libtool \
            libssl-dev \
            zlib1g-dev \
            pkg-config \
            python-docutils \
            python-sphinx \
            enchant \
            varnish-dev \
        " \
    # Update apt and get dependencies
        && apt-get update -q -y \
        && apt-get install -q -y --no-install-recommends ca-certificates curl bc net-tools unzip git libc6 libpcre3 zlib1g lsb-base debian-archive-keyring gnupg \
        \
    # Get official Varnish package
        && url_version=$(echo $VARNISH_VERSION | tr -d '.') \
        && curl -s -L https://packagecloud.io/install/repositories/varnishcache/varnish${url_version}/script.deb.sh | bash \
        && apt-get install -q -y --no-install-recommends varnish $buildDeps \
        \
    # Install varnish modules
        && curl -A "Docker" -o /tmp/varnish-modules.tar.gz -D - -L -s https://download.varnish-software.com/varnish-modules/varnish-modules-${VARNISH_MODULES_VERSION}.tar.gz \
        && tar zxpf /tmp/varnish-modules.tar.gz -C /tmp/ \
        && cd /tmp/varnish-modules-${VARNISH_MODULES_VERSION} \
        && ./configure \
        && make \
        # && make check \
        && make install \
        && rm -f /tmp/varnish-modules.tar.gz && rm -Rf /tmp/varnish-modules \
        \
    # Nginx
        && mkdir -p /tmp/custom_nginx \
        && cd /tmp/custom_nginx \
        && curl -L -O https://nginx.org/download/nginx-${NGINX_VERSION}.tar.gz \
        && curl -L -O https://github.com/FRiCKLE/ngx_cache_purge/archive/${NGINX_CACHE_PURGE_VERSION}.tar.gz \
        && tar -xf nginx-${NGINX_VERSION}.tar.gz && rm nginx-${NGINX_VERSION}.tar.gz \
        && tar -xf ${NGINX_CACHE_PURGE_VERSION}.tar.gz && rm ${NGINX_CACHE_PURGE_VERSION}.tar.gz \
        && cd nginx-${NGINX_VERSION} \
        && mkdir build \
        && ./configure \
            --with-debug \
            --prefix=/tmp/custom_nginx/build/ \
            --add-module=/tmp/custom_nginx/ngx_cache_purge-${NGINX_CACHE_PURGE_VERSION} \
        && make \
        && make install \
        && ln -s /tmp/custom_nginx/build/sbin/nginx /usr/sbin/nginx \
        \
    # Cleanup apt cache and remove build packages
        && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false $buildDeps \
        && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

WORKDIR /app