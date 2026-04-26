# Release v0.3.1

This patch release stabilizes the CI and release workflow introduced after `v0.3`.

## Highlights

- Upgrade CodeQL to v4 and use a supported JavaScript and TypeScript scan configuration.
- Opt GitHub Actions into the Node 24 runtime path to avoid Node 20 deprecation warnings.
- Keep Dockerfile linting active while ignoring the intentional `DL3008` package-version warning.
- Document the CI maintenance changes in the changelog and contribution guidance.

## Validation

- Workflow YAML parses correctly.
- Dockerfile lint passes with the configured hadolint ignore rule.
- Release images build successfully as `securevpn-gateway:v0.3.1` and `securevpn-openvpn:v0.3.1`.

## Operational note

If the upstream repository does not have the workflow on its default branch, pull requests can still show `Checks 0` until the maintainer adds the workflow to upstream.