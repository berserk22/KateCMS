<?php
return [
    'template'=>[
        'template_path'=>realpath(dirname(__FILE__).'/../').'/public/template',
        'view_manager' => [
            'not_found_template'       => 'error/404',
            'exception_template'       => 'error/index',
        ],
    ],
];