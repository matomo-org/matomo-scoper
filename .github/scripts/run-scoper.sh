#!/bin/bash

set -e

# setup matomo-scoper (after matomo tests action sets up PHP, etc.)
cd ..

php8.2 $(which composer) install # TODO: cache the composer directory for the root repo?

cd matomo

php8.2 ../bin/matomo-scoper scope -y .
