<?php

namespace Core\Form\Filter;

/**
 * Description of FormFilter
 *
 * @author Sergey Tevs
 */

class FormFilter {
    
    protected $error=[
        'isInt'=>[
            'Der Eingabewert ist keine ganze Zahl'
        ],
        'isFloat'=>[
            'Der Eingabewert scheint keine Gleitkommazahl zu sein'
        ],
        'notEmpty'=>[
            'Es wird ein Eingabewert benötigt. Dieser darf nicht leer sein'
        ],
        'lenght'=>[
            'Der Eingabewert ist weniger als %s Zeichen lang',
            'Der Eingabewert ist mehr als %s Zeichen lang'
        ],
        'isEmail'=>[
            'Der Eingabewert ist keine gültige E-Mail-Adresse.'
        ],
        'isStreetNumber'=>[
            'Straße muss mit Hausnummer sein'
        ],
        'checkFile'=>[
            'Es liegt kein Fehler vor, die Datei wurde erfolgreich hochgeladen.',
            'Die hochgeladene Datei überschreitet die in der Anweisung %s festgelegte Größe.',
            'Die hochgeladene Datei überschreitet die in dem HTML Formular mittels der Anweisung MAX_FILE_SIZE angegebene maximale Dateigröße.',
            'Die Datei wurde nur teilweise hochgeladen.',
            'Es wurde keine Datei hochgeladen.',
            'Fehlender temporärer Ordner.',
            'Speichern der Datei auf die Festplatte ist fehlgeschlagen.',
            'Eine PHP Erweiterung hat den Upload der Datei gestoppt.'
        ]
    ];
    
    protected $message=[];

    public function __construct() {}
    
    public function isInt($name, $value){
        if (!filter_var($value, FILTER_VALIDATE_INT)){
            $this->message[$name]=$this->error[__FUNCTION__];
            return false;
        }
        else {
            return true;
        }
    }
    
    public function isFloat($name, $value){        
        if (!filter_var($value, FILTER_VALIDATE_FLOAT)){
            $value=str_replace(',', '.', $value);
            if (!filter_var($value, FILTER_VALIDATE_FLOAT)){
                $this->message[$name]=$this->error[__FUNCTION__];
                return false;
            }
            else {
                return true;
            }
        }
        else {
            return true;
        }
    }
    
    public function notEmpty($name, $value){
        if ($value==='' || $value===null){
            $this->message[$name]=$this->error[__FUNCTION__];
            return false;
        }
        else {
            return true;
        }
    }
    
    public function lenght($name, $value, $min = 1, $max = 255){
        $strlen=strlen($value);
        if ($strlen<$min){
            $this->message[$name][0]=sprintf($this->error[__FUNCTION__][0], $min);
            return false;
        }
        else if ($strlen>$max){
            $this->message[$name][0]=sprintf($this->error[__FUNCTION__][1], $max);
            return false;
        }
        else {
            return true;
        }
    }
        
    public function isEmail($name, $value){
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)){
            $this->message[$name]=$this->error[__FUNCTION__];
            return false;
        }
        else {
            return true;
        }
    }
    
    public function isStreetNumber($name, $value){
        $match = array();
        preg_match('/^([^\d]*[^\d\s]) *(\d.*)$/', $value, $match);
        if (count($match) == 0) {
            $this->message[$name]=$this->error[__FUNCTION__];
            return false;
        }
        else{
            return true;
        }
    }
    
    public function checkFile($name, $file){
        if ($file['error']!==0 && $file['error']!==4){
            $error=$file['error'];
            if ($error===1){
                $this->message[$name][0]=sprintf($this->error[__FUNCTION__][$error], ini_get('upload_max_filesize'));
            }
            else {
                $this->message[$name][0]=$this->error[__FUNCTION__][$error];
            }
            return false;
        }
        else {
            return true;
        }
    }
    
    public function StripTags($str){
        return strip_tags($str);
    }
    
    public function StringTrim($str){
        return trim($str);
    }
    
    public function getMessages(){
        return $this->message;
    }
    
}
