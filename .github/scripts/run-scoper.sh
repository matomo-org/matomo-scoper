#!/bin/bash

set -e

# matomo-scoper's locked dependencies need PHP 8.2+, but the job's php may be older (eg 7.2), so fall
# back to a newer binary already on the runner. The runner image ships one; setup-php only installs
# the job's version.
SCOPER_PHP=""
if php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);'; then
  SCOPER_PHP=php
else
  for candidate in php8.5 php8.4 php8.3 php8.2; do
    if command -v "$candidate" >/dev/null; then
      SCOPER_PHP="$candidate"
      break
    fi
  done
fi

if [ -z "$SCOPER_PHP" ]; then
  echo "No PHP 8.2 or later found to run matomo-scoper with." >&2
  exit 1
fi

SCOPER_PHP_VERSION="$("$SCOPER_PHP" -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
echo "Running matomo-scoper with $SCOPER_PHP ($SCOPER_PHP_VERSION)"

# setup matomo-scoper (after matomo tests action sets up PHP, etc.)
cd ..

if [ "$PLUGIN_NAME" != "" ]; then
  MATOMO_SCOPER_PATH="$(pwd)/matomo-scoper/bin/matomo-scoper"

  if [ "$PLUGIN_NAME" = "GoogleAnalyticsImporter" ]; then
    cp ./matomo-scoper/tests/resources/googleanalyticsimporter-scoper.inc.php matomo/plugins/GoogleAnalyticsImporter/scoper.inc.php
  fi

  cd matomo-scoper

  "$SCOPER_PHP" "$(which composer)" install

  cd "../matomo/plugins/$PLUGIN_NAME"
else
  MATOMO_SCOPER_PATH="$(pwd)/bin/matomo-scoper"

  "$SCOPER_PHP" "$(which composer)" install

  cd matomo
fi

sudo sed -i 's/memory_limit[[:space:]]*=[[:space:]]*[0-9-]\+/memory_limit = 2048M/g' "/etc/php/$SCOPER_PHP_VERSION/cli/php.ini"

if [[ -f "/etc/php/$SCOPER_PHP_VERSION/cli/conf.d/99-pecl.ini" ]]; then
  sudo sed -i 's/memory_limit[[:space:]]*=[[:space:]]*[0-9-]\+[[:space:]]*M/memory_limit = 2048M/g' "/etc/php/$SCOPER_PHP_VERSION/cli/conf.d/99-pecl.ini"
fi

echo "Memory limit used:"
"$SCOPER_PHP" -r 'echo ini_get("memory_limit")."\n";'

"$SCOPER_PHP" "$MATOMO_SCOPER_PATH" scope -y --rename-references .
