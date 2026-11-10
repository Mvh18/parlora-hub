# Contributing to Parlora Hub

Thanks for your interest in Parlora Hub. This guide explains how work is
planned, how to set up the project, and what a pull request needs before it
can be merged.

Parlora Hub is **source-available under the PolyForm Noncommercial License
1.0.0**, not open source. Contributions are accepted under a
[Contributor Licence Agreement](CLA.md) (see [below](#contributor-licence-agreement)).

## Before you start

- **Open or pick an issue first.** Every change is tracked by an issue, and
  the issue is the specification: it carries the context and the acceptance
  criteria. Use the issue forms (feature, bug, content, chore, spike).
- **Security problems** go through private vulnerability reporting, never a
  public issue: see [SECURITY.md](SECURITY.md).
- **Questions** go through the channels in [Getting help](#getting-help), not
  in issues.
- Comment on the issue before starting larger work, so effort is not
  duplicated.

## Getting help

Parlora Hub is maintained by one person in their spare time. Support is
best-effort.

| You want to…                      | Go to                                                                         |
| --------------------------------- | ----------------------------------------------------------------------------- |
| Report a bug                      | [New issue → Bug](https://github.com/Mvh18/parlora-hub/issues/new/choose)     |
| Report an error in course content | [New issue → Content](https://github.com/Mvh18/parlora-hub/issues/new/choose) |
| Propose a feature                 | [New issue → Feature](https://github.com/Mvh18/parlora-hub/issues/new/choose) |
| Report a security vulnerability   | Private vulnerability reporting — see [SECURITY.md](SECURITY.md)              |
| Report a Code of Conduct concern  | team.parlora@gmail.com — see [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)         |
| Ask about commercial licensing    | team.parlora@gmail.com                                                        |

Before opening an issue, search the existing ones; a 👍 reaction on an
existing issue is more useful than a duplicate. In a report, include:

- what you tried, what happened and what you expected;
- your environment: OS, browser, Docker Desktop version, PHP version, and the
  Parlora Hub version or commit;
- logs with personal data removed. Never attach voice recordings, passwords or
  other learners' data.

## Development setup

The Laravel skeleton is still being built; this section grows with it.
Today you need git and [pre-commit](https://pre-commit.com/), installed for
example with [uv](https://docs.astral.sh/uv/):

```sh
uv tool install pre-commit
pre-commit install --install-hooks   # secret scanning, hygiene and commit-msg checks
pre-commit run --all-files
```

### Local runtime

PHP, Composer and Node run in the Compose `app` service; PostgreSQL, Valkey
and Mailpit run next to it. You need Docker Desktop (or Docker Engine with
Compose v2).

```sh
docker compose up -d --wait          # first start installs packages, creates .env and migrates
docker compose exec app composer check
docker compose exec app pnpm check
```

| Service                 | URL on the host               |
| ----------------------- | ----------------------------- |
| Parlora Hub             | http://127.0.0.1:8000         |
| Horizon (queues)        | http://127.0.0.1:8000/horizon |
| Mailpit (captured mail) | http://127.0.0.1:8025         |
| Vite dev server         | http://127.0.0.1:5173         |

`composer dev` (the `app` command) runs the web server, Horizon, the log tail
and Vite together; follow them with `docker compose logs -f app`. Every port
is published on `127.0.0.1` only. If a port is taken (a native PostgreSQL on
5432, for example), change `APP_PORT`, `VITE_PORT` or the matching
`FORWARD_*` variable in `.env`. `docker compose down -v` removes the database
and the package volumes.

Horizon is admin-only. Register an account, then grant yourself the flag:

```sh
docker compose exec app php artisan hub:make-admin you@example.com  # --revoke removes it
```

Set `HUB_REGISTRATION_ENABLED=false` in `.env` once your account exists; the
sign-up page then returns 404.

### JavaScript packages: pnpm only

PHP and Node run inside the project's Docker image
([ADR 0002](docs/adr/0002-php-environment.md)), so you do not need Node on the
host. The JavaScript package manager is **pnpm**, pinned through the
`packageManager` field of `package.json` and enabled with corepack. npm and
yarn are not used, and `package-lock.json` or `yarn.lock` must never be
committed.

Two settings in `pnpm-workspace.yaml` protect the supply chain:

- `minimumReleaseAge: 10080`: pnpm only installs versions that were published
  at least seven days ago, which leaves time for a compromised release to be
  found and pulled. A fresh resolution picks the newest version that is old
  enough. An existing lockfile with a newer entry is rejected; do not relax
  the setting to get around that.
- `strictDepBuilds: true`: dependencies may not run install or build scripts
  unless they are on the allow-list, and an unapproved script fails the
  install. The starter kit needs no build scripts (its native Rolldown and
  Tailwind binaries ship as prebuilt optional packages), so the allow-list is
  empty. Adding a package to it needs a reason in the pull request.

Install with the lockfile as it is, the same way CI does:

```sh
pnpm install --frozen-lockfile
```

`pnpm install` warns about unmet `vite` peer dependencies. That is expected:
the starter kit replaces `vite` with the Vite+ core package
(`npm:@voidzero-dev/vite-plus-core`), and the Vite plugins still declare
`vite` 7 or 8 as their peer.

### JavaScript checks

The starter kit ships [Vite+](https://viteplus.dev/), which bundles the
JavaScript quality tools: Oxfmt (Prettier-compatible formatter), Oxlint
(typescript-eslint and React hooks rules, type-aware) and Vitest. All of them
read their settings from `vite.config.ts`. TypeScript runs in `strict` mode.

```sh
pnpm check       # Oxfmt check, Oxlint, tsc --noEmit, Vitest
pnpm check:fix   # apply formatting and auto-fixable lint fixes
pnpm test        # Vitest only
```

Component tests live next to the component as `*.test.tsx` and use React
Testing Library with jsdom; import the test API from `vite-plus/test`.

The pre-commit configuration runs Oxfmt and Oxlint on staged files through
`pnpm exec` on the host, so run `pnpm install` on the host before committing.
The Pint hook runs in the `app` container, so keep it running.

## How we work

1. **Branch** from `main` as `<type>/<issue>-<slug>`, for example
   `feat/42-level-map`.
2. **Test first.** Grading, unlock rules and policies are written test-first;
   every change includes its tests.
3. **Commit** with [Conventional Commits](https://www.conventionalcommits.org/):
   `<type>(<scope>): <description>`, imperative and lowercase, with a body that
   explains _why_. Types: `feat`, `fix`, `perf`, `refactor`, `docs`, `test`,
   `build`, `ci`, `style`, `chore`, `revert`. Scopes: `backend`, `web`,
   `content`, `admin`, `exams`, `integration`, `infra`, `ci`, `docs`, `deps`,
   `repo`. The commit-msg hook checks this.
4. **Open a pull request** using the template. The title follows the same
   format (it becomes the commit on `main`, because pull requests are
   squash-merged) and the body links the issue with `Closes #N`.
5. **CI must be green**: the single required check is `ci-ok`.

Versions and the changelog are generated by release-please from the commit
history; never bump versions by hand.

## Privacy and security rules

- Never commit personal data: learner accounts, answers, voice recordings or
  database dumps. Test audio must be synthetic and live under a
  `fixtures/synthetic/` folder.
- Never commit secrets, credentials or local absolute paths. The gitleaks hook
  and CI scan for secrets.
- Exercise and exam solutions must never be sent to the browser before the
  learner answers — not even inside page props.
- New dependencies must have a licence compatible with the project: no GPL or
  AGPL code. CI checks every dependency change.

## Course content

Lessons, exercises and exams live as JSON under `content/`. When you fix or
add content, cite your source if it is not your own work, and never paste
copyrighted material (textbooks, exam papers) you are not allowed to share.

## Contributor licence agreement

Before your first pull request can be merged you must accept the
[Contributor Licence Agreement](CLA.md). It lets the copyright holder keep
licensing Parlora Hub, including under other terms, with your contribution in
it; you keep the copyright of your work. You accept it once, and it covers all
your future contributions.

## Code of conduct

Everyone taking part in this project follows the
[Code of Conduct](CODE_OF_CONDUCT.md).
