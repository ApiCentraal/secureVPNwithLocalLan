#!/bin/sh
set -eu

mkdir -p /etc/openvpn/clientConfig /var/log/openvpn
if [ ! -f /var/log/openvpn/ovpn.log ]; then
    touch /var/log/openvpn/ovpn.log
fi
chown -R www-data:www-data /var/log/openvpn
chmod 664 /var/log/openvpn/ovpn.log || true

if command -v rsyslogd >/dev/null 2>&1; then
    rsyslogd
fi

if [ "$#" -gt 0 ]; then
    exec "$@"
fi

exec apache2-foreground
