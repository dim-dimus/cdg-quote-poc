<?php

declare(strict_types=1);

namespace CDG\Pricing\Modules\Tint;

use CDG\Pricing\Contracts\CalculatorInput;

/**
 * Per-quote inputs for a window-tint job.
 *
 * This module is an EXTENSIBILITY DEMO, not part of the POC's priced scope
 * (wrap only). It exists to prove a new service type can be added purely by
 * dropping a new folder under src/Modules/ — implementing the same Calculator /
 * CalculatorInput contracts — with zero edits to Engine, CalculatorRegistry, or
 * the Wrap module. See tests/ModuleIsolationTest.php.
 *
 * Note it carries entirely different fields than WrapInput: the contract is the
 * only thing modules share.
 */
final readonly class TintInput implements CalculatorInput
{
    public function __construct(
        public int $windowCount,
        public int $pricePerWindowCents,
        public int $costPerWindowCents,
    ) {}
}
