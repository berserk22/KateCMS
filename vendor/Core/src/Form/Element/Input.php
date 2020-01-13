<?php

namespace Core\Form\Element;

/**
 * Description of Input
 *
 * @author Sergey Tevs
 */

class Input {
        
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
        $element='<input name="'.$this->name.'"'.$attr.' />';
        return $element;
    }
    
    public function getLabel(){
        if (isset($this->options['label'])){
            return '<label>'.$this->options['label'].'</label>';
        }
    }
    
}