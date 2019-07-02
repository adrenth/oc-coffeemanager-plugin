<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Classes\BeveragePredictor;

/**
 * Interface BeveragePredictor
 *
 * @package Adrenth\CoffeeManager\Classes\BeveragePredictor
 */
interface BeveragePredictor
{
    /**
     * TODO: Return value object and accept an array of value objects
     *
     * @param array $data
     * @return int
     */
    public function predict(array $data): int;
}
