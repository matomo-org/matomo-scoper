#!/bin/bash

set -e

# NOTE: for plugin tests the github-action-tests action expects the workspace to have the plugin contents
# so we have to move things around a bit.
shopt -s extglob
mkdir matomo-scoper
mv !(matomo-scoper) matomo-scoper

git clone -q --depth 1 https://github.com/matomo-org/plugin-GoogleAnalyticsImporter $WORKSPACE/GoogleAnalyticsImporter
cd $WORKSPACE/GoogleAnalyticsImporter
git fetch -q --depth 1 origin debug-build
git checkout FETCH_HEAD
git submodule update -q --init --recursive --depth 1
cd $WORKSPACE

mv ./GoogleAnalyticsImporter/* .
