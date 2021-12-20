#!/bin/bash

set -e
# Install Varnish
echo "### Installing Varnish $VARNISH_VERSION ###"
VARNISH_VERSION=60lts bash -c 'curl -L https://packagecloud.io/varnishcache/varnish${VARNISH_VERSION//./}/gpgkey | sudo apt-key add -'
VARNISH_VERSION=60lts bash -c 'curl -L "https://packagecloud.io/install/repositories/varnishcache/varnish${VARNISH_VERSION//./}/config_file.list?os=ubuntu&dist=focal&source=script" | sudo tee -a /etc/apt/sources.list'
cat /etc/apt/sources.list
sudo apt-get update
sudo apt-get install -t focal varnish
if [ "$VARNISH_MODULES_VERSION" != "" ]; then sh "${GITHUB_WORKSPACE}/tests/install-varnish-modules.sh"; fi
