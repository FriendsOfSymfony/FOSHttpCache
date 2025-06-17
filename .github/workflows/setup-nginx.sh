#!/bin/bash

set -e
# Install NGINX
sudo apt-get remove nginx
sudo rm -f /usr/sbin/nginx
sh "${GITHUB_WORKSPACE}/tests/install-nginx.sh"
