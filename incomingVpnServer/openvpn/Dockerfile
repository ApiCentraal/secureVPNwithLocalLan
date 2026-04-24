FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive \
    OPENVPN_CONFIG=/etc/openvpn/server.conf

WORKDIR /etc/openvpn

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    bash ca-certificates curl iproute2 iptables openvpn procps tini \
    && rm -rf /var/lib/apt/lists/*

COPY incomingVpnServer/openvpn/up.sh /etc/openvpn/up.sh
COPY incomingVpnServer/openvpn/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

RUN chmod 755 /etc/openvpn/up.sh /usr/local/bin/docker-entrypoint.sh \
    && mkdir -p /etc/openvpn /var/log/openvpn

VOLUME ["/var/log/openvpn"]

EXPOSE 1194/udp

ENTRYPOINT ["/usr/bin/tini", "--", "/usr/local/bin/docker-entrypoint.sh"]
