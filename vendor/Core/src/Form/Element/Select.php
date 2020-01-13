<?php

namespace Core\Form\Element;

/**
 * Description of Select
 *
 * @author Sergey Tevs
 */

class Select {
    
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
        $element='<select name="'.$this->name.'"'.$attr.'>';
        $options='<option>'.$this->options['empty_value'].'</option>';
        foreach ($this->value_options as $key => $value) {
            $options.='<option value="'.$key.'">'.$value.'</option>';
        }
        $element.=$options;
        $element.='</select>';
        return $element;
    }
    
    public function getLabel(){
        if (isset($this->options['label'])){
            return '<label>'.$this->options['label'].'</label>';
        }
    }
    
}
