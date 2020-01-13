<?php

namespace Core\Model;

use Core\Config\Config;
use Core\MySQL\PDOMySQL;
use Core\Entity\Setting;

class Template {

    protected $em;

    protected $config;

    public $defaultTemplate='default';
    
    public $adminTemplate='dashboard';
    
    protected $TemplateListe=array();
    
    protected $activeTemplate;
    
    protected $templatePath='';

    public function __construct() {
        $this->config = Config::getConfig('template');
        $this->setTemplatePath($this->config['template_path']);
    }
    
    public function setEntityManager(PDOMySQL $em) {
        $this->em = $em;
    }

    public function getEntityManager() {
        if (null === $this->em) {
            $this->em = new PDOMySQL();
        }
        return $this->em;
    }
        
    private function setTemplatePath($path=null){
        if ($path!==null){
            $this->templatePath=$path;
        }
    }
    
    public function getTemplatePath(){
        return $this->templatePath;
    }
    
    public function getTemplateListe(){
        $dir_open=opendir($this->getTemplatePath());
        while (FALSE !== ($folder_name = readdir($dir_open))){
            if (!is_dir($folder_name)){
                $this->TemplateListe[]=$folder_name;
            }
        }
        return $this->TemplateListe;
    }
        
    public function getActiveTemplate(){
        if (!$this->activeTemplate){
            $activ=$this->getEntityManager()->findByOne(Setting::class, ['key'=>'theme']);
            if (!$activ){                
                foreach ($this->getTemplateListe() as $temp_template) {
                    if ($this->defaultTemplate===$template)
                        $temp_template=$this->defaultTemplate;
                }
            }
            else {
                $this->setActiveTemplate($activ->__get('value'));
            } 
            
            if (isset($temp_template)){
                $this->setActiveTemplate($temp_template);
            }
        }
        return $this->activeTemplate;
    }
    
    public function getTemplateName(){
        $activ=$this->getEntityManager()->findByOne(Setting::class, ['key'=>'theme']);
        return $activ->__get('value');
    }
    
    public function setActiveTemplate($template){
        $this->activeTemplate=$template;
    }
    
    public function getDefaultTemplate(){
        return $this->defaultTemplate;
    }
    
    public function getAdminTemplate(){
        return $this->adminTemplate;
    }
    
}