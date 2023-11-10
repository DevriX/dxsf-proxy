#!/bin/bash

zip_name=$1

if [ -n "$zip_name" ]; then
    echo "Updating plugin package ..."
    zip -r "$zip_name" ./ -x *.git* ./node_modules/**\* ./.sass-cache/**\* *.zip ./ziptheme.sh ./npm-debug.log ./tests ./gulpfile.js "*.DS_Store" "*.editorconfig" "*.gitignore" "*.sass-cache/" "*.node_modules/" "tests/*" "zip.sh" ".eslintrc.json" ".editorconfig" ".sass-lint.yml"

    echo "Zip successfull."
else
    echo "ERROR: ZIP file name not provided..."
fi
