# Upstream CI Note

The pull request in `Bram-diederik/secureVPNwithLocalLan` currently shows no normal CI checks because the base repository does not yet have a workflow under `.github/workflows/` on its default branch.

## What the maintainer needs to do

1. Add the CI workflow from this fork to `Bram-diederik/main`.
2. Ensure GitHub Actions remains enabled for the upstream repository.
3. Push a small follow-up commit to the pull request branch or re-run the workflow after the base branch contains the workflow.

## Why this matters

GitHub evaluates `pull_request` workflows from the base repository's default branch. If the workflow exists only on the fork, the pull request page can show `Checks 0` even though the branch itself contains a valid workflow.

## Recommended follow-up

- Keep `.github/workflows/ci.yml` on the upstream default branch.
- Use `workflow_dispatch` for manual runs when validating infrastructure changes.
- Avoid `pull_request_target` unless there is a clear need and a security review.