<?php
namespace App\Entity;

use Core\Interfaces\ArraySerializableInterface;

class Users implements ArraySerializableInterface {

    protected $id;
        
    protected $firstname;
    
    protected $lastname;
    
    protected $sex;

    protected $email;
    
    protected $password;
    
    protected $options;
    
    protected $token_auth;
    
    protected $ban;

    protected $created;
    
    protected $updated;
    
    protected $activation;

    public function __get($name) {
        return property_exists($this, $name)?$this->$name:null;
    }
    
    public function __set($name, $value) {
        property_exists($this, $name)?$this->$name=$value:null;
    }
    
    public function exchangeArray(array $array) {
        foreach ($array as $key => $value) {
            property_exists($this, $key)?$this->$key=$value:null;
        }
    }

    public function getArrayCopy() {
        return get_object_vars($this);
    }

}