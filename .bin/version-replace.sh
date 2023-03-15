#!/bin/bash

OLD_VERSION=$(node -p -e "require('./package.json').version")

echo -n "Enter new version: "
read NEW_VERSION

sed -i "" "s/$OLD_VERSION/$NEW_VERSION/g" package.json
sed -i "" "s/$OLD_VERSION/$NEW_VERSION/g" google-calendar-events.php
sed -i "" "s/$OLD_VERSION/$NEW_VERSION/g" readme.txt

npm run build
