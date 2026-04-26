# Release v0.3.1

This patch release stabilizes CI, improves release governance, and adds a reproducible local VPN runtime workflow after `v0.3`.

## Scope

This release note is cumulative for all changes delivered around `v0.3.1`, including follow-up commits that completed local runtime automation and security-safe developer guidance.

## Highlights

- Upgraded CodeQL to v4 and switched to a supported JavaScript/TypeScript configuration.
- Opted GitHub Actions into the Node 24 runtime path to avoid Node 20 deprecation warnings.
- Kept Dockerfile linting active while ignoring the intentional `DL3008` package-version warning.
- Added maintainers guidance for upstream CI behavior when pull requests show `Checks 0`.
- Added a full local Docker Compose workflow for gateway and inbound OpenVPN runtime.
- Added end-to-end local VPN verification scripts and documented expected tunnel status checks.
- Added ignore rules to prevent accidental commits of generated runtime secrets and local env files.

## Local Runtime Additions

- Added `docker-compose.local.yml` for a reproducible two-container local setup.
- Added `scripts/setup-local-vpn.sh` to:
  - generate local runtime files under `.runtime/`
  - generate local credentials in `.env.local`
  - build images and start containers
  - activate the `localtest` tunnel profile
- Added `scripts/verify-local-vpn.sh` to validate:
  - `ActiveState=active`
  - presence of `tun0` in the gateway container

## Security and Secret Handling

- Added `.gitignore` entries for local runtime and secret files:
  - `.runtime/`
  - `.env` and `.env.*`
  - key/cert artifacts (`*.key`, `*.pem`, `*.p12`, `*.ovpn`)
- Updated `.dockerignore` to exclude local runtime and secret-oriented files from build context.
- Updated README guidance for developers and operators to avoid plain-text credential leaks.

## Validation

- Workflow YAML parses correctly.
- Dockerfile lint passes with the configured hadolint rule.
- Release images build successfully as `securevpn-gateway:v0.3.1` and `securevpn-openvpn:v0.3.1`.
- Local runtime verification passes with:
  - active OpenVPN state in gateway status output
  - active tunnel profile (`localtest`)
  - `tun0` present on both VPN sides during runtime checks

## Operational note

If the upstream repository does not have the workflow on its default branch, pull requests can still show `Checks 0` until the maintainer adds the workflow to upstream.

## Included Follow-up Commits

- `1d39112` docs: add release and upstream CI notes
- `bb5ce95` feat(local-dev): add reproducible local VPN compose workflow

## Packaging Progress (debianpackage branch)

- Added initial Debian package skeleton for `securevpn-gateway`.
- Added maintainer lifecycle scripts for idempotent runtime setup and safe purge behavior.
- Added package-oriented installation and security guidance in README and SECURITY policy documentation.
