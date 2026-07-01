# Pricing Decisions & Open Questions

This is the authoritative record of how each ambiguous business rule is
handled. The Excel workbook is the source of truth; where it is unclear or
self-contradictory, the rule below applies.

Status legend: `CONFIRMED` (client answered) · `ASSUMPTION` (pending client) ·
`OPEN` (blocking, needs an answer).

---

### D1 — Labor in the margin math  ·  status: CONFIRMED
Labor Revenue is added to BOTH `TOTAL SELL` and `TOTAL COST`, so labor
contributes $0 gross profit. Gross profit comes only from the wrap, materials,
and add-ons.
**Client:** "This is intentional. Labor is both revenue and cost, so labor
itself carries no margin."
**Rule:** replicate exactly as-is. Labor nets to zero GP.

### D2 — Waste multiplier  ·  status: CONFIRMED
`Shop Settings` has `Material Waste Multiplier = 1.2`. The adjacent note
("Order 1.3x surface area") is outdated.
**Client:** "Use 1.2. The note referencing 1.3 is outdated and should be ignored."
**Rule:** use **1.2** from the cell value.

### D3 — Rounding  ·  status: CONFIRMED
Match Excel exactly — no intermediate rounding. Carry full precision through
all intermediate steps. If rounding is ever needed, it will be added intentionally.
**Client:** "Match Excel exactly. Do not introduce any additional rounding.
The app should produce the same results as the workbook."
**Rule:** `Support\Rounding::toCents()` is called only on final money totals
(totalSell, totalCost). All intermediate values — baseWrapRev, laborRev,
materialCost, etc. — stay as full-precision floats until the final sum.
Gross margin is a ratio; keep at full precision, round only for display.

### D4 — Averaging  ·  status: CONFIRMED
Labor hours, surface area, and rate per sq ft are each the midpoint
`AVERAGE(low, high)` of the configured range.
**Client:** "Yes, always use the midpoint (average) between the low and high
values unless we later add another pricing option."
**Rule:** always the midpoint.

### D5 — Complexity multiplier scope  ·  status: CONFIRMED
The multiplier applies **only** to Base Wrap Revenue. Labor and material are
not scaled by it.
**Client:** "Correct. Complexity only affects the Base Wrap Revenue. It does
not affect labor or material calculations."
**Rule:** `baseWrapRevenue = sqFt × rate × complexityMultiplier`. Labor and
material use raw values.

### D6 — Decision thresholds  ·  status: CONFIRMED
Thresholds and labels exactly as in Shop Settings. Values come from
`PricingConfig.marginFloors` so admins can change them without code changes.
**Client:** "Use the thresholds exactly as defined in the Shop Settings sheet.
The labels and thresholds should come directly from the admin settings so they
can be changed later without modifying code."
**Rule:**
- grossMargin < 0.55 → `REJECT / REPRICE`
- grossMargin < 0.60 → `REVIEW`
- grossMargin < 0.65 → `GOOD`
- grossMargin ≥ 0.65 → `STRONG`

### D7 — Add-on pricing  ·  status: CONFIRMED
Add-on prices default to the config catalog. Authorized users may override
the **sell price** of a specific add-on on an individual quote. The original
configured price is retained for reference. Add-on **cost** is never
overridden — it always comes from config.
**Client:** "Add-on pricing should come from the admin configuration by default.
However, the application should allow an authorized user to override an
individual add-on price on a specific quote when needed. The original configured
price should still be retained for reference."
**Rule:** `WrapInput.addOnSelections` is `array<string, int|null>` where the
key is the add-on identifier and the value is an override sell price in cents
(`null` = use config default). Engine computes:
`addOnSell = overridePrice ?? config.addOns[key].priceCents`
`addOnCost = config.addOns[key].costCents` (always from config, not overridable)

### D8 — Unknown vehicles  ·  status: CONFIRMED
POC supports listed vehicles only. Manual vehicle entry will be added later.
**Client:** "For the POC, limit vehicle selection to the existing database.
Manual vehicle creation will be added later."
**Rule:** vehicle selection is a dropdown of DB vehicles; no free-text entry.
`WrapInput` carries resolved low/high values (already looked up by the Laravel layer).

### D9 — Requested Finish  ·  status: CONFIRMED
Requested Finish is informational only today — no price effect. It is stored
on the quote so pricing can be tied to finish types later without re-engineering.
No other manually entered numeric fields exist in this module.
**Client:** "Requested Finish is currently informational only and does not
affect pricing. Build it so pricing can easily be tied to finish types later,
but for now it is simply stored on the quote. There are no other manually
entered pricing values in this module."
**Rule:** `WrapInput.requestedFinish` is `?string`. The engine carries it
through to the result but applies no price effect today. Future: add finish
pricing rules to `PricingConfig` and use the field in `WrapCalculator`.
