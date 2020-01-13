<?php

namespace Core\Helper;

use Core\Interfaces\ArraySerializableInterface;
use stdClass;

class stdObject extends stdClass implements ArraySerializableInterface {
    
    public function __construct(array $arguments = array()) {
        if (!empty($arguments)) {
            foreach ($arguments as $property => $argument) {
                if ($argument instanceOf Closure) {
                    $this->{$property} = $argument;
                } else {
                    $this->{$property} = $argument;
                }
            }
        }
    }
    
    public function __call($method, $arguments) {
        if (isset($this->{$method}) && is_callable($this->{$method})) {
            return call_user_func_array($this->{$method}, $arguments);
        } else {
            throw new \Exception("Fatal error: Call to undefined method stdObject::{$method}()");
        }
    }
    
    public function exchangeArray(array $array) {
        foreach ($array as $key => $value) {
            property_exists($this, $key)?$this->$key=$value:null;
        }
    }

    public function getArrayCopy() {
        return get_object_vars($this);
    }
    
    public function get($name) {
        return property_exists($this, $name)?$this->$name:null;
    }
    
    public function set($name, $value) {
        property_exists($this, $name)?$this->$name=$value:null;
    }

}
