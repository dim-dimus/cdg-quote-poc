# Fixtures Source Map

Every parity fixture in `packages/pricing/tests/Fixtures/wrap-cases.json` is
traceable back to the workbook here, so the ground truth is auditable.

Values computed from `CDG_workbook.xlsx` (Vehicle Data + Rates + Shop Settings +
AddOns sheets) using the formula map in `.claude/skills/cdg-pricing/SKILL.md`.
The oracle that computes the expected outputs is version-controlled at
`packages/pricing/tests/Fixtures/compute_fixtures.mjs` (plain Node.js, no
dependencies — run with `node tests/Fixtures/compute_fixtures.mjs`); every
vehicle row below is transcribed from the `Vehicle Data` sheet.

### Baseline set

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

### Expanded diversity set

Broadens vehicle/category coverage beyond the Ford Transit baseline and exercises
every complexity level, both wrap types, and all seven add-ons.

| Fixture id | Vehicle | Category | Wrap type | Complexity | Add-ons | Decision | Depends on | Source notes |
|---|---|---|---|---|---|---|---|---|
| `sprinter-144-cc-standard-none` | Sprinter 144 | Van | Color Change | Standard | none | REVIEW | D1 D2 D4 D5 | Larger van than Transit; more labor dilutes margin |
| `sprinter-170-printed-complex-none` | Sprinter 170 | Van | Printed | Complex | none | GOOD | D1 D2 D4 D5 | Large canvas + printed rate + complex multiplier |
| `rav4-cc-easy-none` | Toyota RAV4 | Small SUV | Color Change | Easy | none | REVIEW | D1 D2 D4 D5 | First Small SUV; low multiplier keeps it in REVIEW |
| `model-y-printed-standard-tint` | Tesla Model Y | Small SUV | Printed | Standard | window_tint | GOOD | D1 D2 D4 D5 D7 | Premium small SUV + single add-on |
| `defender-cc-complex-trim` | Land Rover Defender | Mid SUV | Color Change | Complex | trim_removal | GOOD | D1 D2 D4 D5 D7 | Exercises `trim_removal` add-on |
| `grand-cherokee-printed-standard-none` | Jeep Grand Cherokee | Mid SUV | Printed | Standard | none | GOOD | D1 D2 D4 D5 | Mainstream Mid SUV, printed |
| `escalade-printed-specialty-ceramic` | Cadillac Escalade | Full SUV | Printed | Specialty | ceramic_coating | STRONG | D1 D2 D4 D5 D7 | First Full SUV; premium everything → 68.8% margin |
| `tahoe-cc-standard-none` | Chevy Tahoe | Full SUV | Color Change | Standard | none | REVIEW | D1 D2 D4 D5 | Large retail SUV, standard CC |
| `f150-cc-easy-none` | Ford F150 | Truck | Color Change | Easy | none | REVIEW | D1 D2 D4 D5 | First Truck; large body + Easy discount |
| `raptor-printed-specialty-none` | Ford Raptor | Truck | Printed | Specialty | none | STRONG | D1 D2 D4 D5 | Premium truck at max rate + multiplier |
| `silverado-cc-complex-prep` | Chevy Silverado | Truck | Color Change | Complex | prep_fee | GOOD | D1 D2 D4 D5 D7 | Exercises `prep_fee` add-on |
| `model-3-printed-standard-design-basic` | Tesla Model 3 | Sedan | Printed | Standard | design_basic | STRONG | D1 D2 D4 D5 D7 | Exercises `design_basic` (zero-cost) add-on |
| `civic-cc-easy-design-advanced` | Honda Civic | Sedan | Color Change | Easy | design_advanced | STRONG | D1 D2 D4 D5 D7 | Compact + high-price zero-cost `design_advanced` lifts margin |
| `wrangler-cc-easy-none` | Jeep Wrangler 4 Door | Specialty | Color Change | Easy | none | REJECT / REPRICE | D1 D2 D4 D5 | High labor (hinges) + Easy discount → 54.96% |
| `wrangler-printed-specialty-trim-prep` | Jeep Wrangler 4 Door | Specialty | Printed | Specialty | trim_removal, prep_fee | STRONG | D1 D2 D4 D5 D7 | Multi add-on on a specialty vehicle |
| `cargo-trailer-printed-standard-none` | Cargo Trailer | Commercial | Printed | Standard | none | STRONG | D1 D2 D4 D5 | Large sqft (320-500) dominates revenue |
| `service-pickup-cc-standard-design-custom-override` | Service Pickup | Commercial | Color Change | Standard | design_custom (override $500) | GOOD | D1 D2 D4 D5 D7 | **Upward price override** — $600 catalog → $500 |
| `model-x-cc-complex-tint-override` | Tesla Model X | General | Color Change | Complex | window_tint (override $550) | GOOD | D1 D2 D4 D5 D7 | First General category; **upward override** $400 → $550; `requestedFinish=Satin` (D9, informational) |

### Extra REJECT coverage (over-discounted add-ons)

REJECT is a narrow band in this model (labor cancels in gross profit but inflates
the sell denominator), so pure-input rejects only occur on the two highest-labor
specialty vehicles. These cases show REJECT arising from an over-aggressive
**downward** price override — a rep comping an add-on below cost on a thin job —
which is exactly what the decision engine must flag.

| Fixture id | Vehicle | Category | Wrap type | Complexity | Add-ons | Decision | Depends on | Source notes |
|---|---|---|---|---|---|---|---|---|
| `rivian-r1t-cc-easy-tint-comped-override` | Rivian R1T | Specialty | Color Change | Easy | window_tint (override $0) | REJECT / REPRICE | D1 D2 D4 D5 D7 | Free tint (cost still $120) drags a REVIEW job to 54.92% |
| `sprinter-170-cc-easy-tint-ceramic-comped-override` | Sprinter 170 | Van | Color Change | Easy | window_tint + ceramic (both override $0) | REJECT / REPRICE | D1 D2 D4 D5 D7 | Two comped add-ons (cost still charged) → 54.44% |

## Coverage summary

| Dimension | Values covered |
|---|---|
| Vehicle categories | Van, Small SUV, Mid SUV, Full SUV, Truck, Sedan, Specialty, Commercial, General (all 9) |
| Distinct vehicles | 19 (Ford Transit no longer dominates: 8 of 32 cases) |
| Wrap types | Color Change, Printed |
| Complexity levels | Easy, Standard, Complex, Specialty (all 4) |
| Add-on combinations | None, single, multi; all 7 add-ons exercised |
| Price overrides (D7) | Upward ($400→$550, $600→$500) and downward/comped ($0) |
| Decisions | REJECT/REPRICE ×4, REVIEW ×7, GOOD ×13, STRONG ×9 (all 4 bands) |
| Total cases | 32 |

## Notes

- "Depends on" lists the DECISIONS.md rule(s) a fixture's expected value relies on.
  If a client answer changes D1 or D2, the affected fixtures must be recomputed.
- `ford-transit-cc-standard-none` is the primary baseline case — it should be
  the first to pass and the last to regress.
- Tesla Cybertruck's REJECT is caused by the combination of very high labor hours
  (28–40, avg 34) and the Easy multiplier discounting wrap revenue below 55% margin.
