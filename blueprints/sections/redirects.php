<?php

return [
    'type' => 'fields',
    'fields' => [
        'redirects' => [
            'type' => 'structure',
            'translate' => false,
            'fields' => [
                'fromuri' => [
                    'label' => [
                        'en' => 'Old',
                        'de' => 'Alt',
                    ],
                    'type' => 'text',
                    'required' => true,
                ],
                'touri' => [
                    'label' => [
                        'en' => 'New',
                        'de' => 'Neu',
                    ],
                    'type' => 'text',
                ],
                'code' => [
                    'label' => [
                        'en' => 'Status Code',
                        'de' => 'Status Code',
                    ],
                    'default' => '301',
                    'type' => 'select',
                    'options' => \Bnomei\Redirects::staticCache(
                        'codes',
                        fn () => array_map(
                            fn ($item) => [
                                'text' => $item['code'].': '.$item['label'],
                                'value' => $item['code'],
                            ],
                            \Bnomei\Redirects::codes()
                        )
                    ),
                ],
            ],
        ],
    ],
];
