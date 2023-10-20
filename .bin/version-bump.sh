#!/bin/bash

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
