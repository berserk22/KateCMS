<?php

namespace Core\Http;

use Core\Config\Config;

class Session  implements \SessionHandlerInterface {

    private static $status=[
        0=>'Session is desabled.',
        1=>'Session is inactive.',
        2=>'Session is active.'
    ];
    
    protected static $session_id;

    protected $name;

    protected $lifetime=86400;

    protected $path;
    
    protected $data=[];

    public function __construct() {
        $this->name = self::getConfig()['name'];
        $this->lifetime = isset(self::getConfig()['lifetime'])?self::getConfig()['lifetime']:$this->lifetime;
        $this->path = self::getConfig()['session_path'];
        
        if (self::isStart()){
            $this->data=$_SESSION[$this->name];
        }
    }

    public function start(){
        if (!self::isStart()){
            session_name($this->name);
            session_save_path($this->path);
            session_set_cookie_params($this->lifetime);
            session_start();
        }
    }
    
    private static function getConfig(){
        return Config::getConfig('sessions');
    }
    
    public static function isStart(){
        if (self::getStatus()['code']===2){
            return true;
        }
        else {
            return false;
        }
    }
    
    public static function getStatus(){
        $code=session_status();
        return [
            'code'=>$code,
            'message'=>self::$status[$code]
        ];        
    }
    
    public static function getSessionId(){
        if (self::$session_id!==null){
            return self::$session_id;
        }
        else {
            self::$session_id=session_id();
            if (self::$session_id!==''){
                return self::$session_id;
            }
            else {
                return null;
            }
        }
    }
     
    public function set($name, $value){
        $_SESSION[$name]=$value;
        $this->commit();
    }
    
    public function get($name){
        if (array_key_exists($name, $_SESSION)){
            return $_SESSION[$name];
        }
        else {
            return $_SESSION;
        }
    }
    
    public function remove($name){
        unset($_SESSION[$name]);
        $this->commit();
    }

    protected function commit(){
        $this->write($this->getSessionId(), session_encode());
    }

    public function has($name){
        return array_key_exists($name, $_SESSION);
    }
    
    public function close() {}

    public function destroy($session_id) {}

    public function gc($maxlifetime) {}

    public function open($save_path, $name) {}

    public function read($session_id) {}

    public function write($session_id, $session_data) {}

}