# Contributing to Secure VPN With Local LAN

## Contribution Guidelines

This project welcomes focused contributions that improve the VPN gateway, the inbound VPN path, the dashboard experience, security hardening, documentation, or container support.

Please keep changes small and well scoped, describe the behavior you changed, and include screenshots or validation notes when the update affects the UI or runtime behavior.

## CI Actions You Can Use

The repository CI is intentionally small and uses a limited set of GitHub Actions. When you extend the workflow, prefer the following actions:

- `actions/checkout@v4` for checking out the repository.
- `shivammathur/setup-php@v2` for installing and configuring PHP in the CI runner.
- `actions/setup-node@v4` for providing Node.js for JavaScript checks.
- `actions/upload-artifact@v4` only when you need to publish logs, screenshots, or other build evidence.
- `github/codeql-action/init@v4` and `github/codeql-action/analyze@v4` for CodeQL security scans.

If you need additional actions, keep the workflow minimal and document the reason in the pull request.

## Security Checks and Code Reviews

Every pull request should include a security check and a human review before merge.

- Make sure the CodeQL security scan passes.
- Keep shell scripts and Dockerfiles free of obvious command injection risks.
- Call out any changes to authentication, VPN routing, or secrets handling in the PR description.
- Ask for a code review and address reviewer feedback before merging.
- Add screenshots or runtime notes when the change affects the dashboard or connection flow.
