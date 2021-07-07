#!/bin/bash

set -e
# Install NGINX
sudo apt-get remove nginx
sudo rm /usr/sbin/nginx
sh "${GITHUB_WORKSPACE}/tests/install-nginx.sh"
