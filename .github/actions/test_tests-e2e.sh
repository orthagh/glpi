#!/bin/bash
set -e -u -x -o pipefail

# Set baseUrl for Cypress in environment variable
export CYPRESS_BASE_URL="http://localhost:80"

# Install Cypress
node_modules/.bin/cypress install

CHROME_PATH=${{ steps.setup-chrome.outputs.chrome-path }}
echo "CHROME_PATH: $CHROME_PATH"

# Run Cypress tests
node_modules/.bin/cypress run --project tests --browser $CHROME_PATH
