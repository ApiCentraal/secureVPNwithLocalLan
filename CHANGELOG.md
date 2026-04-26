# Changelog

## Unreleased

Future changes will be listed here.

## v0.3.1 - 2026-04-26

This patch release stabilizes the CI security and container validation workflow after the initial v0.3 release.

### CI and security maintenance

- Updated the security scan to use CodeQL v4 with a supported JavaScript and TypeScript configuration.
- Opted GitHub Actions into the Node 24 runtime path to avoid current Node 20 deprecation warnings.
- Adjusted Dockerfile linting so hadolint ignores the repository's intentional package-version pinning rule.

## v0.3 - 2026-04-24

This release summarizes the changes that were made to modernize, secure, containerize, and document the VPN gateway project.

### Summary

- Security and session handling were centralized and hardened.
- VPN command execution was isolated in a single service layer.
- The dashboard was rebuilt into smaller reusable UI components and made WAN-aware.
- JSON API endpoints were added for action, status, logs, and health checks.
- Production documentation, screenshots, and smoke testing were added.
- Docker support was added for the VPN gateway and the inbound VPN server.

### Security and session hardening

- Replaced the old monolithic login flow with a shared `Auth` helper that handles login checks, session lifecycle, and lockout logic.
- Added a `Csrf` helper so all state-changing requests use token validation instead of relying on implicit browser state.
- Introduced a secure bootstrap layer that configures session cookies, strict session mode, and shared service loading in one place.
- Hardened login and logout handling so they require POST requests, validate CSRF tokens, and clear sessions consistently.
- Added brute-force protection with a five-attempt lockout window to slow down repeated credential guessing.
- Updated the legacy `Member` class so it delegates authentication to the shared auth layer instead of relying on hardcoded credentials.

### VPN command service

- Centralized all shell execution in `VpnService` so the UI and API no longer call shell commands directly.
- Added argument whitelisting and command exit-code enforcement so invalid or failing VPN operations are rejected cleanly.
- Normalized VPN status parsing so the service can distinguish between active VPN, local routing, stopped state, and degraded connectivity.
- Added log tail retrieval and connection listing through the service layer instead of direct shell calls in views.
- Extended the service to surface WAN reachability and public IP information so the UI can show online, WAN-down, and LAN-only states clearly.

### API layer

- Added a CSRF-protected action endpoint for applying VPN selections from the dashboard.
- Added a status endpoint that returns the current dashboard state as JSON for polling clients.
- Added a logs endpoint for live log tail polling.
- Added a public health endpoint that can be used by monitoring tools to check the VPN binary, log accessibility, and PHP runtime availability.
- Kept the legacy endpoints in place but rewired them to use the new shared service and safer error handling.

### Dashboard redesign

- Rebuilt the authenticated dashboard into reusable PHP components for the top bar, status cards, connection panel, and log panel.
- Reworked the dashboard CSS into a single visual system with cards, tones, panels, and responsive spacing.
- Added a dedicated JavaScript dashboard controller that handles status polling, log polling, AJAX form submission, and live UI updates.
- Made the dashboard WAN-aware so it can show whether the internet uplink is online, unreachable, or only local routing is available.
- Added a persistent warning banner when the VPN is active but the WAN uplink is down.
- Updated the route switcher terminology so local fallback is shown as LAN-only mode instead of a vague local route label.
- Added a current-connection summary, WAN uplink card, and updated route labels in the status panel.
- Updated the login screen to match the new visual language and to show inline validation and session error messages.

### Documentation and screenshots

- Updated the README with current UI screenshots for the login screen and dashboard.
- Added a centered login screenshot to better document the current visual layout.
- Added production security guidance in `SECURITY.md` for credentials, sudoers, HTTPS, PHP settings, and file permissions.
- Added an executable smoke test script that exercises login, health, status, and logs end to end.
- Expanded the architecture documentation so the role of the incoming VPN server and the VPN gateway remains explicit.

### Containerization

- Added Dockerfiles for the PHP VPN gateway and the inbound OpenVPN server.
- Added lightweight container entrypoints so each service can start in a container without relying on host-specific service managers.
- Added a containerization plan file under `.azure/` to describe the intended container setup and service boundaries.
- Added `.dockerignore` so build contexts stay small and do not include unnecessary repository content.
- Adjusted `vpnadmin.sh` so it can run in a container-friendly environment with a systemd fallback.

### Visual assets

- Added repository screenshots for the current login and dashboard UI under `images/`.
- Added a centered login capture to document the current visual composition more clearly.

### Repository maintenance

- Added a repository-level changelog so the scope and evolution of the project are documented in one place.
- Linked the changelog from the README so it is easy to find from the main project overview.

### CI and review governance

- Added a CodeQL security scan for PHP and JavaScript.
- Added Dockerfile linting for the container images.
- Documented allowed CI actions, security checks, and review expectations in `CONTRIBUTING.md`.
- Added a pull request template with security and validation checkboxes.

## Notes

- The project still represents a two-machine design: one machine for incoming VPN access and one machine for outbound VPN gateway control.
- The dashboard now shows both VPN routing and WAN state, so it remains useful even when the internet uplink is unavailable.
- Containerization is intended to package the services cleanly; the VPN features still require the host kernel networking capabilities they depend on.
