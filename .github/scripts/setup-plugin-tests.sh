#!/bin/bash

set -e

# NOTE: for plugin tests the github-action-tests action expects the workspace to have the plugin contents
# so we have to move things around a bit.
shopt -s extglob
mkdir matomo-scoper
cp -R !(matomo-scoper) matomo-scoper

git clone -q --depth 1 https://github.com/matomo-org/plugin-GoogleAnalyticsImporter ${{ github.workspace }}/GoogleAnalyticsImporter
cd ${{ github.workspace }}/GoogleAnalyticsImporter
git fetch -q --depth 1 origin 5.x-dev
git checkout FETCH_HEAD
git submodule update -q --init --recursive --depth 1
cd ${{ github.workspace }}

cp ./GoogleAnalyticsImporter/* .
