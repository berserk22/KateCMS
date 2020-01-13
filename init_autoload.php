<?php

require_once ROOT_DIR.'/vendor/Loader.php';

$loader = new Loader();

$loader->register();

register_shutdown_function('ShutdownHandler');

set_error_handler('ErrorHandler');

$loader->addNamespace('Core', ROOT_DIR.'/vendor/Core/src');
$loader->addNamespace('PHPMailer\PHPMailer', ROOT_DIR.'/vendor/PHPMailer/src');

$loader->addNamespace('App', ROOT_DIR.'/module/app');
$loader->addNamespace('Api', ROOT_DIR.'/module/api');
$loader->addNamespace('Terminal', ROOT_DIR.'/module/terminal');


/********************************************** */
/* Plugins Loader */

$plugins_dir=ROOT_DIR.'/module/plugins';
if (!file_exists($plugins_dir)){
    @mkdir($plugins_dir, 0755);
}
$dir_hndl = opendir($plugins_dir);
while (false !== ($name = readdir($dir_hndl))) {
    if (!is_file($plugins_dir)){
        if ($name==='.' || $name==='..') {}
        else {
            $plugin_name='';
            foreach(explode('_', $name) as $tmp){
                $plugin_name.=ucfirst($tmp);
            }
            $loader->addNamespace('Plugins\\'.$plugin_name, $plugins_dir.'/'.$name.'/src');
        }
    }
}
closedir($dir_hndl);