<?php

declare(strict_types=1);

namespace App\Service\Admin;

readonly class DailyVolume
{
    /**
     * @param numeric-string $amount  minor units
     * @param int<0, 100>    $percent share of the busiest day, for the CSS bar width
     */
    public function __construct(public \DateTimeImmutable $day, public int $count, public string $amount, public int $percent)
    {
    }
}
