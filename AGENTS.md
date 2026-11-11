# Parlora Hub — guide for AI coding agents

Parlora Hub is the course platform of the Parlora brand: lessons, practice,
exams with level unlocking, progress and a mistakes notebook. It runs locally
for one learner and is built so it can be published later without a rewrite.

## Brand rules

- **Parlora** is the umbrella brand, not a repository or a product.
- Only standalone products carry a "Parlora X" name: **Parlora Hub** (this
  repository, `parlora-hub`) and **Parlora Tutor** (voice tutor, Python,
  separate repository).
- Sections inside Hub use plain names: Lessons, Practice, Exams, Progress,
  Review, Community. Never create sub-brands such as "Parlora Practice".

## Parlora Tutor boundary

- Hub talks to Tutor **only through its HTTP API** via `ParloraTutorClient`.
  Never import Tutor code or read its database.
- The contract is Tutor's `contracts/openapi.json`; Hub keeps a versioned copy
  at `contracts/parlora-tutor.openapi.json` for CI. Contract changes land in
  Tutor first.
- Integration is optional: with `PARLORA_TUTOR_ENABLED=false` Hub makes no
  calls to Tutor, and calls and AI grading show a clear notice.
- Env vars: `PARLORA_TUTOR_URL`, `PARLORA_TUTOR_API_KEY`,
  `PARLORA_TUTOR_ENABLED`, `PARLORA_TUTOR_WEBHOOK_SECRET`. Shared Docker
  network: `parlora-net`.
- Each system owns its data: Hub owns identity, progress, attempts, the
  mistakes notebook and spaced repetition (FSRS); Tutor owns conversations
  (by `learner_ref`).
- Tutor has no product UI: Hub builds its own call screen with
  `@pipecat-ai/client-js` against the RTVI message schema published in Tutor's
  contract. No Tutor frontend code is shared or imported.

## Stack

Laravel 13 (PHP 8.5, in Docker), Inertia + React + TypeScript (strict) +
Tailwind 4, shadcn/ui, PostgreSQL, Valkey + Horizon, Docker Compose; Filament
arrives in Phase 1. Quality: Pest, Pint, Larastan; Oxlint, Oxfmt and Vitest
through Vite+. See [docs/architecture.md](docs/architecture.md).

## Commands

PHP, Composer and Node run in the Compose `app` service
([ADR 0002](docs/adr/0002-php-environment.md)); there is no PHP on the host.

```sh
docker compose up -d --wait               # start the stack (http://127.0.0.1:8000)
docker compose exec app composer check    # Pint, Larastan, Pest with coverage gates
docker compose exec app pnpm check        # Oxfmt, Oxlint, tsc --noEmit, Vitest
docker compose exec app php artisan wayfinder:generate --with-form  # after route changes
```

Run both checks before every commit; CI runs the same ones under `ci-ok`.
Use pnpm only: never npm or yarn, never `package-lock.json` or `yarn.lock`.

## Rules that must not be broken

- Exercise and exam grading happens **on the server**. Solutions never reach
  the browser before the learner answers — not in API responses and not in
  Inertia page props.
- `level_unlocks` is the single source of truth for access to levels; policies
  enforce it, the UI only reflects it.
- Everything configurable comes from environment variables, read only in
  `config/`.
- Content lives as JSON under `content/` and is imported with
  `php artisan content:import`.
- No secrets, personal data, voice recordings, database dumps or local
  absolute paths in committed files.
- **UI copy comes from `lang/en/*.php`.** Components read it with
  `useTrans()` (`t('home.greeting.morning', { name })`); JSX never contains
  literal text (`react/jsx-no-literals`). Add the key to the right group
  file; the front end must not use a key that does not exist.
- **Style with design tokens only.** Colours, radii, shadows and type come
  from `resources/css/tokens.css` through Tailwind utilities (`bg-surface`,
  `text-ink-muted`, `rounded-card`, `bubble-bl`, …). No hex values, colour
  functions or Tailwind palette colours (`bg-blue-500`) in `resources/js`;
  new colours are added as tokens with a contrast test. Interactive targets
  are at least 44 px.
- **Admin is a flag, not a role system.** `users.is_admin` is never
  mass-assignable and never sent to the browser; grant it only with
  `php artisan hub:make-admin {email}` (`--revoke` removes it). Gates read
  it (today `viewHorizon`).
- `users.learner_ref` is immutable and is the only learner identifier ever
  shared with Parlora Tutor.
- The navigation lists only sections that exist: a phase adds its item to
  `resources/js/lib/navigation.ts` when its pages ship.

## Conventions

- Every committed artefact is in English (`README.es.md` is the only Spanish
  public document).
- Conventional Commits 1.0.0; branches `<type>/<issue>-<slug>`; pull requests
  are squash-merged and the title becomes the commit. Versions come from
  release-please.
- Every change is tracked by an issue; tests come with every change.
- See [CONTRIBUTING.md](CONTRIBUTING.md), [SECURITY.md](SECURITY.md) and
  [docs/INTEGRATION.md](docs/INTEGRATION.md).
