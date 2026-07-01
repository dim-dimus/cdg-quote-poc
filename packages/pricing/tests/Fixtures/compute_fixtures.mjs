/**
 * Parity fixture generator for the CDG wrap pricing engine.
 *
 * This is an INDEPENDENT re-implementation of the Front Desk formula map, used
 * as the oracle for ExcelParityTest. Inputs (vehicle labor/sqft, rates, add-ons,
 * shop settings) are transcribed from CDG_workbook.xlsx; outputs are computed
 * here and asserted against the PHP engine to the cent.
 *
 *     Vehicle stats  -> Vehicle Data sheet
 *     Rates          -> Rates sheet (dollars -> cents)
 *     Shop settings  -> Shop Settings sheet
 *     Add-on catalog -> AddOns sheet (dollars -> cents)
 *
 * Run from the package root:  node tests/Fixtures/compute_fixtures.mjs
 * It rewrites tests/Fixtures/wrap-cases.json.
 *
 * RULE: never hand-edit wrap-cases.json to make a test pass. If the engine
 * disagrees, fix the engine — or, if an input here is wrong vs the workbook,
 * fix the input here and regenerate. See .claude/skills/cdg-pricing/SKILL.md.
 *
 * Written in plain JavaScript (a different language from the PHP engine) so it
 * stays an independent oracle, with zero dependencies and no build step.
 */
import { writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

// ── Shop Settings (workbook) ────────────────────────────────────────────────
const SHOP_RATE_CENTS = 11000;              // $110/hr
const WASTE_MULT = 1.2;                      // D2: use cell value 1.2
const MATERIAL_COST_CENTS_PER_SQFT = 220;    // $2.20/sqft
const COMPLEXITY_MULTS = { easy: 0.95, standard: 1.0, complex: 1.12, specialty: 1.22 };
const MARGIN_FLOORS = { reject: 0.55, review: 0.60, strong: 0.65 }; // D6

// ── Add-On catalog (AddOns sheet, dollars -> cents) ─────────────────────────
const ADD_ONS = {
  ceramic_coating: { priceCents: 70000, costCents: 10000 },
  window_tint:     { priceCents: 40000, costCents: 12000 },
  prep_fee:        { priceCents: 25000, costCents:  4000 },
  trim_removal:    { priceCents: 30000, costCents:  7500 },
  design_basic:    { priceCents: 30000, costCents:     0 },
  design_custom:   { priceCents: 60000, costCents:     0 },
  design_advanced: { priceCents: 120000, costCents:    0 },
};

// ── Rates (Rates sheet, dollars -> cents) ───────────────────────────────────
const RATES = {
  color_change: { low: 1600, high: 2400 },
  printed:      { low: 1800, high: 2800 },
};

const roundHalfUp = (x) => Math.floor(x + 0.5);

function decision(margin) {
  if (margin < MARGIN_FLOORS.reject) return 'REJECT / REPRICE';
  if (margin < MARGIN_FLOORS.review) return 'REVIEW';
  if (margin < MARGIN_FLOORS.strong) return 'GOOD';
  return 'STRONG';
}

/**
 * @param addOnSelections object mapping add-on key -> override sell price in
 *        cents, or null for the catalog price (D7).
 */
function compute(laborLow, laborHigh, sqftLow, sqftHigh, wrapType, complexity, addOnSelections) {
  // ── Averages (D4) ─────────────────────────────────────────────────────
  const laborHours = (laborLow + laborHigh) / 2;
  const sqFt = (sqftLow + sqftHigh) / 2;
  const rateCents = (RATES[wrapType].low + RATES[wrapType].high) / 2;

  // ── Revenue & cost components (full precision cents) (D3) ──────────────
  const mult = COMPLEXITY_MULTS[complexity];
  const baseWrapRev = sqFt * rateCents * mult;        // D5: mult only on wrap rev
  const laborRev = laborHours * SHOP_RATE_CENTS;      // D1: in both sell & cost
  const materialQty = sqFt * WASTE_MULT;
  const materialCost = materialQty * MATERIAL_COST_CENTS_PER_SQFT;
  // D7: sell price is overridable per quote; cost always from catalog.
  const entries = Object.entries(addOnSelections);
  const addOnRev = entries.reduce(
    (sum, [key, override]) => sum + (override ?? ADD_ONS[key].priceCents), 0);
  const addOnCost = entries.reduce(
    (sum, [key]) => sum + ADD_ONS[key].costCents, 0);

  const totalSellRaw = baseWrapRev + laborRev + addOnRev;
  const totalCostRaw = materialCost + laborRev + addOnCost;

  // ── Round only final money outputs (D3) ───────────────────────────────
  const totalSellCents = roundHalfUp(totalSellRaw);
  const totalCostCents = roundHalfUp(totalCostRaw);
  const grossProfitCents = totalSellCents - totalCostCents;
  const grossMargin = totalSellCents > 0 ? grossProfitCents / totalSellCents : 0.0;

  return {
    _intermediates: {
      laborHours,
      sqFt,
      rateCentsPerSqFt: rateCents,
      baseWrapRevCents: baseWrapRev,
      laborRevCents: laborRev,
      materialOrderQtySqFt: materialQty,
      materialCostCents: materialCost,
      addOnRevCents: addOnRev,
      addOnCostCents: addOnCost,
    },
    totalSellCents,
    totalCostCents,
    grossProfitCents,
    grossMargin: Math.round(grossMargin * 1e10) / 1e10,
    decision: decision(grossMargin),
  };
}

// [id, description, vehicle, category, laborLow, laborHigh, sqftLow, sqftHigh,
//  wrapType, complexity, addOnSelections, requestedFinish]
const casesInput = [
  // ── Existing baseline set (Phase 2) ──────────────────────────────────────
  ['ford-transit-cc-easy-none',
   'Ford Transit · Color Change · Easy · no add-ons',
   'Ford Transit', 'Van', 22, 28, 280, 300, 'color_change', 'easy', {}, null],
  ['ford-transit-cc-standard-none',
   'Ford Transit · Color Change · Standard · no add-ons — baseline case',
   'Ford Transit', 'Van', 22, 28, 280, 300, 'color_change', 'standard', {}, null],
  ['ford-transit-cc-complex-none',
   'Ford Transit · Color Change · Complex · no add-ons',
   'Ford Transit', 'Van', 22, 28, 280, 300, 'color_change', 'complex', {}, null],
  ['ford-transit-cc-specialty-none',
   'Ford Transit · Color Change · Specialty · no add-ons',
   'Ford Transit', 'Van', 22, 28, 280, 300, 'color_change', 'specialty', {}, null],
  ['ford-transit-printed-standard-none',
   'Ford Transit · Printed · Standard · no add-ons',
   'Ford Transit', 'Van', 22, 28, 280, 300, 'printed', 'standard', {}, null],
  ['ford-transit-printed-specialty-none',
   'Ford Transit · Printed · Specialty · no add-ons',
   'Ford Transit', 'Van', 22, 28, 280, 300, 'printed', 'specialty', {}, null],
  ['toyota-corolla-cc-standard-none',
   'Toyota Corolla · Color Change · Standard · no add-ons',
   'Toyota Corolla', 'Sedan', 14, 16, 200, 210, 'color_change', 'standard', {}, null],
  ['toyota-corolla-printed-specialty-none',
   'Toyota Corolla · Printed · Specialty · no add-ons',
   'Toyota Corolla', 'Sedan', 14, 16, 200, 210, 'printed', 'specialty', {}, null],
  ['tesla-cybertruck-cc-easy-none',
   'Tesla Cybertruck · Color Change · Easy · no add-ons — triggers REJECT',
   'Tesla Cybertruck', 'Specialty', 28, 40, 320, 350, 'color_change', 'easy', {}, null],
  ['box-truck-cc-standard-none',
   'Box Truck 16-26 ft · Color Change · Standard · no add-ons — commercial vehicle',
   'Box Truck 16-26 ft', 'Commercial', 26, 38, 400, 650, 'color_change', 'standard', {}, null],
  ['ford-transit-cc-standard-tint',
   'Ford Transit · Color Change · Standard · Window Tint add-on',
   'Ford Transit', 'Van', 22, 28, 280, 300, 'color_change', 'standard', { window_tint: null }, null],
  ['ford-transit-cc-standard-ceramic-tint',
   'Ford Transit · Color Change · Standard · Ceramic Coating + Window Tint',
   'Ford Transit', 'Van', 22, 28, 280, 300, 'color_change', 'standard',
   { ceramic_coating: null, window_tint: null }, null],

  // ── Expanded diversity set (all categories / complexities / add-ons) ─────
  ['sprinter-144-cc-standard-none',
   'Sprinter 144 · Color Change · Standard · no add-ons',
   'Sprinter 144', 'Van', 24, 30, 300, 320, 'color_change', 'standard', {}, null],
  ['sprinter-170-printed-complex-none',
   'Sprinter 170 · Printed · Complex · no add-ons',
   'Sprinter 170', 'Van', 28, 36, 340, 370, 'printed', 'complex', {}, null],
  ['rav4-cc-easy-none',
   'Toyota RAV4 · Color Change · Easy · no add-ons',
   'Toyota RAV4', 'Small SUV', 16, 18, 220, 230, 'color_change', 'easy', {}, null],
  ['model-y-printed-standard-tint',
   'Tesla Model Y · Printed · Standard · Window Tint',
   'Tesla Model Y', 'Small SUV', 18, 20, 230, 240, 'printed', 'standard', { window_tint: null }, null],
  ['defender-cc-complex-trim',
   'Land Rover Defender · Color Change · Complex · Trim Removal',
   'Land Rover Defender', 'Mid SUV', 20, 24, 250, 260, 'color_change', 'complex', { trim_removal: null }, null],
  ['grand-cherokee-printed-standard-none',
   'Jeep Grand Cherokee · Printed · Standard · no add-ons',
   'Jeep Grand Cherokee', 'Mid SUV', 18, 22, 240, 250, 'printed', 'standard', {}, null],
  ['escalade-printed-specialty-ceramic',
   'Cadillac Escalade · Printed · Specialty · Ceramic Coating',
   'Cadillac Escalade', 'Full SUV', 22, 26, 270, 285, 'printed', 'specialty', { ceramic_coating: null }, null],
  ['tahoe-cc-standard-none',
   'Chevy Tahoe · Color Change · Standard · no add-ons',
   'Chevy Tahoe', 'Full SUV', 20, 24, 260, 275, 'color_change', 'standard', {}, null],
  ['f150-cc-easy-none',
   'Ford F150 · Color Change · Easy · no add-ons',
   'Ford F150', 'Truck', 20, 24, 260, 280, 'color_change', 'easy', {}, null],
  ['raptor-printed-specialty-none',
   'Ford Raptor · Printed · Specialty · no add-ons',
   'Ford Raptor', 'Truck', 22, 26, 270, 290, 'printed', 'specialty', {}, null],
  ['silverado-cc-complex-prep',
   'Chevy Silverado · Color Change · Complex · Prep Fee',
   'Chevy Silverado', 'Truck', 20, 24, 260, 280, 'color_change', 'complex', { prep_fee: null }, null],
  ['model-3-printed-standard-design-basic',
   'Tesla Model 3 · Printed · Standard · Design Basic',
   'Tesla Model 3', 'Sedan', 16, 18, 210, 220, 'printed', 'standard', { design_basic: null }, null],
  ['civic-cc-easy-design-advanced',
   'Honda Civic · Color Change · Easy · Design Advanced',
   'Honda Civic', 'Sedan', 14, 16, 200, 210, 'color_change', 'easy', { design_advanced: null }, null],
  ['wrangler-cc-easy-none',
   'Jeep Wrangler 4 Door · Color Change · Easy · no add-ons — triggers REJECT',
   'Jeep Wrangler 4 Door', 'Specialty', 20, 26, 230, 240, 'color_change', 'easy', {}, null],
  ['wrangler-printed-specialty-trim-prep',
   'Jeep Wrangler 4 Door · Printed · Specialty · Trim Removal + Prep Fee',
   'Jeep Wrangler 4 Door', 'Specialty', 20, 26, 230, 240, 'printed', 'specialty',
   { trim_removal: null, prep_fee: null }, null],
  ['cargo-trailer-printed-standard-none',
   'Cargo Trailer · Printed · Standard · no add-ons',
   'Cargo Trailer', 'Commercial', 20, 30, 320, 500, 'printed', 'standard', {}, null],
  ['service-pickup-cc-standard-design-custom-override',
   'Service Pickup · Color Change · Standard · Design Custom (price override $500)',
   'Service Pickup', 'Commercial', 18, 24, 240, 260, 'color_change', 'standard', { design_custom: 50000 }, null],
  ['model-x-cc-complex-tint-override',
   'Tesla Model X · Color Change · Complex · Window Tint (price override $550)',
   'Tesla Model X', 'General', 18, 22, 240, 260, 'color_change', 'complex', { window_tint: 55000 }, 'Satin'],

  // ── Extra REJECT coverage: over-discounted add-ons on thin jobs (D7 downward) ─
  ['rivian-r1t-cc-easy-tint-comped-override',
   'Rivian R1T · Color Change · Easy · Window Tint comped ($0) — override pushes to REJECT',
   'Rivian R1T', 'Specialty', 22, 26, 260, 270, 'color_change', 'easy', { window_tint: 0 }, null],
  ['sprinter-170-cc-easy-tint-ceramic-comped-override',
   'Sprinter 170 · Color Change · Easy · Window Tint + Ceramic comped ($0) — override pushes to REJECT',
   'Sprinter 170', 'Van', 28, 36, 340, 370, 'color_change', 'easy',
   { window_tint: 0, ceramic_coating: 0 }, null],
];

const casesOutput = casesInput.map((row) => {
  const [id, description, vehicle, category, ll, lh, sl, sh,
         wrapType, complexity, addOnSelections, requestedFinish] = row;
  const result = compute(ll, lh, sl, sh, wrapType, complexity, addOnSelections);
  const rate = RATES[wrapType];
  console.log(
    `${id.padEnd(52)} sell=${String(result.totalSellCents).padStart(8)}  ` +
    `gp=${String(result.grossProfitCents).padStart(8)}  ` +
    `margin=${result.grossMargin.toFixed(4)}  ${result.decision}`);
  return {
    id,
    description,
    vehicle,
    category,
    input: {
      laborLowHours: ll,
      laborHighHours: lh,
      sqFtLow: sl,
      sqFtHigh: sh,
      rateLowCents: rate.low,
      rateHighCents: rate.high,
      complexity,
      addOnSelections,
      requestedFinish,
    },
    expected: {
      _intermediates: result._intermediates,
      totalSellCents: result.totalSellCents,
      totalCostCents: result.totalCostCents,
      grossProfitCents: result.grossProfitCents,
      grossMargin: result.grossMargin,
      decision: result.decision,
    },
  };
});

const fixture = {
  _note: 'Ground-truth fixtures for the wrap engine. Inputs transcribed from ' +
    'CDG_workbook.xlsx; outputs computed by tests/Fixtures/compute_fixtures.mjs. ' +
    'Never edit expected values to make a test pass — fix the engine instead.',
  config: {
    shopRateCents: SHOP_RATE_CENTS,
    wasteMultiplier: WASTE_MULT,
    materialCostCentsPerSqFt: MATERIAL_COST_CENTS_PER_SQFT,
    complexityMultipliers: COMPLEXITY_MULTS,
    marginFloors: MARGIN_FLOORS,
    addOns: ADD_ONS,
  },
  cases: casesOutput,
};

const out = join(dirname(fileURLToPath(import.meta.url)), 'wrap-cases.json');
writeFileSync(out, JSON.stringify(fixture, null, 2) + '\n');
console.log(`\n${casesOutput.length} cases written to ${out}`);
