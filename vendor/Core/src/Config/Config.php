<?php

namespace Core\Config;

class Config {

    static $stat_conf=[];
        
    public static function getConfig($param=null){
        if (self::$stat_conf===null || count(self::$stat_conf)===0){
            $config_dir=ROOT_DIR.'/config/';
            if (!file_exists($config_dir)){
                @mkdir($config_dir, 0775);
            }
            $dir_hndl = opendir($config_dir);
            while (false !== ($name = readdir($dir_hndl))) {
                if (!is_file($config_dir)){
                    if ($name==='.' || $name==='..') {}
                    else {
                        if(stristr($name,'.config.php')!==FALSE){
                            $file=$config_dir.$name;
                            $conf_tmp=require $file;
                            if (is_array($conf_tmp)){
                                self::$stat_conf=array_merge(self::$stat_conf, $conf_tmp);
                            }
                        }
                    }
                }
            }
            closedir($dir_hndl);
        }
        if ($param!==null){
            return self::$stat_conf[$param];
        }
        else {
            return self::$stat_conf;
        }
    }

}