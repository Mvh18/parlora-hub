# ADR 0001: PolyForm Noncommercial licence, a CLA and the owner's own use

- **Status:** Accepted
- **Date:** 2026-11-06
- **Issue:** #16 (part of #2)

> This ADR records an engineering and governance decision. It is not legal
> advice; the CLA text is flagged for legal review before the first external
> contribution is accepted.

## Context

Parlora Hub is public on GitHub from its first commit, like Parlora Tutor.
The copyright holder wants three things at once:

1. anyone may use, study, modify and share Hub for non-commercial purposes,
   such as personal study, teaching or research;
2. third parties may not use it commercially, for example by selling courses
   on it, without a separate licence;
3. the copyright holder keeps full freedom to use it, commercially too:
   today as a personal study platform, later perhaps as a published product
   that embeds Parlora Tutor, with external contributions included.

An OSI-approved licence cannot meet the second goal, because the OSI's
definition forbids restricting commercial use. Accepting contributions under
the project licence alone would block the third goal.

## Decision

1. **Licence:** the verbatim PolyForm Noncommercial License 1.0.0 in
   `LICENSE`, with the `Required Notice: Copyright Marlon Vásquez Herrera`
   line, the same licence as Parlora Tutor. Public documentation describes
   Hub as **source-available, free for non-commercial use**, and never as
   open source. A repository test (`tests/Feature/DocsTest.php`) enforces
   both.
2. **Commercial use** by anyone else needs a separate licence from the
   copyright holder, requested at team.parlora@gmail.com.
3. **The owner is not a licensee.** PolyForm Noncommercial limits what others
   may do with the code; it does not restrict the copyright holder. The
   owner's own use (running Hub for personal study, publishing it as a
   service, or charging for courses on it) needs no exception and no
   change to the licence.
4. **Contributions** are accepted under the individual Contributor Licence
   Agreement in `CLA.md` (modelled on the Apache ICLA). Contributors keep
   their copyright and grant the maintainer a broad licence that includes
   the right to sublicense and relicense, commercially too, and a patent
   licence.
5. **Enforcement:** the `cla.yml` workflow sets a `cla` commit status on pull
   requests from external contributors, who accept once by commenting the
   fixed phrase. The maintainer and bots are exempt. The status is not a
   required check; the maintainer does not merge an external pull request
   while it fails.

## Consequences

- GitHub shows the licence as "Other", and some people and organisations
  avoid non-OSI projects. Both READMEs explain the licence in plain words.
- The owner can publish Hub commercially with community contributions in it,
  and can combine it with Parlora Tutor under the same terms.
- The CLA adds friction: one comment on the first pull request.
- Third-party dependencies keep their own licences. The Composer licence
  check and the dependency review in `security.yml` share one allow-list
  (`dependency-review-config.yml`) of licences compatible with distributing
  Hub under these terms.
- A change of licence later (for example to a commercial licence for
  hosted customers) needs no permission from contributors, thanks to the
  CLA.

## Options ruled out

- **AGPL-3.0 with a commercial dual licence:** OSI-approved, but it permits
  commercial use under copyleft, which does not meet goal 2.
- **CC BY-NC-SA 4.0:** Creative Commons advises against it for software, and
  it has no patent grant.
- **Developer Certificate of Origin instead of a CLA:** gives no relicensing
  right, so contributions could not be reused commercially (goal 3).
- **A different licence from Parlora Tutor:** the two products are used
  together; one licence keeps their terms and the contributor process
  identical.
