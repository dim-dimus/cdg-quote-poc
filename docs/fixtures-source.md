# Fixtures Source Map

Every parity fixture in `packages/pricing/tests/Fixtures/wrap-cases.json` is
traceable back to the workbook here, so the ground truth is auditable. Fill one
row per fixture as it is created (via `/extract-fixtures`).

| Fixture id | Vehicle | Wrap type | Complexity | Add-ons | Depends on (DECISIONS) | Source notes |
|---|---|---|---|---|---|---|
| wrap-van-standard-none | Ford Transit | Color Change | Standard | none | D1, D4 | Front Desk, baseline midpoint case |
| _add rows as fixtures are created_ | | | | | | |

Notes:
- "Source notes" should say exactly which workbook inputs produced the case and
  any quirk relied upon (e.g. "labor counted in cost per D1").
- If a fixture's expected value depends on an ASSUMPTION (not yet CONFIRMED),
  list the decision id so it can be revisited if the client answers differently.
