#!/bin/bash
# Builds the release zip. The plugin sits in a dxsf-wordpress-proxy folder inside it, the folder
# name 2.x installs have, so `wp plugin install <zip> --force` and the updater replace it in place.

zip_name=${1:-dxsf-wordpress-proxy.zip}
case "$zip_name" in /*) ;; *) zip_name="$(pwd)/$zip_name" ;; esac

build=$(mktemp -d)
mkdir "$build/dxsf-wordpress-proxy"
rsync -a --exclude .git --exclude node_modules --exclude .sass-cache --exclude '*.zip' --exclude zip.sh \
	--exclude tests --exclude .gitignore --exclude .editorconfig --exclude .eslintrc.json --exclude .sass-lint.yml \
	--exclude .DS_Store --exclude npm-debug.log ./ "$build/dxsf-wordpress-proxy/"
rm -f "$zip_name"
(cd "$build" && zip -qr "$zip_name" dxsf-wordpress-proxy)
rm -rf "$build"
echo "Built $zip_name"
