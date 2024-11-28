<?php

return [
    'storage' => [
        'path' => storage_path('bonsai/trees'),
        'format' => 'json',
    ],
    
    'aging' => [
        'interval' => 90, // days between aging checks
        'max_age' => 'ancient',
    ],
    
    'seasonal' => [
        'enabled' => true,
        'hemisphereNorth' => true,
    ],

    'styles' => [
        'formal' => [
            'description' => 'Classic upright style (Chokkan)',
            'curve' => 0,
            'complexity' => 1.0,
        ],
        'informal' => [
            'description' => 'Informal upright style (Moyogi)',
            'curve' => 0.15,
            'complexity' => 1.2,
        ],
        'slanting' => [
            'description' => 'Slanting style (Shakan)',
            'curve' => 0.3,
            'complexity' => 1.1,
        ],
        'cascade' => [
            'description' => 'Cascade style (Kengai)',
            'curve' => -0.5,
            'complexity' => 1.4,
        ],
    ],

    'characters' => [
        'trunk' => ['│', '─', '┌', '┐', '└', '┘', '├', '┤', '┬', '┴', '┼'],
        'leaves' => [
            'spring' => ['❀', '✿', '♠'],
            'summer' => ['☘', '❦', '❧'],
            'fall' => ['✾', '❁', '⚘'],
            'winter' => ['❄', '❆', '❅'],
        ],
    ],
]; 