apt-get install build-essential libc6 libpcre3 libpcre3-dev libpcrecpp0 libssl0.9.8 libssl-dev zlib1g zlib1g-dev lsb-base
cd /tmp/
mkdir custom_nginx
cd custom_nginx
wget http://nginx.org/download/nginx-1.4.6.tar.gz
wget http://labs.frickle.com/files/ngx_cache_purge-2.1.tar.gz
tar -xvf nginx-1.4.6.tar.gz
tar -xvf ngx_cache_purge-2.1.tar.gz
cd nginx-1.4.6

./configure \
  --conf-path=/etc/nginx/nginx.conf \
  --error-log-path=/var/log/nginx/error.log \
  --pid-path=/var/run/nginx.pid \
  --lock-path=/var/lock/nginx.lock \
  --http-log-path=/var/log/nginx/access.log \
  --with-http_dav_module \
  --http-client-body-temp-path=/var/lib/nginx/body \
  --http-proxy-temp-path=/var/lib/nginx/proxy \
  --with-http_stub_status_module \
  --http-fastcgi-temp-path=/var/lib/nginx/fastcgi \
  --with-debug \
  --add-module=/tmp/custom_nginx/ngx_cache_purge-2.1

./make && ./make install
