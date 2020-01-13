<?php

namespace Core\Log;

use Core\Config\Config;

class Logger {
    
    const Log_File=".log";
    
    const Debug_File=".debug.log";
    
    const Error_File=".error.log";
    
    public static function Error($str){
        $Log=self::getConfig()['error_path'].'/'.__FUNCTION__.'_'.date("d.m.Y").self::Error_File;
        if(!file_exists($Log)){
            $handle=fopen($Log, 'w+');
        }
        else {
            $handle=fopen($Log, 'a+');
        }
        if($handle!==FALSE){
            fwrite($handle, '[ '.date("d.m.Y H:i:s").' ] '.__FUNCTION__.": ".$str ."\r\n");
            fclose($handle);
        }
    }
    
    public static function MySQLError($str){
        $Log=self::getConfig()['error_path'].'/'.__FUNCTION__.'_'.date("d.m.Y").self::Error_File;
        if(!file_exists($Log)){
            $handle=fopen($Log, 'w+');
        }
        else {
            $handle=fopen($Log, 'a+');
        }
        
        if($handle!==FALSE){
            fwrite($handle, '[ '.date("d.m.Y H:i:s").' ] '.__FUNCTION__.": ".$str ."\r\n");
            fclose($handle);
        }
    }
    
    public static function MySQLExecuteInfo($str){
        $Log=self::getConfig()['log_path'].'/'.__FUNCTION__.'_'.date("d.m.Y").self::Log_File;
        if(!file_exists($Log)){
            $handle=fopen($Log, 'w+');
        }
        else {
            $handle=fopen($Log, 'a+');
        }
        if($handle!==FALSE){
            fwrite($handle, '[ '.date("d.m.Y H:i:s").' ] '.__FUNCTION__.": ".$str ."\r\n");
            fclose($handle);
        }
    }
    
    public static function ErrorReport($str){
        $Log=self::getConfig()['error_path'].'/'.__FUNCTION__.'_'.date("d.m.Y").self::Error_File;
        if(!file_exists($Log)){
            $handle=fopen($Log, 'w+');
        }
        else {
            $handle=fopen($Log, 'a+');
        }
        if($handle!==FALSE){
            fwrite($handle, $str);
            fclose($handle);
        }
    }
    
    public static function MailReport($str){
        $Log=self::getConfig()['mail_path'].'/'.__FUNCTION__.'_'.date("d.m.Y").self::Log_File;
        if(!file_exists($Log)){
            $handle=fopen($Log, 'w+');
        }
        else {
            $handle=fopen($Log, 'a+');
        }
        if($handle!==FALSE){
            fwrite($handle, $str);
            fclose($handle);
        }
    }
    
    public static function ImapReport($str){
        $Log=self::getConfig()['mail_path'].'/'.__FUNCTION__.'_'.date("d.m.Y").self::Log_File;
        if(!file_exists($Log)){
            $handle=fopen($Log, 'w+');
        }
        else {
            $handle=fopen($Log, 'a+');
        }
        if($handle!==FALSE){
            fwrite($handle, $str);
            fclose($handle);
        }
    }
    
    public static function Info($str){
        $Log=self::getConfig()['log_path'].'/'.__FUNCTION__.'_'.date("d.m.Y").self::Log_File;
        if(!file_exists($Log)){
            $handle=fopen($Log, 'w+');
        }
        else {
            $handle=fopen($Log, 'a+');
        }
        if($handle!==FALSE){
            fwrite($handle, '[ '.date("d.m.Y H:i:s").' ] : '.$str ."\r\n");
            fclose($handle);
        }
    }
    
    public static function Debug($str){
        $Debug=self::getConfig()['debug_path'].'/'.__FUNCTION__.'_'.date("d.m.Y").self::Debug_File;
        if(!file_exists($Debug)){
            $handle=fopen($Debug, 'w+');
        }
        else {
            $handle=fopen($Debug, 'a+');
        }
        if($handle!==FALSE){
            fwrite($handle, '[ '.date("d.m.Y H:i:s").' ] : '.$str ."\r\n");
            fclose($handle);
        }
    }
    
    protected static function getConfig(){
        return Config::getConfig('log');
    }
}