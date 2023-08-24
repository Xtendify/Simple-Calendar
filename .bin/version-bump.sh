#!/bin/bash

OLD_VERSION=$(node -p -e "require('./package.json').version")

options=("major" "minor" "patch" "premajor" "preminor" "prepatch" "prerelease")
select opt in "${options[@]}"
do
    case $opt in
        "major")
            break
            ;;
        "minor")
            break
            ;;
        "patch")
            break
            ;;
        "premajor")
            break
            ;;
        "preminor")
            break
            ;;
        "prepatch")
            break
            ;;
        "prerelease")
            break
            ;;
        *)
            echo "invalid option $REPLY"
            exit 0
            ;;
    esac
done

yarn version $opt --no-git-tag-version --preid=beta

NEW_VERSION=$(node -p -e "require('./package.json').version")

sed -i "" "s/$OLD_VERSION/$NEW_VERSION/g" google-calendar-events.php
sed -i "" "s/Stable tag: $OLD_VERSION/Stable tag: $NEW_VERSION/g" readme.txt

yarn build
