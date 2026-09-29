#!/bin/bash
set -uo pipefail
# xd-mode.sh — switch Xdebug modes safely for CLI + FPM
# 
# Author: M.Noermoehammad
#
# Usage:
#   ./xd-mode.sh [basic|cov|full|off|status]
#
# Env overrides:
#   PHP_VERSION         e.g. 8.5 (auto-detected if unset)
#   XDEBUG_CLIENT_HOST  e.g. host.docker.internal (default: localhost)
#   XDEBUG_CLIENT_PORT  default: 9003

# --- 0. DETECT ENVIRONMENT -------------------------------------------------
if ! command -v php >/dev/null 2>&1; then
    echo " php binary not found on PATH." >&2
    exit 1
fi

PHP_VERSION="${PHP_VERSION:-$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')}"
CLI_CONF="/etc/php/${PHP_VERSION}/cli/conf.d/20-xdebug.ini"
FPM_CONF="/etc/php/${PHP_VERSION}/fpm/conf.d/20-xdebug.ini"
LOG_DIR="/var/log/xdebug"
CLIENT_HOST="${XDEBUG_CLIENT_HOST:-localhost}"
CLIENT_PORT="${XDEBUG_CLIENT_PORT:-9003}"

if ! php -m 2>/dev/null | grep -qi '^xdebug$'; then
    echo "  Xdebug extension does not appear to be loaded for PHP ${PHP_VERSION} CLI."
    echo "  Continuing anyway — this may still be fine for FPM-only setups."
fi

for f in "$CLI_CONF" "$FPM_CONF"; do
    [ -f "$f" ] || echo "  Config not found, will be skipped: $f"
done

# --- 1. MODE DEFINITIONS ----------------------------------------------------
BASIC="develop,debug"
COVERAGE="develop,debug,coverage"
FULL="develop,debug,coverage,profile,trace"
OFF="off"

# --- 2. CLEANUP OLD LOGS -----------------------------------------------------
echo " Cleaning up old Xdebug logs..."
sudo mkdir -p "$LOG_DIR"
sudo find "$LOG_DIR" -type f -mtime +1 -delete 2>/dev/null

# --- 3. SELECT MODE ----------------------------------------------------------
case "${1:-basic}" in
    full)
        NEW_MODE=$FULL; START_REQ="trigger"
        echo " Mode: FULL (develop,debug,coverage,profile,trace — heavy disk usage)"
        ;;
    cov|coverage)
        NEW_MODE=$COVERAGE; START_REQ="trigger"
        echo " Mode: COVERAGE (optimized for unit testing)"
        ;;
    off)
        NEW_MODE=$OFF; START_REQ="no"
        echo " Mode: OFF (Xdebug disabled, zero overhead)"
        ;;
    status)
        echo "🔍 Current status (PHP ${PHP_VERSION}):"
        for label_file in "CLI:$CLI_CONF" "FPM:$FPM_CONF"; do
            label="${label_file%%:*}"; file="${label_file#*:}"
            if [ -f "$file" ]; then
                mode=$(grep -E '^[[:space:]]*xdebug\.mode[[:space:]]*=' "$file" | tail -1 | cut -d'=' -f2- | xargs)
                start=$(grep -E '^[[:space:]]*xdebug\.start_with_request[[:space:]]*=' "$file" | tail -1 | cut -d'=' -f2- | xargs)
                echo "  [$label] mode=${mode:-<unset>} start_with_request=${start:-<unset>} ($file)"
            else
                echo "  [$label] config not found ($file)"
            fi
        done
        exit 0
        ;;
    basic|*)
        NEW_MODE=$BASIC; START_REQ="trigger"
        echo "⚡ Mode: BASIC (develop,debug — safe/fast)"
        ;;
esac

# --- 4. INI DIRECTIVE HELPER -------------------------------------------------
# Replaces the directive if present (commented or not), otherwise appends it.
# This is the actual fix: your original sed was replace-only and silently
# no-op'd whenever the line didn't already exist verbatim in the file.
set_ini_directive() {
    local file="$1" key="$2" value="$3"
    [ -f "$file" ] || return 0
    if grep -qE "^[;[:space:]]*${key}[[:space:]]*=" "$file"; then
        sudo sed -i -E "s|^[;[:space:]]*${key}[[:space:]]*=.*|${key}=${value}|" "$file"
    else
        echo "${key}=${value}" | sudo tee -a "$file" > /dev/null
    fi
}

update_config() {
    local file="$1"
    [ -f "$file" ] || return 0

    set_ini_directive "$file" "xdebug.mode" "$NEW_MODE"
    set_ini_directive "$file" "xdebug.start_with_request" "$START_REQ"

    if [ "$NEW_MODE" != "off" ]; then
        set_ini_directive "$file" "xdebug.client_host" "$CLIENT_HOST"
        set_ini_directive "$file" "xdebug.client_port" "$CLIENT_PORT"
        set_ini_directive "$file" "xdebug.log" "${LOG_DIR}/xdebug.log"
        set_ini_directive "$file" "xdebug.output_dir" "$LOG_DIR"
    fi

    # Verify the write actually landed
    local applied
    applied=$(grep -E "^xdebug\.mode[[:space:]]*=" "$file" | tail -1 | cut -d'=' -f2- | xargs)
    if [ "$applied" = "$NEW_MODE" ]; then
        echo " Updated & verified: $file (xdebug.mode=$applied)"
    else
        echo " Verification failed for: $file (expected '$NEW_MODE', got '${applied:-<empty>}')" >&2
    fi
}

update_config "$CLI_CONF"
update_config "$FPM_CONF"

# --- 5. RESTART FPM (CLI needs no restart, ini is read per-invocation) -----
FPM_SERVICE="php${PHP_VERSION}-fpm"
if systemctl list-unit-files 2>/dev/null | grep -q "^${FPM_SERVICE}\.service"; then
    if systemctl is-active --quiet "$FPM_SERVICE"; then
        sudo systemctl restart "$FPM_SERVICE"
        echo " ${FPM_SERVICE} restarted."
    else
        echo "  ${FPM_SERVICE} is installed but not active — skipping restart."
    fi
else
    echo "${FPM_SERVICE} service not found — skipping restart."
fi

echo "System ready (PHP ${PHP_VERSION})."