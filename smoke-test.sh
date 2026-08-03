#!/usr/bin/env bash
# =============================================================================
# smoke-test.sh — API smoke test for the VPN Gateway web interface
# @since  2026-04-24 — FectionLabs security pass
#
# Usage:
#   ./smoke-test.sh [BASE_URL] [USERNAME] [PASSWORD]
#
# Defaults:
#   BASE_URL  = http://localhost
#   USERNAME  = login
#   PASSWORD  = (prompted if not supplied)
#
# Requirements: curl, jq (optional — degrades gracefully if absent)
# =============================================================================

set -euo pipefail

# ── Colour helpers ────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; NC='\033[0m'
ok()   { echo -e "${GREEN}[PASS]${NC} $*"; }
fail() { echo -e "${RED}[FAIL]${NC} $*"; FAILURES=$((FAILURES + 1)); }
info() { echo -e "${YELLOW}[INFO]${NC} $*"; }

FAILURES=0
COOKIE_JAR=$(mktemp /tmp/vpn-smoke-cookies-XXXXXX.txt)
trap 'rm -f "$COOKIE_JAR"' EXIT

# ── Arguments ─────────────────────────────────────────────────────────────────
BASE_URL="${1:-http://localhost}"
BASE_URL="${BASE_URL%/}"           # strip trailing slash
USERNAME="${2:-login}"
if [[ -n "${3:-}" ]]; then
    PASSWORD="$3"
else
    read -r -s -p "Password for '${USERNAME}': " PASSWORD
    echo
fi

HAS_JQ=false
command -v jq &>/dev/null && HAS_JQ=true

# ── Helper: HTTP request ───────────────────────────────────────────────────────
# Usage: do_request METHOD URL [extra curl args...]
# Sets globals: HTTP_STATUS  RESPONSE_BODY
do_request() {
    local method="$1" url="$2"; shift 2
    local tmp
    tmp=$(mktemp)
    HTTP_STATUS=$(curl -s -o "$tmp" -w '%{http_code}' \
        -X "$method" \
        -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
        "$@" \
        "$url") || true
    RESPONSE_BODY=$(cat "$tmp")
    rm -f "$tmp"
}

json_field() {
    local field="$1"
    if $HAS_JQ; then
        echo "$RESPONSE_BODY" | jq -r ".$field // empty" 2>/dev/null || true
    else
        # naive grep fallback
        echo "$RESPONSE_BODY" | grep -o "\"${field}\":[^,}]*" | head -1 | sed 's/.*: *//'
    fi
}

# =============================================================================
echo
echo "=== VPN Gateway smoke tests against: ${BASE_URL} ==="
echo

# ── Test 1: Health endpoint ───────────────────────────────────────────────────
info "1/7  GET /api/health.php"
do_request GET "${BASE_URL}/api/health.php"

if [[ "$HTTP_STATUS" == "200" ]]; then
    ok "Health endpoint returned HTTP 200"
    HEALTH_OK=$(json_field ok)
    [[ "$HEALTH_OK" == "true" ]] && ok "All health checks passed" \
                                  || fail "Health checks contain failures: $RESPONSE_BODY"
elif [[ "$HTTP_STATUS" == "503" ]]; then
    fail "Health endpoint returned HTTP 503 (degraded): $RESPONSE_BODY"
else
    fail "Health endpoint returned unexpected HTTP ${HTTP_STATUS}"
fi

# ── Test 2: Unauthenticated access to dashboard is redirected ─────────────────
info "2/7  GET / without session — expect redirect to login"
do_request GET "${BASE_URL}/index.php"

if [[ "$HTTP_STATUS" == "302" || "$HTTP_STATUS" == "301" ]]; then
    ok "Unauthenticated request redirected (HTTP ${HTTP_STATUS})"
elif [[ "$HTTP_STATUS" == "200" ]] && echo "$RESPONSE_BODY" | grep -qi "login"; then
    ok "Unauthenticated request shows login page (HTTP 200 with login form)"
else
    fail "Expected redirect or login page, got HTTP ${HTTP_STATUS}"
fi

# ── Test 3: Unauthenticated API call returns 401 ─────────────────────────────
info "3/7  GET /api/status.php without session — expect 401"
do_request GET "${BASE_URL}/api/status.php"

if [[ "$HTTP_STATUS" == "401" ]]; then
    ok "Unauthenticated API call returned HTTP 401"
else
    fail "Expected HTTP 401, got HTTP ${HTTP_STATUS}"
fi

# ── Test 4: Fetch login page and extract CSRF token ──────────────────────────
info "4/7  GET login page — extract CSRF token"
do_request GET "${BASE_URL}/index.php"

CSRF_TOKEN=$(echo "$RESPONSE_BODY" | grep -o 'name="csrf_token" value="[^"]*"' | sed 's/.*value="//;s/"//')

if [[ -n "$CSRF_TOKEN" ]]; then
    ok "CSRF token found in login form: ${CSRF_TOKEN:0:16}…"
else
    fail "CSRF token not found in login page"
    CSRF_TOKEN="missing"
fi

# ── Test 5: Login ─────────────────────────────────────────────────────────────
info "5/7  POST /login-action.php — authenticate"
do_request POST "${BASE_URL}/login-action.php" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    --data-urlencode "username=${USERNAME}" \
    --data-urlencode "password=${PASSWORD}" \
    --data-urlencode "csrf_token=${CSRF_TOKEN}"

if [[ "$HTTP_STATUS" == "302" || "$HTTP_STATUS" == "301" ]]; then
    LOCATION=$(echo "$RESPONSE_BODY" | grep -i "location:" | head -1 | tr -d '\r')
    ok "Login redirected (HTTP ${HTTP_STATUS})"
elif [[ "$HTTP_STATUS" == "200" ]] && echo "$RESPONSE_BODY" | grep -qi "dashboard\|logout"; then
    ok "Login succeeded — dashboard loaded (HTTP 200)"
else
    fail "Login failed — HTTP ${HTTP_STATUS}; response: ${RESPONSE_BODY:0:200}"
fi

# ── Test 6: Authenticated status call ────────────────────────────────────────
info "6/7  GET /api/status.php — authenticated"
do_request GET "${BASE_URL}/api/status.php"

if [[ "$HTTP_STATUS" == "200" ]]; then
    ok "Authenticated status call returned HTTP 200"
    ACTIVE_STATE=$(json_field activeState)
    [[ -n "$ACTIVE_STATE" ]] && ok "activeState = ${ACTIVE_STATE}" \
                              || fail "activeState missing from response: $RESPONSE_BODY"
else
    fail "Expected HTTP 200, got HTTP ${HTTP_STATUS}: $RESPONSE_BODY"
fi

# ── Test 7: Authenticated log call ───────────────────────────────────────────
info "7/7  GET /api/logs.php?limit=10 — authenticated"
do_request GET "${BASE_URL}/api/logs.php?limit=10"

if [[ "$HTTP_STATUS" == "200" ]]; then
    ok "Authenticated log call returned HTTP 200"
    LOG_LINES=$(json_field lines)
    $HAS_JQ && LOG_LINES=$(echo "$RESPONSE_BODY" | jq '.lines | length' 2>/dev/null) || true
    [[ -n "$LOG_LINES" ]] && info "Log lines returned: ${LOG_LINES}"
else
    fail "Expected HTTP 200, got HTTP ${HTTP_STATUS}: $RESPONSE_BODY"
fi

# ── Summary ───────────────────────────────────────────────────────────────────
echo
echo "=================================="
if [[ "$FAILURES" -eq 0 ]]; then
    echo -e "${GREEN}All tests passed.${NC}"
    exit 0
else
    echo -e "${RED}${FAILURES} test(s) failed.${NC}"
    exit 1
fi
