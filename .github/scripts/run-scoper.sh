#!/bin/bash

set -e

# setup matomo-scoper (after matomo tests action sets up PHP, etc.)
cd ..

if [ "$PLUGIN_NAME" != "" ]; then
  MATOMO_SCOPER_PATH="$(pwd)/matomo-scoper/bin/matomo-scoper"

  if [ "$PLUGIN_NAME" = "GoogleAnalyticsImporter" ]; then
    cp ./matomo-scoper/tests/resources/googleanalyticsimporter-scoper.inc.php matomo/plugins/GoogleAnalyticsImporter/scoper.inc.php
  fi

  cd matomo-scoper

  php8.2 $(which composer) install

  cd "../matomo/plugins/$PLUGIN_NAME"
else
  MATOMO_SCOPER_PATH="$(pwd)/bin/matomo-scoper"

  php8.2 $(which composer) install

  cd matomo
fi

sudo sed -i 's/memory_limit[[:space:]]*=[[:space:]]*[0-9-]\+/memory_limit = 2048M/g' /etc/php/8.2/cli/php.ini

if [[ -f "/etc/php/8.2/cli/conf.d/99-pecl.ini" ]]; then
  sudo sed -i 's/memory_limit[[:space:]]*=[[:space:]]*[0-9-]\+[[:space:]]*M/memory_limit = 2048M/g' /etc/php/8.2/cli/conf.d/99-pecl.ini
fi

echo "Memory limit used:"
php -r 'echo ini_get("memory_limit")."\n";'

php8.2 "$MATOMO_SCOPER_PATH" scope -y --rename-references .
