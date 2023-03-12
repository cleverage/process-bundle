<?php

declare(strict_types=1);

use CleverAge\ProcessBundle\CleverAgeProcessBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;

return [
    FrameworkBundle::class => [
        'all' => true,
    ],
    CleverAgeProcessBundle::class => [
        'all' => true,
    ],
    MonologBundle::class => [
        'all' => true,
    ],
];
