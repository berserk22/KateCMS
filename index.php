<?php

define('ROOT_DIR', dirname(__FILE__));
define('VERSION', '0.1');
chdir(dirname(__FILE__));

require_once 'init_autoload.php';

if (php_sapi_name()==='cli-server' && is_file(__DIR__.parse_url(filter_input(INPUT_SERVER, 'REQUEST_URI'), PHP_URL_PATH))){
    if ($argv!==null){
        $terminal=new \Core\Terminal($argv);
        $terminal->run();
    }
    else {
        return false;
    }
}
else {
    $route=explode('/', filter_input(INPUT_SERVER, 'REQUEST_URI'));
    if ($route[1]==='api'){
        $session=new \Core\Http\Session();
        $session->start();

        $api=new \Core\Api();
        $api->run();
    }
    else {
        $session=new \Core\Http\Session();
        $session->start();

        $app=new \Core\Application();
        $app->run();
    }
}
?>

