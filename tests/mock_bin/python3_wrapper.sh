#!/bin/bash
# Wrapper script for python3 to mock 'python3 -m venv' command.

# Define a base directory for mock venv creation.
# This path is hardcoded for simplicity and clarity.
MOCK_VENV_BASE_DIR="/var/www/blogware/public_html/tests/mock_venv_base"

# Ensure the base directory exists
mkdir -p "$MOCK_VENV_BASE_DIR"

# Check if the command is 'python3 -m venv ...'
if [[ "$@" == "-m venv"* ]]; then
    echo "Mocking python3 -m venv..."
    # Extract the venv directory path from the arguments passed to python3
    VENV_PATH=$(echo "$@" | sed -n 's/.*-m venv \(.*\)/\1/p')
    if [ -z "$VENV_PATH" ]; then
        echo "Mock Python wrapper error: Could not extract venv path from '$@'." >&2
        exit 1
    fi

    # Construct the full path for the virtual environment to be created
    FULL_VENV_TARGET_PATH="$MOCK_VENV_BASE_DIR/$VENV_PATH"

    # Call our mock activate script with the constructed path
    # Note: MOCK_DIR is not defined here, assumes 'activate' is in PATH or same dir.
    # For this approach, we assume 'activate' is callable directly.
    "$MOCK_DIR/activate" "$FULL_VENV_TARGET_PATH"

    # Update PATH for sourced activate script
    export PATH="$FULL_VENV_TARGET_PATH/bin:$PATH"

    exit 0 # Successfully mocked 'venv' creation
else
    # Fallback for non-venv commands
    echo "Executing mock python3 with args: $@"
    # Fallback to actual python3 if available and not a venv command
    if command -v /usr/bin/python3 &> /dev/null; then
        /usr/bin/python3 "$@"
    else
        echo "Mock Python wrapper error: python3 not found or not mocked correctly." >&2
        exit 1
    fi
fi
