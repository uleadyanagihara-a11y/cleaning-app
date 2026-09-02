#!/usr/bin/env bash
# PostToolUse hook: format the file Claude just wrote/edited.
#   *.php                                  -> Pint
#   resources/**/*.{js,ts,vue,css,json}    -> Prettier  (+ ESLint --fix for js/vue)
# Each tool runs locally if a Linux toolchain is present, otherwise via `./vendor/bin/sail`.
# Never blocks: always exits 0, all tool output is discarded.
set -u

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$root" || exit 0

# The hook receives the tool call as JSON on stdin. No jq on this machine, so parse with python3.
f="$(python3 -c '
import sys, json
try:
    d = json.load(sys.stdin)
except Exception:
    print(""); raise SystemExit
ti = d.get("tool_input") or {}
tr = d.get("tool_response") or {}
print(ti.get("file_path") or tr.get("filePath") or "")
' 2>/dev/null)"

[ -n "$f" ] || exit 0
[ -f "$f" ] || exit 0
rel="${f#"$root"/}"

# runtool <pint|prettier|eslint> <args...>
runtool() {
  tool="$1"; shift
  case "$tool" in
    pint)
      if command -v php >/dev/null 2>&1 && [ -x vendor/bin/pint ]; then
        vendor/bin/pint "$@" >/dev/null 2>&1 || true
      elif [ -x vendor/bin/sail ]; then
        vendor/bin/sail pint "$@" >/dev/null 2>&1 || true
      fi
      ;;
    prettier | eslint)
      if command -v node >/dev/null 2>&1 && [ -x "node_modules/.bin/$tool" ]; then
        "node_modules/.bin/$tool" "$@" >/dev/null 2>&1 || true
      elif [ -x vendor/bin/sail ]; then
        vendor/bin/sail npx "$tool" "$@" >/dev/null 2>&1 || true
      fi
      ;;
  esac
}

case "$rel" in
  *.php)
    runtool pint "$rel"
    ;;
  resources/*.js | resources/*.ts | resources/*.vue | resources/*.css | resources/*.json)
    runtool prettier --write "$rel"
    case "$rel" in
      *.js | *.vue) runtool eslint --fix "$rel" ;;
    esac
    ;;
esac

exit 0
