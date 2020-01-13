<?php

namespace Core\Form\Element;

/**
 * Description of Button
 *
 * @author Sergey Tevs
 */

class Button {
    
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
        $element='<button '.$attr.' />'.$this->options['label'].'</button>';
        return $element;
    }
}
