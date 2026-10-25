<?php

namespace App\Support;

/** Pemformat angka gaya Indonesia. */
class Fmt
{
    public static function rp(float|int|null $n): string
    {
        return 'Rp '.number_format((float) $n, 0, ',', '.');
    }

    public static function num(float|int|null $n, int $dec = 1): string
    {
        $s = number_format((float) $n, $dec, ',', '.');

        return $dec > 0 ? rtrim(rtrim($s, '0'), ',') : $s;
    }
}
