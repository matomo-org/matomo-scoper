#!/bin/bash

set -e

# setup matomo-scoper (after matomo tests action sets up PHP, etc.)
cd ..

if [ "$PLUGIN_NAME" != "" ]; then
  MATOMO_SCOPER_PATH="$(pwd)/matomo-scoper/bin/matomo-scoper"

  if [ "$PLUGIN_NAME" = "GoogleAnalyticsImporter" ]; then
    cp tests/resources/googleanalyticsimporter-scoper.inc.php matomo/plugins/GoogleAnalyticsImporter/scoper.inc.php
  fi

  cd matomo-scoper

  php8.2 $(which composer) install

  cd "../matomo/plugins/$PLUGIN_NAME"
else
  MATOMO_SCOPER_PATH="$(pwd)/bin/matomo-scoper"

  php8.2 $(which composer) install

  cd matomo
fi

php8.2 "$MATOMO_SCOPER_PATH" scope -y .
