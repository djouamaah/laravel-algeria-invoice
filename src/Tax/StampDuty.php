<?php

namespace Djouamaah\AlgeriaInvoice\Tax;

use InvalidArgumentException;

/**
 * Stamp duty on cash payments (droit de timbre de quittance, Code du timbre art. 100-I).
 *
 * The rules are configurable (config('algeria-invoice.stamp_duty')). Without configuration the
 * Finance Law 2025 defaults apply; see the config file for the points that still need confirmation.
 */
class StampDuty
{
    protected string $mode;

    protected float $slice;

    protected float $exemptUpTo;

    /** @var array<int, array{0: float|null, 1: float}> */
    protected array $brackets;

    protected float $minimum;

    protected ?float $maximum;

    public function __construct(array $config = [])
    {
        $this->mode = $config['mode'] ?? 'whole';
        $this->slice = (float) ($config['slice'] ?? 100);
        $this->exemptUpTo = (float) ($config['exempt_up_to'] ?? 300);
        $this->brackets = $config['brackets'] ?? [[30000, 1.0], [100000, 1.5], [null, 2.0]];
        $this->minimum = (float) ($config['minimum'] ?? 5);
        $this->maximum = isset($config['maximum']) ? (float) $config['maximum'] : null;

        if (! in_array($this->mode, ['whole', 'marginal'], true)) {
            throw new InvalidArgumentException("Unknown stamp duty mode [{$this->mode}], use 'whole' or 'marginal'.");
        }
        if ($this->slice <= 0 || $this->brackets === [] || end($this->brackets)[0] !== null) {
            throw new InvalidArgumentException('The last stamp duty bracket must have no upper limit (null).');
        }
    }

    /**
     * Stamp duty in centimes for an amount in centimes.
     */
    public function calculate(int $centimes): int
    {
        $amount = abs($centimes) / 100;

        if ($amount <= 0 || $amount <= $this->exemptUpTo) {
            return 0;
        }

        $duty = $this->mode === 'whole' ? $this->whole($amount) : $this->marginal($amount);
        $duty = max($duty, $this->minimum);

        if ($this->maximum !== null) {
            $duty = min($duty, $this->maximum);
        }

        return (int) round($duty * 100);
    }

    /**
     * Every started slice of the whole amount at the rate of the bracket the amount falls in.
     */
    protected function whole(float $amount): float
    {
        foreach ($this->brackets as [$upTo, $rate]) {
            if ($upTo === null || $amount <= $upTo) {
                return $this->slices($amount) * $rate;
            }
        }

        return 0.0;
    }

    /**
     * Each bracket's rate applies only to the part of the amount inside that bracket.
     */
    protected function marginal(float $amount): float
    {
        $duty = 0.0;
        $lower = 0.0;

        foreach ($this->brackets as [$upTo, $rate]) {
            $part = ($upTo === null ? $amount : min($amount, $upTo)) - $lower;
            if ($part <= 0) {
                break;
            }
            $duty += $this->slices($part) * $rate;
            $lower = (float) $upTo;
            if ($upTo === null || $amount <= $upTo) {
                break;
            }
        }

        return $duty;
    }

    protected function slices(float $amount): int
    {
        // tolerate float noise such as 30000.000000001
        return (int) ceil(round($amount / $this->slice, 6));
    }
}
