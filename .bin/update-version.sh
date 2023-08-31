VERSION=$(node -p -e "require('./package.json').version")

sed -i "" "s/PACKAGE_VERSION/$VERSION/g" build/google-calendar-events/google-calendar-events.php
sed -i "" "s/PACKAGE_VERSION/$VERSION/g" build/google-calendar-events/readme.txt

