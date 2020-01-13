<?php

return [
    'host'=>(filter_input(INPUT_SERVER, 'SERVER_PORT')==='443'?'https':'http').'://'.filter_input(INPUT_SERVER, 'SERVER_NAME').(filter_input(INPUT_SERVER, 'SERVER_PORT')!=='80'&&filter_input(INPUT_SERVER, 'SERVER_PORT')!=='443'?':'.filter_input(INPUT_SERVER, 'SERVER_PORT'):'').'/',
    'router'=>[
        'home'=>[
            'options' => [
                'route' => '/[:page_type/][:page_name/]',
                'constrain' => [
                    'page_type' => '[a-z][a-zA-Z0-9_-]*',
                    'page_name' => '[a-z][a-zA-Z0-9_-]*',
                ],
                'default'=>[
                    'controller' => 'App\Controller\PageController',
                    'action'=>'index',
                ],
            ],
        ],
        'dashboard'=>[
            'options' => [
                'route' => '/dashboard/[:page_name/]',
                'constrain' => [
                    'page_name' => '[a-z][a-zA-Z0-9_-]*',
                ],
                'default'=>[
                    'controller' => 'App\Controller\DashboardController',
                    'action'=>'index'
                ]
            ],
        ],
        'api'=>[
            'options' => [
                'route' => '/api/[:page_name/]',
                'constrain' => [
                    'page_name' => '[a-z][a-zA-Z0-9_-]*',
                ],
                'default'=>[
                    'controller' => 'Api\Controller\ApiController',
                    'action' => 'index',
                ]
            ],
        ],
    ],
];