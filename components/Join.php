<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Components;

use Cms\Classes\ComponentBase;

/**
 * Class Join
 *
 * @package Adrenth\CoffeeManager\Console
 */
class Join extends ComponentBase
{
    /**
     * {@inheritdoc}
     */
    public function componentDetails(): array
    {
        return [
            'name' => 'Join Component',
            'description' => '',
        ];
    }
}
