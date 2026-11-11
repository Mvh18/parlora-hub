# Parlora Hub

**A self-hosted English course platform: lessons, practice, exams and progress.**
Study CEFR levels A1 to C1 with server-graded exercises, level exams that unlock
the next module, and a mistakes notebook that shows where you fail most.

> 🚧 **Work in progress.** Phase 0 is done: the skeleton runs locally with
> sign-in, the app shell and an empty Home. Lessons, practice and exams come
> in the next phases. [Leer en español](README.es.md).

## Principles

- **Runs on your machine** — built for a single learner on a local setup, ready
  to be published later without a rewrite.
- **Graded on the server** — answers are checked by the backend; solutions
  never reach the browser before you answer.
- **Content as code** — the syllabus lives in versioned JSON files and is
  imported with one command.
- **Works without AI** — voice calls and AI grading of speaking and writing
  come from the optional
  [Parlora Tutor](https://github.com/Mvh18/parlora-tutor) service. Without it,
  Hub keeps working, spaced-repetition review included.

## Quick start

You need [Git](https://git-scm.com/) and
[Docker Desktop](https://www.docker.com/products/docker-desktop/) (or Docker
Engine with Compose v2). PHP, Composer and Node run inside the project's
container, so nothing else is installed on your machine
([ADR 0002](docs/adr/0002-php-environment.md)).

```sh
git clone https://github.com/Mvh18/parlora-hub.git
cd parlora-hub
docker compose up -d --wait
```

The first start builds the image, installs the Composer and pnpm packages,
creates `.env` with an app key and migrates the database; it takes a few
minutes. Then open **http://127.0.0.1:8000**, register an account and sign
in.

| Service                 | URL                           |
| ----------------------- | ----------------------------- |
| Parlora Hub             | http://127.0.0.1:8000         |
| Mailpit (captured mail) | http://127.0.0.1:8025         |
| Horizon (queues, admin) | http://127.0.0.1:8000/horizon |

To open Horizon, make your account an administrator; once your account
exists, you can turn sign-up off by setting `HUB_REGISTRATION_ENABLED=false`
in `.env`:

```sh
docker compose exec app php artisan hub:make-admin you@example.com
```

`docker compose stop` stops the stack and `docker compose up -d --wait`
starts it again. If a port is already in use on your machine (for example a
local PostgreSQL on 5432), create `.env` before the first start with
`cp .env.example .env` and change `APP_PORT`, `VITE_PORT` or the matching
`FORWARD_*` variable. Development checks, hooks and conventions are in [CONTRIBUTING.md](CONTRIBUTING.md); the system is described in
[docs/architecture.md](docs/architecture.md).

## License

Parlora Hub is **source-available** under the
[PolyForm Noncommercial License 1.0.0](LICENSE): you may use, study, modify and
share it for any **non-commercial** purpose (personal study, education,
research, non-profit organisations). It is not "open source" in the OSI sense,
because commercial use is not permitted under this licence.

For commercial licensing, contact **team.parlora@gmail.com**.

Copyright © Marlon Vásquez Herrera.
