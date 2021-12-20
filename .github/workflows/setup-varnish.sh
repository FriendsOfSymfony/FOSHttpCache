#!/bin/bash

set -e
# Install Varnish
echo "### Installing Varnish $VARNISH_VERSION ###"
curl -s https://packagecloud.io/install/repositories/varnishcache/varnish66/script.deb.sh | sudo bash
sudo apt-get install -t focal varnish
if [ "$VARNISH_MODULES_VERSION" != "" ]; then sh "${GITHUB_WORKSPACE}/tests/install-varnish-modules.sh"; fi
