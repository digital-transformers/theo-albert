#!/usr/bin/env bash
set -eu

runtime_dir=/var/web/vd31987/var/supervisor
config=/var/web/vd31987/public_html/theo-pimcore/deploy/level27/supervisord.conf
pidfile="$runtime_dir/supervisord.pid"
socket="$runtime_dir/supervisor.sock"
lock="$runtime_dir/start.lock"

mkdir -p "$runtime_dir"

exec 9>"$lock"
if ! /usr/bin/flock -n 9; then
    exit 0
fi

if /usr/bin/supervisorctl -c "$config" pid >/dev/null 2>&1; then
    exit 0
fi

if test -r "$pidfile"; then
    pid=$(cat "$pidfile" 2>/dev/null || true)
    case "$pid" in
        *[!0-9]*|"") ;;
        *)
            if kill -0 "$pid" 2>/dev/null; then
                exit 0
            fi
            ;;
    esac
fi

rm -f "$pidfile" "$socket"
/usr/bin/supervisord -c "$config" 9>&-
