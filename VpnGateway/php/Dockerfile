FROM php:8.3-apache

ENV DEBIAN_FRONTEND=noninteractive

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    bash ca-certificates curl iproute2 iptables openvpn procps rsyslog sudo \
    && rm -rf /var/lib/apt/lists/* \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf

COPY VpnGateway/php/*.php /var/www/html/
COPY VpnGateway/php/api/ /var/www/html/api/
COPY VpnGateway/php/class/ /var/www/html/class/
COPY VpnGateway/php/lib/ /var/www/html/lib/
COPY VpnGateway/php/view/ /var/www/html/view/
COPY VpnGateway/bin/vpnadmin.sh /usr/local/bin/vpnadmin.sh
COPY VpnGateway/etc/rsyslog.d/openvpn.conf /etc/rsyslog.d/openvpn.conf
COPY VpnGateway/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod 755 /usr/local/bin/vpnadmin.sh /usr/local/bin/docker-entrypoint.sh \
    && printf 'www-data ALL=(root) NOPASSWD: /usr/local/bin/vpnadmin.sh\n' > /etc/sudoers.d/vpn-gateway \
    && chmod 440 /etc/sudoers.d/vpn-gateway \
    && mkdir -p /etc/openvpn/clientConfig /var/log/openvpn \
    && touch /var/log/openvpn/ovpn.log \
    && chown -R www-data:www-data /var/log/openvpn /var/www/html

VOLUME ["/etc/openvpn/clientConfig", "/var/log/openvpn"]

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1/api/health.php >/dev/null || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
