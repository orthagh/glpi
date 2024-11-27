#!/bin/bash
set -e -u -x -o pipefail

# Set baseUrl for Cypress in environment variable
export CYPRESS_BASE_URL="http://localhost:80"

# Install Cypress
node_modules/.bin/cypress install

# Install chrome
wget -q https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb
apt-get install -y ./google-chrome-stable_current_amd64.deb
chrome --version

# Run Cypress tests
node_modules/.bin/cypress run --project tests --browser chrome
