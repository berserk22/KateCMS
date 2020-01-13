<?php

return [
    'host'=>(filter_input(INPUT_SERVER, 'SERVER_PORT')==='443'?'https':'http').'://'.filter_input(INPUT_SERVER, 'SERVER_NAME').(filter_input(INPUT_SERVER, 'SERVER_PORT')!=='80'&&filter_input(INPUT_SERVER, 'SERVER_PORT')!=='443'?':'.filter_input(INPUT_SERVER, 'SERVER_PORT'):'').'/',
    'router'=>[
        'home'=>[
            'options' => [
                'route' => '/[:lang/][:action/]',
                'constrain' => [
                    'lang'=> '[a-z]{2}',
                    'action' => '[a-z][a-zA-Z0-9_-]*',
                ],
                'default'=>[
                    'lang'=>'de',
                    'controller' => 'App\Controller\PageController',
                    'action'=>'index',
                ]
            ],
        ],
        'blog'=>[
            'options' => [
                'route' => '/[:lang/]blog/[:action/]',
                'constrain' => [
                    'lang'=> '[a-z]{2}',
                    'action' => '[a-z][a-zA-Z0-9_-]*',
                ],
                'default'=>[
                    'lang'=>'de',
                    'controller' => 'App\Controller\PageController',
                    'action'=>'index',
                ]
                
            ],
        ],
        'general'=>[
            'options' => [
                'route' => '/[:lang/][:page_name/]',
                'constrain' => [
                    'controller' => '[a-z][a-zA-Z0-9_-]*',
                    'action' => '[a-z][a-zA-Z0-9_-]*',
                    'lang'=> '[a-z]{2}'
                ],
            ],
        ],
        'dashboard'=>[
            'options' => [
                'route' => 'dashboard/[:action/]',
                'constrain' => [
                    'controller' => 'App\Controller\DashboardController',
                    'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                ],
            ],
        ],
        'api'=>[
            'options' => [
                'route' => 'api/[:action/]',
                'constrain' => [
                    'controller' => 'Api\Controller\ApiController',
                    'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                ],
            ],
        ],
    ],
];