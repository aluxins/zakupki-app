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
    'gov_ru_parser' => [
        'server_uri' => env('GOV_RU_SERVER', 'https://zakupki.gov.ru'),
        'base_uri' => env('GOV_RU_HOST', 'https://zakupki.gov.ru/epz/order/'),
        'base_uri_purchases' => env('GOV_RU_HOST_PURCHASES', 'https://zakupki.gov.ru/epz/order/extendedsearch/results.html'),
        'base_uri_purchase_fz44' => env('GOV_RU_HOST_PURCHASE_FZ44', 'https://zakupki.gov.ru/epz/order/notice/ea20/view/'),
        'base_uri_purchase_fz223' => env('GOV_RU_HOST_PURCHASE_FZ223', 'https://zakupki.gov.ru/epz/order/notice/notice223/'),

        'param' => [
            'sortBy' => 'PUBLISH_DATE',
            'recordsPerPage' => '_100', // Количество закупок на странице
            'morphology' => 'on',
            'search-filter' => 'Дате+размещения',
            'pageNumber' => 1,
            'sortDirection' => false,
            'showLotsInfoHidden' => false,
            'fz44' => 'on',
            'fz223' => 'on',
            'af' => 'on',
            'currencyIdGeneral' => -1,
            'okpd2IdsWithNested' => 'on',
            'okpd2Ids' => env('GOV_RU_HOST_PURCHASE_CLASSIFIERS_CODE',  '8874505,8874451,8874517,8874468,8874513'),
            'okpd2IdsCodes' => env('GOV_RU_HOST_PURCHASE_CLASSIFIERS', '31.01,25.99,32.99,27.40,32.30')
        ]
    ]
];
