# Security Policy

## Reporting a Vulnerability

Open a private issue on the repository or e-mail the maintainer directly.  
Do **not** publish vulnerability details publicly before a fix is released.

---

## Production Setup Checklist

### 1 — Credentials via environment variables

The web interface reads credentials exclusively from environment variables.  
**Never hard-code passwords in PHP files or in `docker-compose.yml` plain-text.**

| Variable                  | Required                 | Description                                   |
| ------------------------- | ------------------------ | --------------------------------------------- |
| `VPN_ADMIN_USERNAME`      | Yes                      | Login username (defaults to `login` if unset) |
| `VPN_ADMIN_PASSWORD_HASH` | **Strongly recommended** | bcrypt hash of the password                   |
| `VPN_ADMIN_PASSWORD`      | Fallback only            | Plain-text password (dev/test only)           |

#### Generating a bcrypt hash

```bash
php -r "echo password_hash('YourStrongPassword', PASSWORD_BCRYPT, ['cost' => 12]) . PHP_EOL;"
```

Copy the output (starts with `$2y$12$…`) into `VPN_ADMIN_PASSWORD_HASH`.

#### Setting the variables — systemd

Add an override file for the unit that runs the PHP process:

```ini
# /etc/systemd/system/php-vpngateway.service.d/credentials.conf
[Service]
Environment="VPN_ADMIN_USERNAME=myuser"
Environment="VPN_ADMIN_PASSWORD_HASH=$2y$12$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
```

```bash
sudo systemctl daemon-reload
sudo systemctl restart php-vpngateway
```

#### Setting the variables — Docker Compose

Use a `.env` file that is **not committed to the repository** or use Docker secrets:

```yaml
# docker-compose.yml
services:
  vpngateway:
    env_file:
      - .env.production # kept outside version control
```

```
# .env.production  (chmod 600, owner root)
VPN_ADMIN_USERNAME=myuser
VPN_ADMIN_PASSWORD_HASH=$2y$12$xxxx...
```

---

### 2 — Sudoers policy for vpnadmin.sh

The PHP process must be able to call `vpnadmin.sh` via `sudo` without a password,  
but the permission should be scoped as tightly as possible.

```sudoers
# /etc/sudoers.d/vpn-gateway  (chmod 440)
# Replace www-data with the user the PHP process runs as.
www-data ALL=(root) NOPASSWD: /usr/local/bin/vpnadmin.sh
```

Verify with:

```bash
sudo -u www-data sudo /usr/local/bin/vpnadmin.sh status
```

All arguments passed to the binary from PHP are validated against the regex `^[A-Za-z0-9._-]+$` before the shell call.

---

### 3 — HTTPS is required

The session cookie is set with `Secure=true` when the request arrives over HTTPS.  
Without TLS, the session token travels in plain text.

#### Nginx reverse-proxy example (local network)

```nginx
server {
    listen 443 ssl http2;
    server_name vpngw.local;

    ssl_certificate     /etc/ssl/certs/vpngw.crt;
    ssl_certificate_key /etc/ssl/private/vpngw.key;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    root /var/www/vpngateway/php;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include        fastcgi_params;
        fastcgi_pass   unix:/run/php/php8.2-fpm.sock;
        fastcgi_param  SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    # Block direct browser access to internal directories
    location ~ ^/(lib|class)/ {
        deny all;
        return 404;
    }
}
```

For a self-signed certificate on a local-only gateway:

```bash
openssl req -x509 -nodes -days 3650 -newkey rsa:4096 \
  -keyout /etc/ssl/private/vpngw.key \
  -out /etc/ssl/certs/vpngw.crt \
  -subj "/CN=vpngw.local"
```

---

### 4 — PHP configuration recommendations

```ini
; php.ini or a conf.d drop-in
expose_php          = Off
display_errors      = Off
log_errors          = On
error_log           = /var/log/php/vpngateway.log
session.use_strict_mode  = 1
session.cookie_httponly  = 1
session.cookie_samesite  = Strict
```

---

### 5 — File permissions

```bash
# PHP source files should not be world-readable
find /var/www/vpngateway -type f -exec chmod 640 {} \;
find /var/www/vpngateway -type d -exec chmod 750 {} \;
chown -R root:www-data /var/www/vpngateway

# The vpnadmin binary
chmod 750 /usr/local/bin/vpnadmin.sh
chown root:root /usr/local/bin/vpnadmin.sh
```

---

### 6 — Brute-force lockout (built-in)

The authentication layer automatically locks out an IP/session after **5 failed attempts**  
within a **5-minute window** for **5 minutes**.  
Constants are defined in `lib/Auth.php` (`MAX_LOGIN_ATTEMPTS`, `LOGIN_WINDOW_SECONDS`, `LOCKOUT_SECONDS`).

---

## Known Limitations

- The web interface is intended for a **trusted local network** only; do not expose port 80/443 to the public internet without an additional authentication layer (e.g., VPN-in-VPN or client certificate).
- Log tailing reads directly from `/var/log/openvpn/ovpn.log`; ensure that file is not world-readable on the host.

---

## Debian package security notes

When installed via Debian package, apply these checks after `dpkg -i`:

1. Verify scoped sudoers policy:

```bash
sudo visudo -cf /etc/sudoers.d/vpn-gateway
cat /etc/sudoers.d/vpn-gateway
```

Expected scope:

```text
www-data ALL=(root) NOPASSWD: /usr/local/bin/vpnadmin.sh
```

2. Verify runtime path permissions:

```bash
ls -ld /etc/openvpn/clientConfig
ls -l /var/log/openvpn/ovpn.log
```

3. Re-validate credentials are not default values (`login` / `pass`) in production by setting environment variables for your web service unit.

4. If package is purged, confirm whether local OpenVPN profile files and logs should be manually removed according to your retention policy.
