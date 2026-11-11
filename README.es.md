# Parlora Hub

**Una plataforma de cursos de inglés autoalojada: lecciones, práctica, exámenes y progreso.**
Estudia los niveles MCER A1 a C1 con ejercicios corregidos en el servidor,
exámenes por nivel que desbloquean el siguiente módulo y un cuaderno de errores
que muestra dónde fallas más.

> 🚧 **En construcción.** La fase 0 está terminada: el esqueleto corre en
> local con inicio de sesión, la estructura de la app y una página de inicio
> vacía. Las lecciones, la práctica y los exámenes llegan en las siguientes
> fases. [Read in English](README.md).

## Principios

- **Corre en tu equipo** — pensada para un solo alumno en local, lista para
  publicarse más adelante sin reescribirla.
- **Corrección en el servidor** — el backend comprueba las respuestas; las
  soluciones nunca llegan al navegador antes de responder.
- **Contenido como código** — el temario vive en archivos JSON versionados y se
  importa con un comando.
- **Funciona sin IA** — las llamadas por voz y la evaluación de speaking y
  writing vienen del servicio opcional
  [Parlora Tutor](https://github.com/Mvh18/parlora-tutor). Sin él, Hub sigue
  funcionando, repaso espaciado incluido.

## Inicio rápido

Necesitas [Git](https://git-scm.com/) y
[Docker Desktop](https://www.docker.com/products/docker-desktop/) (o Docker
Engine con Compose v2). PHP, Composer y Node corren dentro del contenedor del
proyecto, así que no hay que instalar nada más
([ADR 0002](docs/adr/0002-php-environment.md), en inglés).

```sh
git clone https://github.com/Mvh18/parlora-hub.git
cd parlora-hub
docker compose up -d --wait
```

El primer arranque construye la imagen, instala los paquetes de Composer y
pnpm, crea `.env` con su clave y migra la base de datos; tarda unos minutos.
Después abre **http://127.0.0.1:8000**, regístrate e inicia sesión.

| Servicio                   | URL                           |
| -------------------------- | ----------------------------- |
| Parlora Hub                | http://127.0.0.1:8000         |
| Mailpit (correo capturado) | http://127.0.0.1:8025         |
| Horizon (colas, admin)     | http://127.0.0.1:8000/horizon |

Para abrir Horizon, da permisos de administración a tu cuenta; cuando ya
exista, puedes cerrar el registro con `HUB_REGISTRATION_ENABLED=false` en `.env`:

```sh
docker compose exec app php artisan hub:make-admin tu@example.com
```

`docker compose stop` detiene el entorno y `docker compose up -d --wait` lo
vuelve a levantar. Si un puerto está ocupado, cambia `APP_PORT` o la variable
`FORWARD_*` correspondiente en `.env`. Las comprobaciones, los hooks y las
convenciones de desarrollo están en [CONTRIBUTING.md](CONTRIBUTING.md) (en
inglés), y la arquitectura en [docs/architecture.md](docs/architecture.md).

## Licencia

Parlora Hub es **source-available** bajo la
[PolyForm Noncommercial License 1.0.0](LICENSE): puedes usarlo, estudiarlo,
modificarlo y compartirlo con cualquier fin **no comercial** (estudio personal,
educación, investigación, organizaciones sin ánimo de lucro). No es "open
source" en el sentido de la OSI, porque esta licencia no permite el uso
comercial.

Para licencias comerciales, escribe a **team.parlora@gmail.com**.

Copyright © Marlon Vásquez Herrera.
