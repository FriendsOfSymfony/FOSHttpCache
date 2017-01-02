FROM php:7.0-fpm-alpine

ENV VARNISH_VERSION 5.0.0
ENV VARNISH_MODULES_VERSION "issue/7"

# See http://git.alpinelinux.org/cgit/aports/tree/main/varnish/musl-mode_t.patch
ADD mode_t.patch /

# See https://www.varnish-cache.org/docs/trunk/installation/install.html
RUN apk add --no-cache pcre \
    && apk add --no-cache --virtual .build-deps autoconf automake bash g++ libtool libedit-dev linux-headers make ncurses-dev openssl pcre-dev python py-docutils \
    && wget http://repo.varnish-cache.org/source/varnish-$VARNISH_VERSION.tar.gz \
    && tar xf varnish-$VARNISH_VERSION.tar.gz \
    && cd varnish-$VARNISH_VERSION \
    && ./autogen.sh \
    && ./configure --with-rst2man=/bin/true \
    && patch -p1 < /mode_t.patch \
    && make \
    && make install \
    && cd .. \
    && rm -r varnish-$VARNISH_VERSION.tar.gz varnish-$VARNISH_VERSION /mode_t.patch \
    && wget -O varnish-modules.tar.gz https://github.com/Dridi/varnish-modules/archive/${VARNISH_MODULES_VERSION}.tar.gz \
    && tar xf varnish-modules.tar.gz \
    && cd varnish-modules-issue-7 \
    && PKG_CONFIG_PATH=/usr/local/lib/pkgconfig ./bootstrap \
    && PKG_CONFIG_PATH=/usr/local/lib/pkgconfig ./configure \
    && make \
    && make install \
    && cd .. \
    # TODO: remove hard-coded branch name when https://github.com/varnish/varnish-modules/pull/38 has been merged
    && rm -r varnish-modules-issue-7 varnish-modules.tar.gz \
    && apk del .build-deps
