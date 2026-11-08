# ADR 0002: PHP runs in Docker, with the repository on the Windows file system

- **Status:** Accepted
- **Date:** 2026-11-07
- **Issue:** #3 (spike S1, part of #2)

## Context

Parlora Hub is developed on a Windows 11 machine (16 GB RAM, Docker Desktop
with the WSL2 backend) that also runs Parlora Tutor. That machine is for
development and testing only: Hub is expected to run on a Linux server, and
the candidate host (Oracle Always Free) is ARM64. The decision of where PHP
runs drives `compose.yaml`, the quick start in the README, and how closely CI
mirrors development.

The spike generated a throwaway app from the official React starter kit and
compared three options. Each figure is the median of three runs of the kit's
40 tests, of the first load of `/login` after `php artisan optimize:clear`,
and of a Vite hot update (time from saving a `.tsx` file to Vite reporting the
update).

| Option                                                                     | `php artisan test` | First load | Vite hot update   |
| -------------------------------------------------------------------------- | ------------------ | ---------- | ----------------- |
| Native PHP on Windows (Herd Lite 8.5)                                      | 3.7 s              | 166 ms     | ~370 ms (noisy)   |
| Docker, repository on NTFS, everything bind-mounted                        | 40 s               | 0.3–7.8 s  | not measured      |
| Docker, repository on NTFS, `vendor/` and `node_modules/` in named volumes | 8.3 s              | 625 ms     | ~330 ms (polling) |
| Docker, repository inside the WSL2 file system                             | 3.9 s              | 10 ms      | 78 ms             |

Findings:

- Most of the cost of the NTFS bind mount comes from reading `vendor/`: just
  booting `php artisan` takes about 8 s when `vendor/` is bind-mounted, in any
  PHP image. Moving `vendor/` and `node_modules/` into named volumes cuts the
  test suite from 40 s to 8 s.
- File-change events do not cross the NTFS bind mount, so Vite inside a
  container needs polling (`CHOKIDAR_USEPOLLING=true`, 100 ms interval).
- The kit's Wayfinder Vite plugin runs `php artisan wayfinder:generate`, so
  Vite has to run where PHP runs.
- Herd Lite for Windows ships without `pdo_pgsql` and `pcov`, so it cannot run
  the PostgreSQL feature tests or measure coverage. Its PHP 8.4 build is
  8.4.0, and Pest 5.3 needs `symfony/process` 8.1, which needs PHP 8.4.1 or
  later.
- The Laravel installer 5.15 writes `APP_URL=http://localhost:8000:8000`,
  which breaks `artisan`; the value has to be fixed by hand.
- With PHP and Vite running in one container, the container uses about 1.3 GB
  of RAM. The WSL2 VM used about 6 GB in total and left 2.6 GB of the host's
  16 GB free. Parlora Tutor was not running during this measurement.

The repository inside WSL2 is the fastest option. The owner chose to keep the
repository on the Windows file system so that the Windows workflow (editor,
git, Explorer) does not change.

## Decision

1. **PHP runs in Docker in every environment.** One project image is built
   from `php:8.5-cli` with `pdo_pgsql`, `redis`, `pcntl` and `zip`. A
   development stage adds `pcov`, Composer and Node 24. The image is built for
   `linux/amd64` and `linux/arm64`.
2. **The repository stays on the Windows file system.** `compose.yaml`
   bind-mounts the source into the `app` service and keeps `vendor/` and
   `node_modules/` in named volumes.
3. **Vite runs inside the `app` container**, with file polling, because
   Wayfinder needs PHP. HMR is served on `127.0.0.1:5173`.
4. **Development command:** `docker compose up -d`. The app is served on
   `http://127.0.0.1:8000`. One-off commands use
   `docker compose exec app <command>` (for example
   `docker compose exec app composer check`).
5. **PHP 8.5 everywhere**, including CI, instead of the 8.4 assumed in the
   design.
6. **No PHP is required on the host.** Herd Lite is not part of the project.

Versions the starter kit produced on 2026-11-06, to pin when it is merged:

| Package                                          | Version      |
| ------------------------------------------------ | ------------ |
| `laravel/framework`                              | 13.40.0      |
| `inertiajs/inertia-laravel` / `@inertiajs/react` | 3.7.0 / ^3.0 |
| `laravel/fortify`                                | 1.41.0       |
| `laravel/wayfinder`                              | 0.1.23       |
| `react`                                          | ^19.2        |
| `tailwindcss`                                    | ^4.0         |
| `typescript`                                     | ^5.7         |
| `vite` (Vite+ core)                              | 1.4.0        |
| `pestphp/pest`                                   | ^5.3         |

Recommended `%UserProfile%\.wslconfig` (Docker Desktop shares this VM with
Parlora Tutor):

```ini
[wsl2]
memory=10GB
swap=4GB

[experimental]
autoMemoryReclaim=gradual
```

## Consequences

- The same image runs in development, in CI and on the server, so extension
  or PHP-version drift shows up before deployment.
- The test suite is about 2× slower than native PHP or WSL2 (8 s against
  4 s for the kit's tests). Moving the repository into WSL2 stays available
  as a speed-up, with no change to the image or to Compose.
- `vendor/` is not visible on the host, so editor extensions that index PHP
  dependencies need a host copy of `vendor/` or the VS Code Dev Containers
  extension. This is documented in the README.
- Pre-commit hooks that run PHP tools (Pint) call them through
  `docker compose exec app`, so the `app` service has to be running when
  committing PHP changes.
- `compose.yaml` gains an `app` service next to PostgreSQL, Valkey and
  Mailpit. Horizon runs inside it or in a sibling service built from the same
  image; the local-runtime group decides which.
- CI uses PHP 8.5, which changes design decision D5 (PHP 8.4).
- RAM with both projects running has not been measured yet. It has to be
  measured before Phase 6, and the `.wslconfig` limits should be adjusted
  then.
