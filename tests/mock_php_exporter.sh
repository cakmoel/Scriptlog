#!/bin/bash
# Mock PHP route exporter
# This script simulates the PHP route exporter for testing purposes.
# It creates a dummy routes.json file.

# Ensure the output directory exists if the output file path includes directories.
OUTPUT_FILE="$1"
OUTPUT_DIR=\$(dirname "\$OUTPUT_FILE")
mkdir -p "\$OUTPUT_DIR"

# Create a dummy routes.json content
echo '{
  "routes": [
    {"url": "/test-route", "method": "GET"},
    {"url": "/admin/dashboard", "method": "GET"},
    {"url": "/login", "method": "POST"}
  ]
}' > "\$OUTPUT_FILE"

exit 0
