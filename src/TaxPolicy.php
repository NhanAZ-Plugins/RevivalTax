<?php

declare(strict_types=1);

namespace NhanAZ\RevivalTax;

use InvalidArgumentException;

final class TaxPolicy {

	public static function validatePercent(mixed $value) : float {
		if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value < 0 || $value > 100) {
			throw new InvalidArgumentException('taxPercent must be a finite number between 0 and 100');
		}
		return (float) $value;
	}

	public static function calculateTax(float $balance, float $percent) : float {
		if (!is_finite($balance) || $balance < 1.0 || $percent <= 0.0) {
			return 0.0;
		}
		return min($balance, round($balance * $percent / 100, 2));
	}
}
