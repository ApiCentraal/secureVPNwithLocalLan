#!/usr/bin/env bash
set -euo pipefail

if ! docker ps --format '{{.Names}}' | grep -qx 'securevpn-gateway-local'; then
  echo "Gateway container is not running." >&2
  exit 1
fi

if ! docker ps --format '{{.Names}}' | grep -qx 'securevpn-openvpn-local'; then
  echo "OpenVPN server container is not running." >&2
  exit 1
fi

status_output="$(docker exec securevpn-gateway-local /usr/local/bin/vpnadmin.sh status)"

echo "$status_output"

echo "$status_output" | grep -q 'ActiveState=active' || {
  echo "VPN is not active." >&2
  exit 1
}

docker exec securevpn-gateway-local /bin/sh -lc 'ip -4 addr show tun0' >/dev/null

echo "VPN verification passed: tunnel is active and tun0 is present."
