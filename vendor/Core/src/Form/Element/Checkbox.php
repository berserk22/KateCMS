<?php

namespace Core\Form\Element;

/**
 * Description of Checkbox
 *
 * @author Sergey Tevs
 */

class Checkbox {
    
    public function __construct(array $array = []) {
        foreach ($array as $key => $value) {
            $this->$key=$value;
        }
    }
    
    public function set($name, $value){
        $this->$name=$value;
    }
    
    public function get($name){
        return $this->$name;
    }
    
    public function getElement(){
        $attr.='';
        foreach ($this->attributes as $key => $value) {
            $attr.=' '.$key.'="'.$value.'"';
        }
        
        foreach ($this->values as $value) {
            $element[]='<input name="'.$this->name.'"'.$attr.' value="'.$value.'"/> '.$value;
        }
        return $element;
    }
}
