#!/bin/bash

set -e
echo "### Installing Legacy Varnish $VARNISH_VERSION ###"
curl -A "FOS Github" -o /tmp/libjemalloc1_3.6.0-11_amd64.deb -D - -L -s http://ftp.osuosl.org/pub/ubuntu/pool/universe/j/jemalloc/libjemalloc1_3.6.0-11_amd64.deb
sudo dpkg -i /tmp/libjemalloc1_3.6.0-11_amd64.deb
sudo apt-get update
sudo apt-get install debian-archive-keyring curl gnupg apt-transport-https
VARNISH_VERSION=$VARNISH_VERSION bash -c 'curl -s -L https://packagecloud.io/varnishcache/varnish${VARNISH_VERSION//./}/gpgkey' | sudo apt-key add -
# hardcode to use ubuntu trusty. that would be 14.04, but it is no longer supported on github actions.
# the varnish 3 repository on packagecloud.io does not provide packages for newer ubuntu distributions
# the trusty packages seem to work. YOLO
VARNISH_VERSION=$VARNISH_VERSION bash -c 'echo deb https://packagecloud.io/varnishcache/varnish${VARNISH_VERSION//./}/ubuntu/ trusty main' | sudo tee /etc/apt/sources.list.d/varnishcache_varnish.list
cat /etc/apt/sources.list.d/varnishcache_varnish.list
sudo tee /etc/apt/preferences.d/varnishcache > /dev/null <<-EOF
Package: varnish varnish-* hitch
Pin: release o=packagecloud.io/varnishcache/*
Pin-Priority: 1000
EOF
sudo apt-get update

sudo apt-get install varnish
if [ "$VARNISH_MODULES_VERSION" != "" ]; then sh "${GITHUB_WORKSPACE}/tests/install-varnish-modules.sh"; fi
