# syntax=docker/dockerfile:1
#
# Project image (ADR 0002): PHP runs in Docker in every environment. Build it
# for both architectures with
#   docker buildx build --platform linux/amd64,linux/arm64 --target base .
# Base images are pinned to digests; Dependabot proposes updates.

FROM php:8.5-cli-trixie@sha256:50956f094e90a716d43bea28087d447555ad80510e386ab8c9c10d8c70d781a9 AS base

COPY --from=mlocati/php-extension-installer:2@sha256:4fd45396c16685266d32bf147a24ecd5e81029d3820295895bf4a90131190ac9 \
    /usr/bin/install-php-extensions /usr/local/bin/

# pcntl and posix let Horizon supervise its workers.
RUN install-php-extensions pdo_pgsql redis pcntl zip

WORKDIR /app

# Development stage: coverage, Composer and Node 24 with pnpm through
# corepack. The source is bind-mounted by compose.yaml; vendor/ and
# node_modules/ live in named volumes.
FROM base AS dev

RUN install-php-extensions pcov \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2@sha256:8de2184c6de224f4689e8f80ed5f5edaf0030b6b8b7839a22a2bcb6722ac87d0 \
    /usr/bin/composer /usr/local/bin/composer
COPY --from=node:24-trixie-slim@sha256:0820eaa85e07d0490446dd01d14707e17e6108a36988cf374899cd057eb3812d \
    /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-trixie-slim@sha256:0820eaa85e07d0490446dd01d14707e17e6108a36988cf374899cd057eb3812d \
    /usr/local/lib/node_modules/corepack /usr/local/lib/node_modules/corepack

ENV COREPACK_ENABLE_DOWNLOAD_PROMPT=0 \
    COREPACK_HOME=/usr/local/share/corepack

# Fetch the pnpm release pinned in package.json (`packageManager`) at build
# time so the container never downloads it on start.
RUN --mount=type=bind,source=package.json,target=/tmp/pnpm/package.json \
    ln -s ../lib/node_modules/corepack/dist/corepack.js /usr/local/bin/corepack \
    && corepack enable pnpm \
    && cd /tmp/pnpm && corepack install && pnpm --version \
    && chmod -R a+rX "$COREPACK_HOME"

COPY --chmod=0755 docker/app-entrypoint.sh /usr/local/bin/app-entrypoint

ENTRYPOINT ["app-entrypoint"]
CMD ["composer", "dev"]
