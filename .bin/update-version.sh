#!/bin/bash

VERSION=$(node -p -e "require('./package.json').version")

perl -i -pe"s/PACKAGE_VERSION/$VERSION/g" build/google-calendar-events/google-calendar-events.php
perl -i -pe"s/PACKAGE_VERSION/$VERSION/g" build/google-calendar-events/readme.txt

