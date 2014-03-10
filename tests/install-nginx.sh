sudo apt-get install build-essential libc6 libpcre3 libpcre3-dev libpcrecpp0 libssl0.9.8 libssl-dev zlib1g zlib1g-dev lsb-base
cd /tmp/
mkdir custom_nginx
cd custom_nginx
wget http://nginx.org/download/nginx-1.4.6.tar.gz
wget http://labs.frickle.com/files/ngx_cache_purge-2.1.tar.gz
tar -xvf nginx-1.4.6.tar.gz
tar -xvf ngx_cache_purge-2.1.tar.gz
cd nginx-1.4.6

./configure \
  --prefix=/tmp/
  --sbin=/tmp/custom_nginx/nginx-1.4.6/objs/nginx
  --conf-path=/tmp/
  --error-log-path=/tmp/foshttpcache-error.log \
  --http-log-path=/tmp/foshttpcache-access.log \
  --pid-path=/tmp/foshttpcache-nginx.pid \
  --lock-path=/tmp/foshttpcache-nginx.lock \
  --http-client-body-temp-path=/tmp/foshttpcache-body \
  --http-proxy-temp-path=/tmp/foshttpcache-proxy \
  --with-http_stub_status_module \
  --http-fastcgi-temp-path=/tmp/foshttpcache-fastcgi \
  --with-debug \
  --add-module=/tmp/custom_nginx/ngx_cache_purge-2.1

/usr/bin/make
