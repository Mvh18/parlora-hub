## Summary

<!--
What changes and why, in a short paragraph. Mention the motivation, the
technical decision or the external constraint if the title does not make it
obvious. Breaking changes and dependencies on other PRs belong here too.
-->

**Main changes:**

1. **<Area>**: <what it does and why>

Closes #<!-- issue number -->
<!-- Part of #<epic> — when the issue is a sub-issue -->

## How to test

<!--
Minimum steps for a reviewer, derived from the issue's acceptance criteria:
1. `composer install && pnpm install` (first time) …
2. …
3. Check that …
-->

---

<!-- Optional sections — delete the ones that do not apply. -->

## Breaking changes

<!--
What breaks and what operators or integrators must update. Must match the
`BREAKING CHANGE:` footer.
-->

## Review guide

<!-- Where to start reading; design decisions worth knowing. -->

## Screenshots

| Before | After |
| ------ | ----- |
|        |       |

---

## Type of change

- [ ] feat — new user-facing capability
- [ ] fix — corrects incorrect behaviour
- [ ] perf — performance only
- [ ] refactor — no behaviour change
- [ ] docs
- [ ] test
- [ ] build — build system or dependencies
- [ ] ci — pipelines and workflows
- [ ] chore — everything else

## Deploy considerations

- [ ] Database migration (`php artisan migrate`)
- [ ] New or changed environment variables (`.env.example` updated)
- [ ] Course content changed (`php artisan content:import`)
- [ ] Queue jobs changed (restart Horizon)
- [ ] Front-end assets changed (rebuild with Vite)
- [ ] Compose changes (recreate the stack)
- [ ] Parlora Tutor contract changed (`contracts/parlora-tutor.openapi.json` updated)

## Checklist

- [ ] PR title follows Conventional Commits (it becomes the squash commit)
- [ ] Tests added or updated; coverage gates pass
- [ ] Local checks pass
- [ ] Self-review of the diff complete
- [ ] Docs updated (README, AGENTS.md, ADR) where behaviour or workflow changed
- [ ] No solutions sent to the browser before the learner answers (props included)
- [ ] No secrets, credentials, personal data, voice recordings, database dumps or local paths in the diff
- [ ] CLA signed (external contributors)
