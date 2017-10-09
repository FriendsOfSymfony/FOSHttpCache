ARG PHP_VERSION
FROM php:${PHP_VERSION}-fpm-alpine

ENV VARNISH_VERSION=5.0.0
ENV VARNISH_MODULES_VERSION=0.12.1
ENV NGINX_VERSION=1.9.15
ENV NGINX_CACHE_PURGE_VERSION=2.3
ENV COMPOSER_ALLOW_SUPERUSER=1

ADD http://git.alpinelinux.org/cgit/aports/plain/main/varnish/musl-mode_t.patch /
COPY install-nginx.sh /

# See https://www.varnish-cache.org/docs/trunk/installation/install.html
RUN apk add --no-cache pcre \
    && apk add --no-cache --virtual .build-deps autoconf automake bash libtool libedit-dev linux-headers make ncurses-dev openssl pcre-dev python py-docutils zlib-dev \
    && apk add --no-cache g++ git openssl \
    && wget https://varnish-cache.org/_downloads/varnish-$VARNISH_VERSION.tgz \
    && tar xf varnish-$VARNISH_VERSION.tgz \
    && cd varnish-$VARNISH_VERSION \
    && ./autogen.sh \
    && ./configure --with-rst2man=/bin/true \
    && patch -p1 < /musl-mode_t.patch \
    && make \
    && make install \
    && cd .. \
    && rm -r varnish-$VARNISH_VERSION.tgz varnish-$VARNISH_VERSION /musl-mode_t.patch \

    # Varnish modules
    && wget -O varnish-modules.tar.gz https://github.com/varnish/varnish-modules/archive/${VARNISH_MODULES_VERSION}.tar.gz \
    && tar xf varnish-modules.tar.gz \
    && cd varnish-modules-${VARNISH_MODULES_VERSION} \
    && PKG_CONFIG_PATH=/usr/local/lib/pkgconfig ./bootstrap \
    && PKG_CONFIG_PATH=/usr/local/lib/pkgconfig ./configure \
    && make \
    && make install \
    && cd .. \
    && rm -r varnish-modules-${VARNISH_MODULES_VERSION} varnish-modules.tar.gz \

    # Nginx
    && /install-nginx.sh \
    && apk del .build-deps

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

WORKDIR /app
