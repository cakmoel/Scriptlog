#!/usr/bin/env bash
#
# Shared setup for the siege-tui BATS suite.
#
# Provides _common_setup (called from each test's setup()) which creates an
# isolated HOME sandbox and sources the script under test. The script's main
# entry point is guarded, so sourcing is side-effect free apart from
# initializing its default configuration variables.

# Absolute path to the tool under test (repo-relative to this helper).
SIEGE_TUI_SCRIPT="${BATS_TEST_DIRNAME}/../../siege-tui.sh"

_common_setup() {
    TEST_HOME="${BATS_TEST_TMPDIR}/home"
    mkdir -p "${TEST_HOME}/.config/siege-tui"

    export HOME="${TEST_HOME}"
    unset RUN_SCENARIO AUTO_YES NONINTERACTIVE

    # shellcheck source=/dev/null
    source "${SIEGE_TUI_SCRIPT}"
}

# Seeds a configuration file with one configured scenario plus defaults,
# mirroring what save_config would persist.
_seed_config() {
    cat > "${CONFIG_FILE}" <<EOF
STATIC_URL='http://fixture.test/index.html'
STATIC_TYPE='html'
STATIC_METHOD='GET'
STATIC_BODY=''
LOGIN_URL=''
LOGIN_TYPE='html'
LOGIN_METHOD='GET'
LOGIN_BODY=''
NOTFOUND_URL=''
NOTFOUND_TYPE='html'
NOTFOUND_METHOD='GET'
NOTFOUND_BODY=''
DYNAMIC_URL=''
DYNAMIC_TYPE='php'
DYNAMIC_METHOD='GET'
DYNAMIC_BODY=''
CONCURRENCY='7'
REQUEST_MODE='requests'
REQUEST_COUNT='42'
DURATION='30S'
DELAY='0'
WORKLOAD_SAMPLES='9'
USER_AGENT='Bats-UA/1'
CUSTOM_HEADERS=''
CONTENT_TYPE=''
EOF
}
