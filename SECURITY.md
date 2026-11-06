# Security policy

## Supported versions

Parlora Hub is pre-1.0. Only the latest release and the `main` branch receive
security fixes.

| Version        | Supported |
| -------------- | --------- |
| `main`         | Yes       |
| Latest release | Yes       |
| Older releases | No        |

## Reporting a vulnerability

Please **do not open a public issue, discussion or pull request** for a
security problem.

Report it privately through GitHub's **private vulnerability reporting**:
open the repository's **Security** tab and choose **Report a vulnerability**
(direct link: <https://github.com/Mvh18/parlora-hub/security/advisories/new>).

Include, where you can:

- the affected component (backend, web app, content panel, queues, Docker
  setup, workflows) and version or commit;
- steps to reproduce or a proof of concept;
- the impact you expect (for example, account takeover or data exposure).

Parlora Hub is maintained by one person. You can expect an acknowledgement
within 7 days and a status update at least every 14 days until the report is
resolved. Fixes are released as GitHub security advisories with credit to the
reporter unless you prefer otherwise.

## Scope

Parlora Hub stores learner accounts, progress, answers and voice recordings of
speaking exercises, which are personal data. In scope:

- authentication, session and authorisation flaws, including access to levels,
  exams or other learners' data that the rules should deny;
- exposure of exercise or exam solutions before the learner answers;
- access to the content panel or the queue dashboard by non-administrators;
- injection (SQL, XSS through lesson Markdown, template injection), CSRF, mass
  assignment, path traversal or unsafe file uploads;
- forged, replayed or unsigned webhooks accepted from Parlora Tutor;
- anything that sends learner data off the machine without the operator
  configuring it, or network exposure beyond `127.0.0.1` in the default setup;
- supply-chain issues in this repository: workflows, pinned actions,
  dependency policy, container images built from it.

Out of scope:

- vulnerabilities in Parlora Tutor (report them in its repository) or in
  third-party libraries that are not caused by how Parlora Hub uses them;
- setups where the operator deliberately exposed ports to a network;
- findings that require an already compromised machine.
