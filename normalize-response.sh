#!/bin/bash
#
# normalize-response.sh
#
# Author: M.Noermoehammad
#
# M1 helper for plan/PERFORMANCE_PLAN_SEPTEMBER2026.md: compare two HTTP
# responses by status code plus a checksum taken AFTER blanking the parts
# that are different on every load by design (security tokens, nonces,
# timestamps). This is what proves the 70.2 percent dynamic / 68.3 percent
# login "failed" rates from `ab` are a measuring quirk (ab compares raw
# byte length against its first response) rather than broken pages.
#
# Portable: curl + sed + sha256sum only. No new PHP, no extensions.
#
# Usage:
#   ./normalize-response.sh -u https://scriptlog.ddev.site/?p=1
#   ./normalize-response.sh -u https://scriptlog.ddev.site/admin/login.php
#
# Exit codes: 0 = stable (same status, same normalized checksum),
#             1 = unstable or transport failure, 2 = usage error.
#
set -uo pipefail
export LC_ALL=C

URL=""
TIMEOUT=10

usage() {
    cat <<EOF
Usage: $0 -u URL [-t TIMEOUT_SECONDS]

Fetches URL twice, compares HTTP status and normalized body checksum.
Volatile parts (CSRF tokens, CSP nonces, timestamps) are blanked before
hashing so per-request security values do not count as differences.
EOF
    exit 2
}

while getopts ":u:t:h" opt; do
    case "$opt" in
        u) URL="$OPTARG" ;;
        t) TIMEOUT="$OPTARG" ;;
        h) usage ;;
        :) echo "ERROR: option -$OPTARG requires an argument." >&2; usage ;;
        \?) echo "ERROR: unknown option -$OPTARG." >&2; usage ;;
    esac
done

[[ -z "$URL" ]] && { echo "ERROR: -u URL is required." >&2; usage; }

# normalize_body: blank per-request volatile values, keep everything else.
# Reads HTML from stdin, writes normalized HTML to stdout.
normalize_body() {
    sed -E \
        -e 's/(name="csrf[^"]*" value=")[^"]*(")/\1__TOKEN__\2/g' \
        -e 's/(name="[^"]*token[^"]*" value=")[^"]*(")/\1__TOKEN__\2/gi' \
        -e "s/(name='csrf[^']*' value=')[^']*(')/\1__TOKEN__\2/g" \
        -e 's/nonce-[A-Za-z0-9+\/=]+/nonce-__NONCE__/g' \
        -e 's/nonce="[^"]*"/nonce="__NONCE__"/g' \
        -e "s/nonce='[^']*'/nonce='__NONCE__'/g" \
        -e 's/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/__DATETIME__/g' \
        -e 's/[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9:.+-]+/__DATETIME__/g'
}

tmpdir=$(mktemp -d) || { echo "ERROR: cannot create temp dir." >&2; exit 1; }
trap 'rm -rf "$tmpdir"' EXIT

code1=$(curl -s -o "$tmpdir/a.body" -w "%{http_code}" --connect-timeout 5 --max-time "$TIMEOUT" "$URL") || code1="000"
code2=$(curl -s -o "$tmpdir/b.body" -w "%{http_code}" --connect-timeout 5 --max-time "$TIMEOUT" "$URL") || code2="000"

if [[ "$code1" == "000" || "$code2" == "000" ]]; then
    echo "UNSTABLE: transport failure (codes $code1/$code2)." >&2
    exit 1
fi

if [[ "$code1" != "$code2" ]]; then
    echo "UNSTABLE: status changed $code1 -> $code2."
    exit 1
fi

sum1=$(normalize_body < "$tmpdir/a.body" | sha256sum | awk '{print $1}')
sum2=$(normalize_body < "$tmpdir/b.body" | sha256sum | awk '{print $1}')
raw1=$(sha256sum "$tmpdir/a.body" | awk '{print $1}')
raw2=$(sha256sum "$tmpdir/b.body" | awk '{print $1}')

echo "status: $code1 (both fetches)"
echo "raw checksum:      $raw1"
echo "raw checksum:      $raw2"
echo "normalized checksum: $sum1"
echo "normalized checksum: $sum2"

if [[ "$sum1" == "$sum2" ]]; then
    if [[ "$raw1" != "$raw2" ]]; then
        echo "STABLE: identical after blanking volatile parts (raw bytes differ by design: tokens/nonces)."
    else
        echo "STABLE: byte-identical responses."
    fi
    exit 0
fi

echo "UNSTABLE: normalized bodies differ (beyond known volatile parts)."
exit 1
