sudo apt-get install nginx build-essential libc6 libpcre3 libpcre3-dev
libpcrecpp0 libssl0.9.8 libssl-dev zlib1g zlib1g-dev lsb-base
cd /tmp/
mkdir custom_nginx
cd custom_nginx
wget http://nginx.org/download/nginx-1.4.6.tar.gz
wget http://labs.frickle.com/files/ngx_cache_purge-2.1.tar.gz
tar -xvf nginx-1.4.6.tar.gz
tar -xvf ngx_cache_purge-2.1.tar.gz
cd nginx-1.4.6

./configure \
	  --with-debug \
	    --add-module=/tmp/custom_nginx/ngx_cache_purge-2.1

/usr/bin/make
sudo /usr/bin/make install
sudo chmod -R 777 /usr/local/nginx