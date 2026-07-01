# Fixtures Source Map

Every parity fixture in `packages/pricing/tests/Fixtures/wrap-cases.json` is
traceable back to the workbook here, so the ground truth is auditable.

Values computed from `CDG_workbook.xlsx` (Vehicle Data + Rates + Shop Settings +
AddOns sheets) using the formula map in `.claude/skills/cdg-pricing/SKILL.md`.

| Fixture id | Vehicle | Category | Wrap type | Complexity | Add-ons | Decision | Depends on | Source notes |
|---|---|---|---|---|---|---|---|---|
| `ford-transit-cc-easy-none` | Ford Transit | Van | Color Change | Easy | none | REVIEW | D1 D2 D4 D5 | Baseline van, lowest multiplier |
| `ford-transit-cc-standard-none` | Ford Transit | Van | Color Change | Standard | none | REVIEW | D1 D2 D4 D5 | **Primary baseline** — matches Front Desk default inputs |
| `ford-transit-cc-complex-none` | Ford Transit | Van | Color Change | Complex | none | GOOD | D1 D2 D4 D5 | Multiplier lifts into GOOD band |
| `ford-transit-cc-specialty-none` | Ford Transit | Van | Color Change | Specialty | none | GOOD | D1 D2 D4 D5 | Highest CC multiplier, still GOOD (labor dilutes margin) |
| `ford-transit-printed-standard-none` | Ford Transit | Van | Printed | Standard | none | GOOD | D1 D2 D4 D5 | Printed rate ($23 mid) vs CC ($20 mid) pushes to GOOD |
| `ford-transit-printed-specialty-none` | Ford Transit | Van | Printed | Specialty | none | STRONG | D1 D2 D4 D5 | Both rate and multiplier at max → STRONG |
| `toyota-corolla-cc-standard-none` | Toyota Corolla | Sedan | Color Change | Standard | none | GOOD | D1 D2 D4 D5 | Smaller sqft / fewer labor hours → better margin than van |
| `toyota-corolla-printed-specialty-none` | Toyota Corolla | Sedan | Printed | Specialty | none | STRONG | D1 D2 D4 D5 | Compact + premium rate + max multiplier → 70% margin |
| `tesla-cybertruck-cc-easy-none` | Tesla Cybertruck | Specialty | Color Change | Easy | none | REJECT / REPRICE | D1 D2 D4 D5 | High labor (34 hrs avg) + Easy discount → margin 54.2% |
| `box-truck-cc-standard-none` | Box Truck 16-26 ft | Commercial | Color Change | Standard | none | STRONG | D1 D2 D4 D5 | Large sqft range (400-650) dominates; high revenue |
| `ford-transit-cc-standard-tint` | Ford Transit | Van | Color Change | Standard | window_tint | REVIEW | D1 D2 D4 D5 D7 | Add-on revenue improves sell but not enough to leave REVIEW |
| `ford-transit-cc-standard-ceramic-tint` | Ford Transit | Van | Color Change | Standard | ceramic_coating, window_tint | GOOD | D1 D2 D4 D5 D7 | Two high-margin add-ons push into GOOD band |

## Coverage summary

| Dimension | Values covered |
|---|---|
| Vehicle categories | Van, Sedan, Specialty, Commercial |
| Wrap types | Color Change, Printed |
| Complexity levels | Easy, Standard, Complex, Specialty (all 4) |
| Add-on combinations | None, single (tint), multi (ceramic + tint) |
| Decisions | REJECT/REPRICE, REVIEW, GOOD, STRONG (all 4) |

## Notes

- "Depends on" lists the DECISIONS.md rule(s) a fixture's expected value relies on.
  If a client answer changes D1 or D2, the affected fixtures must be recomputed.
- `ford-transit-cc-standard-none` is the primary baseline case — it should be
  the first to pass and the last to regress.
- Tesla Cybertruck's REJECT is caused by the combination of very high labor hours
  (28–40, avg 34) and the Easy multiplier discounting wrap revenue below 55% margin.
