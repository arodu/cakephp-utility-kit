<?php
declare(strict_types=1);

return [
    'posts' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
                'autoIncrement' => true,
            ],
            'title' => [
                'type' => 'string',
                'length' => 255,
                'null' => true,
            ],
            'organization_id' => [
                'type' => 'integer',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => ['id'],
            ],
        ],
    ],
];
