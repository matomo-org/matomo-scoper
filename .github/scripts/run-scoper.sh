#!/bin/bash

set -e

# setup matomo-scoper (after matomo tests action sets up PHP, etc.)
cd ..

composer install # TODO: cache the composer directory for the root repo?

cd matomo

../bin/matomo-scoper scope -y --composer-path=composer .
