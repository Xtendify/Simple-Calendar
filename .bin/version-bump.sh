#!/bin/bash

OLD_VERSION=$(node -p -e "require('./package.json').version")

options=("patch" "minor" "major")
select opt in "${options[@]}"
do
    case $opt in
        "patch")
            break
            ;;
        "minor")
            break
            ;;
        "major")
            break
            ;;
        *)
            echo "invalid option $REPLY"
            exit 0
            ;;
    esac
done

npm version $opt --no-git-tag-version

NEW_VERSION=$(node -p -e "require('./package.json').version")

sed -i "" "s/$OLD_VERSION/$NEW_VERSION/g" google-calendar-events.php
sed -i "" "s/Stable tag: $OLD_VERSION/Stable tag: $NEW_VERSION/g" readme.txt

npm run build
