#!/bin/bash

set -e
echo "### Installing Legacy Varnish $VARNISH_VERSION ###"
VARNISH_VERSION=$VARNISH_VERSION bash -c 'curl -s https://packagecloud.io/install/repositories/varnishcache/varnish${VARNISH_VERSION//./}/script.deb.sh' | sudo bash
# this is broken: we don't find the version. when not specifying the version, we end up with varnish 5 from the regular ubuntu distribution
VARNISH_VERSION=$VARNISH_VERSION bash -c 'sudo apt-get install varnish=${VARNISH_VERSION}.*'
if [ "$VARNISH_MODULES_VERSION" != "" ]; then sh "${GITHUB_WORKSPACE}/tests/install-varnish-modules-legacy.sh"; fi
