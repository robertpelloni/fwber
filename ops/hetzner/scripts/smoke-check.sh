#!/usr/bin/env bash
set -euo pipefail

# Lightweight but production-oriented post-deploy smoke check for the restored
# fwber stack. The goal is to verify the public contract that matters most after
# a Hetzner cutover without requiring a full browser-driven test run.
#
# Usage examples:
#   ops/hetzner/scripts/smoke-check.sh
#   FWBER_API_URL="https://api.fwber.me/api" \
#   FWBER_GEO_URL="https://geo.fwber.me" \
#   FWBER_BACKEND_DIR="/var/www/fwber/repo/fwber-backend" \
#   ops/hetzner/scripts/smoke-check.sh
#
# Optional authenticated checks can be enabled with bearer tokens:
#   FWBER_USER_BEARER_TOKEN=...       # premium/status/plans checks
#   FWBER_MERCHANT_BEARER_TOKEN=...   # merchant dashboard check
#   FWBER_MODERATOR_BEARER_TOKEN=...  # moderation dashboard + merchant queue
#
# Optional websocket handshake:
#   FWBER_REVERB_APP_KEY=...          # enables a real websocket upgrade probe
#
# Optional report artifacts:
#   FWBER_REPORT_DIR=/var/log/fwber-smoke \
#   ops/hetzner/scripts/smoke-check.sh
#
# When FWBER_REPORT_DIR is set, the script emits:
#   smoke-check-summary.json
#   smoke-check-summary.md

API_URL="${FWBER_API_URL:-https://api.fwber.me/api}"
FRONTEND_URL="${FWBER_FRONTEND_URL:-https://fwber.me}"
WS_URL="${FWBER_WS_URL:-https://ws.fwber.me}"
GEO_URL="${FWBER_GEO_URL:-https://geo.fwber.me}"
BACKEND_DIR="${FWBER_BACKEND_DIR:-/var/www/fwber/repo/fwber-backend}"
USER_BEARER_TOKEN="${FWBER_USER_BEARER_TOKEN:-}"
MERCHANT_BEARER_TOKEN="${FWBER_MERCHANT_BEARER_TOKEN:-$USER_BEARER_TOKEN}"
MODERATOR_BEARER_TOKEN="${FWBER_MODERATOR_BEARER_TOKEN:-}"
REVERB_APP_KEY="${FWBER_REVERB_APP_KEY:-}"
WS_ORIGIN="${FWBER_WS_ORIGIN:-$FRONTEND_URL}"
SKIP_LOCAL_ARTISAN="${FWBER_SKIP_LOCAL_ARTISAN:-0}"
SKIP_WEBSOCKET="${FWBER_SKIP_WEBSOCKET:-0}"
REPORT_DIR="${FWBER_REPORT_DIR:-}"
REPORT_JSON_PATH="${FWBER_REPORT_JSON_PATH:-}"
REPORT_MD_PATH="${FWBER_REPORT_MD_PATH:-}"
ROAST_PAYLOAD='{"name":"Alex","job":"Bartender","trait":"always late","mode":"roast"}'
LOGIN_PAYLOAD='{"email":"smoke-check-invalid@example.com","password":"definitely-not-valid"}'
GEO_QUERY_URL="${GEO_URL%/}/nearby?lat=40.7580&lng=-73.9855&radius_m=500"
STARTED_AT="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"

pass_count=0
fail_count=0
warn_count=0
case_log_file="$(mktemp)"

cleanup() {
  rm -f "$case_log_file"
}

trap cleanup EXIT

info() {
  printf '[info] %s\n' "$1"
}

record_case() {
  local level="$1"
  local label="$2"
  local detail="$3"

  printf '%s\t%s\t%s\n' "$level" "$label" "$detail" >> "$case_log_file"
  printf '[%s] %s%s\n' "$level" "$label" "${detail:+ - $detail}"
}

pass_case() {
  pass_count=$((pass_count + 1))
  record_case 'pass' "$1" "$2"
}

warn_case() {
  warn_count=$((warn_count + 1))
  record_case 'warn' "$1" "$2"
}

fail_case() {
  fail_count=$((fail_count + 1))
  record_case 'fail' "$1" "$2"
}

require_command() {
  local command_name="$1"

  if ! command -v "$command_name" >/dev/null 2>&1; then
    echo "Required command '$command_name' is missing." >&2
    exit 1
  fi
}

json_escape() {
  local value="$1"
  value=${value//\\/\\\\}
  value=${value//\"/\\\"}
  value=${value//$'\n'/\\n}
  value=${value//$'\r'/\\r}
  value=${value//$'\t'/\\t}
  printf '%s' "$value"
}

ensure_report_paths() {
  if [[ -z "$REPORT_DIR" && -z "$REPORT_JSON_PATH" && -z "$REPORT_MD_PATH" ]]; then
    return
  fi

  if [[ -n "$REPORT_DIR" ]]; then
    mkdir -p "$REPORT_DIR"

    if [[ -z "$REPORT_JSON_PATH" ]]; then
      REPORT_JSON_PATH="$REPORT_DIR/smoke-check-summary.json"
    fi

    if [[ -z "$REPORT_MD_PATH" ]]; then
      REPORT_MD_PATH="$REPORT_DIR/smoke-check-summary.md"
    fi
  fi

  if [[ -n "$REPORT_JSON_PATH" ]]; then
    mkdir -p "$(dirname "$REPORT_JSON_PATH")"
  fi

  if [[ -n "$REPORT_MD_PATH" ]]; then
    mkdir -p "$(dirname "$REPORT_MD_PATH")"
  fi
}

write_json_report() {
  if [[ -z "$REPORT_JSON_PATH" ]]; then
    return
  fi

  local overall_status='passed'
  if [[ "$fail_count" -gt 0 ]]; then
    overall_status='failed'
  elif [[ "$warn_count" -gt 0 ]]; then
    overall_status='passed_with_warnings'
  fi

  {
    printf '{\n'
    printf '  "started_at": "%s",\n' "$(json_escape "$STARTED_AT")"
    printf '  "finished_at": "%s",\n' "$(json_escape "$(date -u +"%Y-%m-%dT%H:%M:%SZ")")"
    printf '  "overall_status": "%s",\n' "$overall_status"
    printf '  "targets": {\n'
    printf '    "api_url": "%s",\n' "$(json_escape "$API_URL")"
    printf '    "frontend_url": "%s",\n' "$(json_escape "$FRONTEND_URL")"
    printf '    "geo_url": "%s",\n' "$(json_escape "$GEO_URL")"
    printf '    "ws_url": "%s"\n' "$(json_escape "$WS_URL")"
    printf '  },\n'
    printf '  "summary": {\n'
    printf '    "passes": %s,\n' "$pass_count"
    printf '    "warnings": %s,\n' "$warn_count"
    printf '    "failures": %s\n' "$fail_count"
    printf '  },\n'
    printf '  "cases": [\n'

    local first_case=1
    while IFS=$'\t' read -r level label detail; do
      if [[ "$first_case" -eq 0 ]]; then
        printf ',\n'
      fi

      first_case=0
      printf '    {"level":"%s","label":"%s","detail":"%s"}' \
        "$(json_escape "$level")" \
        "$(json_escape "$label")" \
        "$(json_escape "$detail")"
    done < "$case_log_file"

    printf '\n  ]\n'
    printf '}\n'
  } > "$REPORT_JSON_PATH"

  info "Wrote JSON smoke-check report to $REPORT_JSON_PATH"
}

write_markdown_report() {
  if [[ -z "$REPORT_MD_PATH" ]]; then
    return
  fi

  local overall_status='PASSED'
  if [[ "$fail_count" -gt 0 ]]; then
    overall_status='FAILED'
  elif [[ "$warn_count" -gt 0 ]]; then
    overall_status='PASSED WITH WARNINGS'
  fi

  {
    printf -- '# fwber Smoke Check Report\n\n'
    printf -- '- **Started:** `%s`\n' "$STARTED_AT"
    printf -- '- **Finished:** `%s`\n' "$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
    printf -- '- **Overall Status:** **%s**\n' "$overall_status"
    printf -- '- **API URL:** `%s`\n' "$API_URL"
    printf -- '- **Frontend URL:** `%s`\n' "$FRONTEND_URL"
    printf -- '- **Geo URL:** `%s`\n' "$GEO_URL"
    printf -- '- **Websocket URL:** `%s`\n\n' "$WS_URL"
    printf -- '## Summary\n\n'
    printf -- '- Passes: **%s**\n' "$pass_count"
    printf -- '- Warnings: **%s**\n' "$warn_count"
    printf -- '- Failures: **%s**\n\n' "$fail_count"
    printf -- '## Case Results\n\n'
    printf -- '| Level | Check | Detail |\n'
    printf -- '| --- | --- | --- |\n'

    while IFS=$'\t' read -r level label detail; do
      printf -- '| %s | %s | %s |\n' "$level" "$label" "$detail"
    done < "$case_log_file"
  } > "$REPORT_MD_PATH"

  info "Wrote Markdown smoke-check report to $REPORT_MD_PATH"
}

write_reports() {
  ensure_report_paths
  write_json_report
  write_markdown_report
}

check_local_artisan() {
  local label='Local artisan deploy verification'

  if [[ "$SKIP_LOCAL_ARTISAN" == "1" ]]; then
    warn_case "$label" 'Skipped because FWBER_SKIP_LOCAL_ARTISAN=1.'
    return
  fi

  if [[ ! -f "$BACKEND_DIR/artisan" ]]; then
    warn_case "$label" "Skipped because '$BACKEND_DIR/artisan' was not found."
    return
  fi

  require_command php

  local output
  if output="$(cd "$BACKEND_DIR" && php artisan deploy:verify --json 2>&1)"; then
    if grep -Eq '"status"[[:space:]]*:[[:space:]]*"healthy"' <<<"$output"; then
      pass_case "$label" 'php artisan deploy:verify reported healthy.'
      return
    fi

    fail_case "$label" "deploy:verify did not report healthy. Output: $(printf '%s' "$output" | tr '\n' ' ' | head -c 400)"
    return
  fi

  fail_case "$label" "deploy:verify failed to execute. Output: $(printf '%s' "$output" | tr '\n' ' ' | head -c 400)"
}

run_http_check() {
  local label="$1"
  local method="$2"
  local url="$3"
  local expected_codes="$4"
  local body_regex="$5"
  local payload="${6:-}"
  local bearer_token="${7:-}"

  local response_file
  local error_file
  response_file="$(mktemp)"
  error_file="$(mktemp)"

  local -a curl_args
  curl_args=(
    -sS
    -X "$method"
    "$url"
    -H 'Accept: application/json'
    -o "$response_file"
    -w '%{http_code}'
  )

  if [[ -n "$payload" ]]; then
    curl_args+=(
      -H 'Content-Type: application/json'
      --data "$payload"
    )
  fi

  if [[ -n "$bearer_token" ]]; then
    curl_args+=( -H "Authorization: Bearer $bearer_token" )
  fi

  local http_code
  if ! http_code="$(curl "${curl_args[@]}" 2>"$error_file")"; then
    local curl_error
    curl_error="$(tr '\n' ' ' < "$error_file")"
    rm -f "$response_file" "$error_file"
    fail_case "$label" "Request failed to connect: ${curl_error:-curl error}"
    return
  fi

  local response_body
  response_body="$(cat "$response_file")"
  rm -f "$response_file" "$error_file"

  local expected_code_matched=0
  local expected_code
  for expected_code in $expected_codes; do
    if [[ "$http_code" == "$expected_code" ]]; then
      expected_code_matched=1
      break
    fi
  done

  if [[ "$expected_code_matched" != "1" ]]; then
    fail_case "$label" "Returned HTTP $http_code, expected one of [$expected_codes]. Body: $(printf '%s' "$response_body" | head -c 400)"
    return
  fi

  if [[ -n "$body_regex" ]] && ! grep -Eq "$body_regex" <<<"$response_body"; then
    fail_case "$label" "Returned HTTP $http_code but body did not match /$body_regex/. Body: $(printf '%s' "$response_body" | head -c 400)"
    return
  fi

  pass_case "$label" "Returned HTTP $http_code."
}

check_websocket_upgrade() {
  local label='Websocket upgrade probe'

  if [[ "$SKIP_WEBSOCKET" == "1" ]]; then
    warn_case "$label" 'Skipped because FWBER_SKIP_WEBSOCKET=1.'
    return
  fi

  if [[ -z "$REVERB_APP_KEY" ]]; then
    warn_case "$label" 'Skipped because FWBER_REVERB_APP_KEY is not set.'
    return
  fi

  require_command openssl

  local ws_host
  ws_host="$(printf '%s' "$WS_URL" | sed -E 's#^https?://##; s#/.*$##')"

  local path
  path="/app/${REVERB_APP_KEY}?protocol=7&client=fwber-smoke-check&version=1.0&flash=false"

  local handshake_response
  handshake_response="$({
    printf 'GET %s HTTP/1.1\r\n' "$path"
    printf 'Host: %s\r\n' "$ws_host"
    printf 'Origin: %s\r\n' "$WS_ORIGIN"
    printf 'Connection: Upgrade\r\n'
    printf 'Upgrade: websocket\r\n'
    printf 'Sec-WebSocket-Key: SGV0em5lclNtb2tlQ2hlY2s=\r\n'
    printf 'Sec-WebSocket-Version: 13\r\n\r\n'
  } | openssl s_client -quiet -connect "${ws_host}:443" -servername "$ws_host" 2>/dev/null | head -n 1)"

  if grep -Eq '101[[:space:]]+Switching Protocols' <<<"$handshake_response"; then
    pass_case "$label" "Received a successful websocket upgrade from $WS_URL."
    return
  fi

  fail_case "$label" "Did not receive 101 Switching Protocols. First line: ${handshake_response:-<empty>}"
}

run_optional_authenticated_checks() {
  if [[ -n "$USER_BEARER_TOKEN" ]]; then
    run_http_check 'Premium plans endpoint' GET "${API_URL%/}/premium/plans" '200' '"plans"' '' "$USER_BEARER_TOKEN"
    run_http_check 'Premium status endpoint' GET "${API_URL%/}/premium/status" '200' '"is_premium"' '' "$USER_BEARER_TOKEN"
  else
    warn_case 'Premium authenticated smoke checks' 'Skipped because FWBER_USER_BEARER_TOKEN is not set.'
  fi

  if [[ -n "$MERCHANT_BEARER_TOKEN" ]]; then
    run_http_check 'Merchant dashboard endpoint' GET "${API_URL%/}/merchant-portal/dashboard" '200' '"stats"' '' "$MERCHANT_BEARER_TOKEN"
  else
    warn_case 'Merchant authenticated smoke checks' 'Skipped because FWBER_MERCHANT_BEARER_TOKEN is not set.'
  fi

  if [[ -n "$MODERATOR_BEARER_TOKEN" ]]; then
    run_http_check 'Moderation dashboard endpoint' GET "${API_URL%/}/moderation/dashboard" '200' '"stats"' '' "$MODERATOR_BEARER_TOKEN"
    run_http_check 'Merchant moderation queue endpoint' GET "${API_URL%/}/moderation/merchants" '200' '"data"|"current_page"' '' "$MODERATOR_BEARER_TOKEN"
  else
    warn_case 'Moderation authenticated smoke checks' 'Skipped because FWBER_MODERATOR_BEARER_TOKEN is not set.'
  fi
}

main() {
  require_command curl

  info 'Starting fwber post-deploy smoke check.'
  info "API URL: $API_URL"
  info "Frontend URL: $FRONTEND_URL"
  info "Geo URL: $GEO_URL"
  info "WS URL: $WS_URL"

  check_local_artisan
  run_http_check 'Frontend reachability' GET "$FRONTEND_URL" '200 301 302 307 308' ''
  run_http_check 'API health endpoint' GET "${API_URL%/}/health" '200' '"status"[[:space:]]*:[[:space:]]*"healthy"'
  run_http_check 'API liveness endpoint' GET "${API_URL%/}/health/liveness" '200' '"status"[[:space:]]*:[[:space:]]*"alive"'
  run_http_check 'API readiness endpoint' GET "${API_URL%/}/health/readiness" '200' '"status"[[:space:]]*:[[:space:]]*"ready"'
  run_http_check 'Invalid-login contract check' POST "${API_URL%/}/auth/login" '422' 'Invalid credentials' "$LOGIN_PAYLOAD"
  run_http_check 'Public roast preview check' POST "${API_URL%/}/public/roast" '200' '"is_preview"[[:space:]]*:[[:space:]]*true' "$ROAST_PAYLOAD"
  run_http_check 'Geo nearby endpoint' GET "$GEO_QUERY_URL" '200' '"users"'
  check_websocket_upgrade
  run_optional_authenticated_checks

  echo
  info "Smoke-check summary: passes=$pass_count warnings=$warn_count failures=$fail_count"
  write_reports

  if [[ "$fail_count" -gt 0 ]]; then
    exit 1
  fi
}

main "$@"
