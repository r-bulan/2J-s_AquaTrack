<?php

namespace App\Services;

use App\Models\Setting;

class PricingService
{
    public function getPrice(string $gallonType): float
    {
        if (strcasecmp($gallonType, 'Flat') === 0) {
            return (float) Setting::get('flat_gallon_price', 40.00);
        }
        return (float) Setting::get('round_gallon_price', 35.00);
    }

    public function calculateTotal(string $gallonType, int $quantity): float
    {
        $price = $this->getPrice($gallonType);
        return round($price * max(1, $quantity), 2);
    }

    public function getPrices(): array
    {
        return [
            'Round' => (float) Setting::get('round_gallon_price', 35.00),
            'Flat' => (float) Setting::get('flat_gallon_price', 40.00),
        ];
    }

    public function updatePrices(float $roundPrice, float $flatPrice, ?ActivityLogService $activityLogService = null): void
    {
        $oldRound = (float) Setting::get('round_gallon_price', 35.00);
        $oldFlat = (float) Setting::get('flat_gallon_price', 40.00);

        Setting::set('round_gallon_price', number_format($roundPrice, 2, '.', ''));
        Setting::set('flat_gallon_price', number_format($flatPrice, 2, '.', ''));

        if ($activityLogService) {
            $activityLogService->log(
                action: 'Price Changed',
                entityType: 'Setting',
                entityId: null,
                description: sprintf(
                    'Changed Round Gallon Price from ₱%.2f to ₱%.2f, Flat Gallon Price from ₱%.2f to ₱%.2f',
                    $oldRound,
                    $roundPrice,
                    $oldFlat,
                    $flatPrice
                ),
                oldValues: ['round_gallon_price' => $oldRound, 'flat_gallon_price' => $oldFlat],
                newValues: ['round_gallon_price' => $roundPrice, 'flat_gallon_price' => $flatPrice]
            );
        }
    }
}
