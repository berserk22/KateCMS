<?php

namespace Core\Auth;

use Core\Http\Session;
use Core\MySQL\PDOMySQL;

class Authentication {

    protected $status=false;
    
    protected $admin_status=false;

    protected $hash;
    
    protected $session;
    
    protected $em;
    
    protected $secret='SAPSv2';

    // protected $secret='KateCMS';

    protected function setEntityManager(PDOMySQL $em) {
        $this->em = $em;
    }

    protected function getEntityManager() {
        if (null === $this->em) {
            $this->em = new PDOMySQL();
        }
        return $this->em;
    }

    public function __construct() {
        $this->session=new Session();
        $this->getStatus();
        $this->getAdminStatus();
    }
    
    public function getStatus(){
        if ($this->session->has('id')){
            $this->status=true;
        }
        else {
            $this->status=false;
        }
        return $this->status;
    }
    
    public function getAdminStatus(){
        if ($this->session->has('admin')){
            $this->admin_status=true;
        }
        else {
            $this->admin_status=false;
        }
        return $this->admin_status;
    }
    
    public function setAuth($id){
        $this->session->set('id', $id);
        $this->status=true;
    }
    
    public function setAdminAuth($login){
        if ($login!==null){
            $this->session->set('admin', $login);
            $this->admin_status=true;
        }   
        else {
            $this->admin_status=false;
        }
    }
    
    public function setHash($str){
        $this->hash=md5($this->secret.strrev($this->secret.md5($str)));
    }
    
    public function getHash(){
        return $this->hash;
    }
    
    public function getAuth(){
        return $this->session->get('id');
    }
    
    public function getAdminAuth(){
        return $this->session->get('admin');
    }
    
    public function removeAuth(){
        $this->session->remove('id');
        $this->status=false;
    }
    
    public function removeAdminAuth(){
        $this->session->remove('admin');
        $this->admin_status=false;
    }
    
    public function getSession(){
        return $this->session->read();
    }
    
    public function set($name, $value){
        $this->session->set($name, $value);
    }
    
    public function get($name){
        return $this->session->get($name);
    }

}