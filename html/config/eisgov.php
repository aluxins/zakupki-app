<?php

return [
    'gosplan_info' => [
        'base_uri_fz44' => env('EISGOV_HOST_FZ44', 'https://v2test.gosplan.info/fz44/'),
        'base_uri_fz223' => env('EISGOV_HOST_FZ223', 'https://v2test.gosplan.info/fz223/'),
        'classifiers' => env('EISGOV_PURCHASES_CLASSIFIERS', '32.30'),
        'days_lookback' => env('EISGOV_DEFAULT_DAYS_LOOKBACK', 3),
        'stage' => 1, //Этап закупки. 1 - подача заявок, 2 - работа комиссии, 3 - закупка завершена, 4 - закупка отменена.
        'sort' => 'updated_at_asc',
        'paginator' => 100,
    ],
];
