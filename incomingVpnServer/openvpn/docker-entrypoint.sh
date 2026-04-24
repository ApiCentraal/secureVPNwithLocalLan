#!/bin/sh
set -eu

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

CONFIG_FILE="${OPENVPN_CONFIG:-/etc/openvpn/server.conf}"

if [ ! -f "$CONFIG_FILE" ]; then
    echo "Missing OpenVPN config: $CONFIG_FILE" >&2
    exit 1
fi

exec openvpn --config "$CONFIG_FILE"
