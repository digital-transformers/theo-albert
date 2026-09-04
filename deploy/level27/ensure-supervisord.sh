#!/usr/bin/env bash
set -eu

runtime_dir=/var/web/vd31987/var/supervisor
config=/var/web/vd31987/public_html/theo-pimcore/deploy/level27/supervisord.conf
pidfile="$runtime_dir/supervisord.pid"
socket="$runtime_dir/supervisor.sock"
lock="$runtime_dir/start.lock"

mkdir -p "$runtime_dir"

exec /usr/bin/flock -n "$lock" /bin/bash -c '
    if /usr/bin/supervisorctl -c "$1" pid >/dev/null 2>&1; then
        exit 0
    fi

    if test -r "$2"; then
        pid=$(cat "$2" 2>/dev/null || true)
        case "$pid" in
            *[!0-9]*|"") ;;
            *)
                if kill -0 "$pid" 2>/dev/null; then
                    exit 0
                fi
                ;;
        esac
    fi

    rm -f "$2" "$3"
    exec /usr/bin/supervisord -c "$1"
' ensure-supervisord "$config" "$pidfile" "$socket"
