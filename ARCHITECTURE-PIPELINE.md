# Architecture Pipeline: Secure VPN with Local LAN

This document describes the complete architecture and data flows of the **Secure VPN with Local LAN** solution. The system is designed to provide a secure bridge between roaming devices, a local network (home/work), and a secured, anonymized internet connection.

## 1. Conceptual Overview

The system combines two functions:
1.  **Incoming VPN**: Allows devices from the outside to securely connect to the local LAN.
2.  **Outgoing VPN (Gateway)**: Sends all traffic from the local network (or specific clients) through an anonymized VPN tunnel.

```mermaid
graph LR
    subgraph Roaming["Roaming Environment"]
        Device["Roaming Device"]
    end

    subgraph LocalNetwork["Local Network"]
        direction TB
        Server1["Incoming VPN Server"]
        Gateway["VPN Gateway"]
        LAN_Devices["Local Devices"]
    end

    subgraph Internet["Secured Internet"]
        VpnProvider["VPN Provider Node"]
    end

    Device -- "Encrypted Tunnel" --> Server1
    Server1 -- "Internal Traffic" --> LAN_Devices
    Server1 -- "External Traffic" --> Gateway
    LAN_Devices -- "Default Gateway" --> Gateway
    Gateway -- "Encrypted Tunnel" --> VpnProvider
    VpnProvider -- "Anonymous" --> WWW["Public Web"]
```

---

## 2. Components & Responsibilities

### A. Incoming VPN Server (`incomingVpnServer`)
This is the entry point for roaming clients.
-   **Software**: OpenVPN or Wireguard (e.g., via PiVPN).
-   **Function**: Receives traffic from clients and forwards it based on routing rules.
-   **Magic**: Uses `up.sh` to create a specific routing table (Table 11) that forces all traffic from VPN clients to use the `VpnGateway` as the default gateway.

### B. VPN Gateway (`VpnGateway`)
The intelligence of the setup.
-   **Routing**: Acts as the default gateway for both incoming VPN clients and selected local devices (via DHCP/Dnsmasq).
-   **Web Interface**: A modern PHP dashboard for monitoring and switching VPN profiles.
-   **Automation**: `vpnadmin.sh` script for managing `iptables`, `ip rules`, and OpenVPN clients.

### C. Home Assistant Integration
Enables visualization and control from the smart home ecosystem.
-   **Sensors**: `binary_sensor` for status, IP address, and current location.
-   **Control**: `input_select` and `shell_command` to switch locations via SSH.

---

## 3. Visualization: Data & Routing Flow

The pipeline below shows how a packet from a roaming device travels to the internet through the local architecture.

```mermaid
sequenceDiagram
    participant D as Roaming Device
    participant S as Incoming VPN Server
    participant G as VPN Gateway
    participant P as VPN Provider
    participant W as Internet (WWW)

    Note over D,W: Packet Journey (Routing to Internet)

    D->>S: 1. Encrypted Request (to 8.8.8.8)
    Note right of S: Receives on tun0 (10.8.0.x)
    S->>S: 2. Check IP Rule (Table 11)
    S->>G: 3. Forward to Gateway (192.168.x.x)
    Note right of G: Receives on eth0
    G->>G: 4. NAT/Masquerade & Tunneling
    G->>P: 5. Encrypted via Provider Tunnel (tun0)
    P->>W: 6. Request arrives via Provider IP
    W->>P: 7. Response
    P->>G: 8. Encrypted Response
    G->>S: 9. Back to Incoming Server
    S->>D: 10. Back to Device
```

---

## 4. Software Architecture (VpnGateway)

The PHP dashboard follows a modern, decoupled architecture:

```mermaid
graph TD
    UI["Browser UI"]
    JS["dashboard.js"]
    API["PHP API"]
    Service["VpnService Class"]
    Bin["vpnadmin.sh"]
    Linux["Linux OS"]

    UI <--> JS
    JS <--> API
    API <--> Service
    Service <--> Bin
    Bin <--> Linux
```

-   **Security**: CSRF tokens, Bcrypt password hashing, whitelisting of arguments, and scoping via `sudoers.d`.
-   **Real-time**: The UI polls the status and logs every few seconds for a "live" experience without page refreshes.

---

## 5. Installation & Use

See individual documents for details:
-   [README.md](./README.md): General setup and iptables rules.
-   [SECURITY.md](./SECURITY.md): Essential security steps (Credentials, Sudo, HTTPS).
-   [HomeAssistant.md](./HomeAssistant.md): Integration with your smart home.
-   [dnsmasq.md](./dnsmasq.md): How to force specific LAN devices through the VPN.

---
