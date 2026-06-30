<?php

declare(strict_types=1);

namespace CDG\Pricing\Support;

use CDG\Pricing\ValueObjects\PricingConfig;

/**
 * Maps a gross margin ratio to a business decision label.
 *
 * Thresholds come from PricingConfig so they are admin-editable without
 * touching this code. See DECISIONS.md D6 for the current defaults.
 */
final class Decision
{
    public static function fromMargin(float $margin, PricingConfig $config): string
    {
        if ($margin < $config->marginFloors['reject']) {
            return 'REJECT / REPRICE';
        }

        if ($margin < $config->marginFloors['review']) {
            return 'REVIEW';
        }

        if ($margin < $config->marginFloors['strong']) {
            return 'GOOD';
        }

        return 'STRONG';
    }
}
