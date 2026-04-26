#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if ! command -v docker >/dev/null 2>&1; then
  echo "Docker is not installed or not in PATH." >&2
  exit 1
fi

if ! docker compose version >/dev/null 2>&1; then
  echo "Docker Compose (docker compose) is required." >&2
  exit 1
fi

mkdir -p .runtime/openvpn-server .runtime/openvpn-client-config .runtime/openvpn-logs

docker build -f VpnGateway/php/Dockerfile -t securevpn-gateway:local .
docker build -f incomingVpnServer/openvpn/Dockerfile -t securevpn-openvpn:local .

if [[ ! -f .env.local ]]; then
  rand_pass="$(openssl rand -base64 24 | tr -dc 'A-Za-z0-9' | head -c 20)"
  {
    echo "VPN_ADMIN_USERNAME=vpnadmin"
    echo "VPN_ADMIN_PASSWORD=${rand_pass}"
  } > .env.local
  chmod 600 .env.local
  echo "Created .env.local with generated credentials."
fi

if [[ ! -f .runtime/openvpn-server/static.key ]]; then
  docker run --rm securevpn-openvpn:local sh -lc 'openvpn --genkey secret /tmp/static.key && cat /tmp/static.key' > .runtime/openvpn-server/static.key
fi
cp .runtime/openvpn-server/static.key .runtime/openvpn-client-config/static.key
chmod 600 .runtime/openvpn-server/static.key .runtime/openvpn-client-config/static.key

cat > .runtime/openvpn-server/server.conf <<'EOF'
port 1194
proto udp
dev tun
ifconfig 10.8.0.1 10.8.0.2
secret /etc/openvpn/static.key
cipher AES-256-CBC
auth SHA256
persist-key
persist-tun
keepalive 10 60
verb 3
status /var/log/openvpn/status.log
log /var/log/openvpn/server.log
EOF

cat > .runtime/openvpn-client-config/localtest.conf <<'EOF'
dev tun
proto udp
remote securevpn-openvpn-local 1194
ifconfig 10.8.0.2 10.8.0.1
secret /etc/openvpn/clientConfig/static.key
cipher AES-256-CBC
auth SHA256
nobind
persist-key
persist-tun
resolv-retry infinite
verb 3
EOF

docker compose -f docker-compose.local.yml up -d --force-recreate

for _ in $(seq 1 10); do
  if docker exec securevpn-gateway-local /usr/local/bin/vpnadmin.sh start localtest >/dev/null 2>&1; then
    break
  fi
  sleep 1
done

echo
echo "Local stack started."
echo "Gateway: http://localhost:8080"
echo "Use credentials from .env.local"
