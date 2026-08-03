# Architectuur Pijplijn: Secure VPN with Local LAN

Dit document beschrijft de volledige architectuur en datastromen van de **Secure VPN with Local LAN** oplossing. Het systeem is ontworpen om een veilige brug te slaan tussen roaming apparaten, een lokaal netwerk (thuis/werk), en een beveiligde, geanonimiseerde internetverbinding.

## 1. Conceptueel Overzicht

Het systeem combineert twee functies:
1.  **Inkomende VPN**: Hiermee kunnen apparaten van buitenaf veilig verbinding maken met het lokale LAN.
2.  **Uitgaande VPN (Gateway)**: Verstuurt al het verkeer van het lokale netwerk (of specifieke clients) door een geanonimiseerde VPN-tunnel.

```mermaid
graph LR
    subgraph Roaming["Roaming Omgeving"]
        Device["Roaming Device"]
    end

    subgraph LocalNetwork["Lokaal Netwerk"]
        direction TB
        Server1["Inkomende VPN Server"]
        Gateway["VPN Gateway"]
        LAN_Devices["Lokale Devices"]
    end

    subgraph Internet["Beveiligd Internet"]
        VpnProvider["VPN Provider Node"]
    end

    Device -- "Gecodeerde Tunnel" --> Server1
    Server1 -- "Intern Verkeer" --> LAN_Devices
    Server1 -- "Extern Verkeer" --> Gateway
    LAN_Devices -- "Standaard Gateway" --> Gateway
    Gateway -- "Gecodeerde Tunnel" --> VpnProvider
    VpnProvider -- "Anoniem" --> WWW["Publiek Web"]
```

---

## 2. Componenten & Verantwoordelijkheden

### A. Inkomende VPN Server (`incomingVpnServer`)
Dit is het toegangspunt voor roaming clients.
-   **Software**: OpenVPN of Wireguard (bijv. via PiVPN).
-   **Functie**: Ontvangt verkeer van clients en stuurt dit door op basis van routeringsregels.
-   **Magie**: Gebruikt `up.sh` om een specifieke routeringstabel (Tabel 11) aan te maken die al het verkeer van VPN-clients dwingt om de `VpnGateway` als default gateway te gebruiken.

### B. VPN Gateway (`VpnGateway`)
De intelligentie van de setup.
-   **Routering**: Fungeert als de default gateway voor zowel de inkomende VPN-clients als geselecteerde lokale apparaten (via DHCP/Dnsmasq).
-   **Web Interface**: Een modern PHP-dashboard voor het monitoren en wisselen van VPN-profielen.
-   **Automatisering**: `vpnadmin.sh` script voor het beheren van `iptables`, `ip rules` en OpenVPN-clients.

### C. Home Assistant Integratie
Maakt visualisatie en bediening mogelijk vanuit het smart home ecosysteem.
-   **Sensoren**: `binary_sensor` voor status, IP-adres en huidige locatie.
-   **Bediening**: `input_select` en `shell_command` om van locatie te wisselen via SSH.

---

## 3. Visualisatie: Data & Routing Flow

De onderstaande pijplijn toont hoe een pakketje van een roaming device naar het internet reist via de lokale architectuur.

```mermaid
sequenceDiagram
    participant D as Roaming Device
    participant S as Incoming VPN Server
    participant G as VPN Gateway
    participant P as VPN Provider
    participant W as Internet (WWW)

    Note over D,W: Pakketreis (Routing naar Internet)

    D->>S: 1. Versleutelde Request (naar 8.8.8.8)
    Note right of S: Ontvangt op tun0 (10.8.0.x)
    S->>S: 2. Check IP Rule (Table 11)
    S->>G: 3. Forward naar Gateway (192.168.x.x)
    Note right of G: Ontvangt op eth0
    G->>G: 4. NAT/Masquerade & Tunneling
    G->>P: 5. Versleuteld via Provider Tunnel (tun0)
    P->>W: 6. Request komt aan via Provider IP
    W->>P: 7. Response
    P->>G: 8. Versleutelde Response
    G->>S: 9. Terug naar Incoming Server
    S->>D: 10. Terug naar Device
```

---

## 4. Software Architectuur (VpnGateway)

Het PHP-dashboard volgt een moderne, gescheiden architectuur:

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

-   **Veiligheid**: CSRF-tokens, Bcrypt wachtwoord-hashing, whitelisting van argumenten en scoping via `sudoers.d`.
-   **Real-time**: De UI pollt de status en logs elke paar seconden voor een "live" ervaring zonder pagina-refreshes.

---

## 5. Installatie & Gebruik

Zie de individuele documenten voor details:
-   [README.md](./README.md): Algemene setup en iptables regels.
-   [SECURITY.md](./SECURITY.md): Essentiële beveiligingsstappen (Credentials, Sudo, HTTPS).
-   [HomeAssistant.md](./HomeAssistant.md): Integratie met je smart home.
-   [dnsmasq.md](./dnsmasq.md): Hoe je specifieke LAN-devices dwingt over de VPN te gaan.

---

