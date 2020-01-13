<?php

namespace Core\Form;

/**
 * Description of Form
 *
 * @author Sergey Tevs
 */

use Core\Form\Filter\InputFilter;

class Form {
    
    protected $attributes = [
        'method' => 'POST',
    ];
    
    public $element=[];
        
    protected $filter;
    
    protected $formFilter;
    
    protected $isValid = false;
    
    public function __construct(){
        $this->filter=new InputFilter();
    }
        
    public function setAttributes($array = []){
        if (!is_array($array)) {
            throw new \Exception(sprintf(
                '%s expects an array argument; received "%s"',
                __METHOD__,
                (is_object($array) ? get_class($array) : gettype($array))
            ));
        }
        $this->attributes=array_merge($this->attributes, $array);
        return $this;
    }
    
    public function add($array = []){
        if (!is_array($array)) {
            throw new \Exception(sprintf(
                '%s expects an array argument; received "%s"',
                __METHOD__,
                (is_object($array) ? get_class($array) : gettype($array))
            ));
        }
        $this->element[$array['name']]=new $array['type']($array);
        return $this;
    }
    
    public function setData($data) {
        if (!is_array($data)) {
            throw new \Exception(sprintf(
                '%s expects an array argument; received "%s"',
                __METHOD__,
                (is_object($data) ? get_class($data) : gettype($data))
            ));
        }
        foreach ($this->element as $name => $setting) {
            if (isset($data[$name])){                
                $this->element[$name]->set('value', $data[$name]);
            }
        }
        return $this;
    }
    
    public function setFormFilter($filter){
        $this->formFilter=$filter;
        var_dump($this->formFilter);
    }
    
    public function getData(){
        $data=[];
        foreach ($this->element as $name => $setting) {
            $data[$name]=$setting->get('value');
        }
        return $data;
    }
    
    public function isValid(){
        foreach ($this->element as $name => $element) {
            $filters=$this->formFilter->getElement($name);
            
            foreach ($filters['filters'] as $key => $filter) {
                
                var_dump($filter);
                
                //$element->value=$this->filter->$filter($element->value);
            }
            
            foreach ($filters['validators'] as $key => $validator) {
                //$this->filter->$validator($element->value);
            }
        }
        
        if (count($this->filter->getMessages())>0)
            $this->isValid=false;
        else
            $this->isValid=true;
        
        return $this->isValid;
    }
    
    public function getError(){
        return $this->filter->getMessages();
    }
    
    public function get($name){
        return $this->element[$name]->getElement();
    }
    
    public function label($name){
        return $this->element[$name]->getLabel();
    }
    
    public function open(){
        $form='<form';
        foreach ($this->attributes as $key => $value) {
            $form.=' '.$key.'="'.$value.'"';
        }
        $form.='>';
        return $form;
    }
    
    public function close(){
        return '</form>';
    }
}
