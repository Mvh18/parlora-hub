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

Laravel (PHP 8.3+), Inertia.js + React + TypeScript + Tailwind, shadcn/ui,
Filament, PostgreSQL, Valkey + Horizon, Docker Compose. Quality: Pest, Pint,
Larastan; ESLint, Prettier, Vitest.

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

## Conventions

- Every committed artefact is in English (`README.es.md` is the only Spanish
  public document).
- Conventional Commits 1.0.0; branches `<type>/<issue>-<slug>`; pull requests
  are squash-merged and the title becomes the commit. Versions come from
  release-please.
- Every change is tracked by an issue; tests come with every change.
- See [CONTRIBUTING.md](CONTRIBUTING.md), [SECURITY.md](SECURITY.md) and
  [docs/INTEGRATION.md](docs/INTEGRATION.md).
