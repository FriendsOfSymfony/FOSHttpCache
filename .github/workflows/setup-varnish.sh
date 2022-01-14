#!/bin/bash

set -e
echo "### Installing Varnish $VARNISH_VERSION ###"
VARNISH_VERSION=$VARNISH_VERSION bash -c 'curl -s https://packagecloud.io/install/repositories/varnishcache/varnish${VARNISH_VERSION//./}/script.deb.sh' | sudo bash
sudo apt-get install varnish
if [ "$VARNISH_MODULES_VERSION" != "" ]; then sh "${GITHUB_WORKSPACE}/tests/install-varnish-modules.sh"; fi
