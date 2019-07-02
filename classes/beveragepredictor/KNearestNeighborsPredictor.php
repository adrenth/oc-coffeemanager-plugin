<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Classes\BeveragePredictor;

use Carbon\Carbon;
use Phpml\Classification\KNearestNeighbors;

/**
 * Class KNearestNeighborsPredictor
 *
 * @package Adrenth\CoffeeManager\Classes\BeveragePredictor
 */
final class KNearestNeighborsPredictor implements BeveragePredictor
{
    /**
     * TODO: Cleanup, value objects and thorough testing
     *
     * {@inheritdoc}
     */
    public function predict(array $data): int
    {
        $classifier = new KNearestNeighbors();

        $samples = [];
        $labels = [];

        foreach ($data as $record) {
            $samples[] = $record['sample'];
            $labels[] = $record['label'];
        }

        $classifier->train($samples, $labels);

        // TODO: Predictions based on day AND hour/minute?
        $time = Carbon::now()->format('Hi');
        return $classifier->predict([$time, $time]);
    }
}
