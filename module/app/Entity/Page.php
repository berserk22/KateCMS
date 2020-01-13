<?php

namespace App\Entity;

use Core\Interfaces\ArraySerializableInterface;

class Page implements ArraySerializableInterface {

    protected $id;

    protected $name;

    protected $title;

    protected $content;

    protected $thumb;

    protected $img;

    protected $keywords;

    protected $guid;

    protected $page_type;

    protected $status;

    protected $options;

    protected $created;

    protected $updated;

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