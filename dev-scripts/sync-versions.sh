#!/bin/bash

# Path to Main.php (adjust as needed)
MAIN_PHP_FILE="src/Main.php"

# Check if Main.php exists
if [ ! -f "$MAIN_PHP_FILE" ]; then
  echo "Error: $MAIN_PHP_FILE not found."
  exit 1
fi

# Extract version from Main.php
VERSION=$(grep -oP "private static \\\$version\\s*=\\s*'\\K[^']+" "$MAIN_PHP_FILE")

if [ -z "$VERSION" ]; then
  echo "Error: Could not extract version from $MAIN_PHP_FILE."
  echo "Debug: The following line should match the regex:"
  grep "private static \$version" "$MAIN_PHP_FILE"
  exit 1
fi

echo "Extracted version: $VERSION"

# Update package.json (if it exists)
if [ -f "package.json" ]; then


  # Update the version in package.json
  sed -i "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" package.json
  echo "Updated package.json with version: $VERSION"
else
  echo "package.json not found. Skipping."
fi

echo "Version sync complete."
exit 0