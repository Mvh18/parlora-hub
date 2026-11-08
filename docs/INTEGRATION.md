# Hub ↔ Tutor integration

Contract and decisions for connecting Parlora Hub with Parlora Tutor. The API contract's source of
truth is `tutor/contracts/openapi.json`; this document records how Hub uses it.

## Flows

| Flow                            | Direction       | Mechanism                                                                                                |
| ------------------------------- | --------------- | -------------------------------------------------------------------------------------------------------- |
| Start a call                    | Hub → Tutor     | `POST /sessions` with context; returns a `client_token` for the browser                                  |
| Conversation                    | Browser ↔ Tutor | Direct WebRTC with Pipecat (SmallWebRTC) and RTVI messages whose schema is published in Tutor's contract |
| Summary on hang-up              | Tutor → Hub     | Signed `session.completed` webhook + `GET /sessions/{id}/summary` (includes `vocabulary_seen`)           |
| Session history                 | Hub → Tutor     | Read-only session and turn queries by `learner_ref`                                                      |
| Speaking and writing evaluation | Hub → Tutor     | Queued `POST /evaluations/*` + `evaluation.completed` webhook                                            |

## Decisions

- **Call:** Pipecat with SmallWebRTC; the browser talks to Tutor directly, Hub only opens and closes
  the session.
- **State:** each system owns its data. Tutor keys conversations by `learner_ref`; Hub owns identity,
  progress and the mistakes notebook.
- **Correction format:** Tutor's corrector schema (`original`, `corrected`, `type`, `explanation`,
  `mentioned_in_voice`), plus rubric scores for evaluations.
- **Authentication:** server key for Hub → Tutor, short-lived `client_token` for the browser, HMAC
  on webhooks (constant-time comparison, idempotency by event id).
- **Corrections:** brief in voice, full on screen.
- **Spaced repetition:** owned by Hub, with FSRS; it works without Tutor. Cards come from lesson
  vocabulary, the mistakes notebook and each call's `vocabulary_seen`.
- **Content generation:** an Artisan command that talks to the Ollama API directly when no calls are
  active.
- **Frontend:** Tutor has no product UI. Hub builds its call screen with `@pipecat-ai/client-js`
  against the RTVI message schema in the contract; no Tutor frontend code is shared.
