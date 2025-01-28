#!/bin/bash

# Run the sync-versions script
./dev-scripts/sync-versions.sh

# Check if the script succeeded
if [ $? -ne 0 ]; then
  echo "Error: Version sync failed. Aborting commit."
  exit 1
fi

echo "Version sync successful. Proceeding with commit."


./vendor/bin/phpunit   tests

if [ $? -ne 0 ]; then
  echo "Error: Tests Failed. Aborting commit."
  exit 1
fi

echo "Tests Succeeded. Proceeding with commit."
exit 0