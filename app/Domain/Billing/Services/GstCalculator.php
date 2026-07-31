<?php

namespace App\Domain\Billing\Services;

class GstCalculator
{
    /**
     * @param  array<array{lineTotal: float, gstRate: float}>  $items
     * @return array{cgst: float, sgst: float, igst: float, total: float}
     */
    public function calculate(array $items, bool $isIntraState): array
    {
        $totalTax = 0.0;

        foreach ($items as $item) {
            $totalTax += round($item['lineTotal'] * ($item['gstRate'] / 100), 2);
        }

        if ($isIntraState) {
            $half = round($totalTax / 2, 2);

            return ['cgst' => $half, 'sgst' => $totalTax - $half, 'igst' => 0.0, 'total' => $totalTax];
        }

        return ['cgst' => 0.0, 'sgst' => 0.0, 'igst' => $totalTax, 'total' => $totalTax];
    }

    /**
     * Same spa/customer state (or no customer state known, e.g. guest billing) is
     * treated as intra-state — CGST+SGST. Only a confirmed different state uses IGST.
     */
    public function isIntraState(?string $spaState, ?string $customerState): bool
    {
        if (! $spaState || ! $customerState) {
            return true;
        }

        return mb_strtolower(trim($spaState)) === mb_strtolower(trim($customerState));
    }
}
