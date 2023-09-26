#!/bin/bash

set -e

# setup matomo-scoper (after matomo tests action sets up PHP, etc.)
cd ..

ls /usr/bin/php*
ls /usr/local/bin/php*

find / -name php

php8-cli $(which composer) install # TODO: cache the composer directory for the root repo?

cd matomo

php8-cli ../bin/matomo-scoper scope -y --composer-path=composer .
