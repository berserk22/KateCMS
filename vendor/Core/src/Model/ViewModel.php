<?php

namespace Core\Model;

use Core\Model\Template;
use Core\Config\Config;
use Core\MySQL\PDOMySQL;
use Core\Helper\LayoutHelper;
use Core\Entity\Setting;

class ViewModel {

    protected $template;
    
    protected $manueleTemplate;

    protected $variables=[];
    
    protected $layout='layout/layout.phtml';
    
    protected $templatePath;
    
    protected $tmp_template;
    
    protected $layot_veriables=[];
    
    protected $header;
    
    protected $em;

    public function setEntityManager(PDOMySQL $em) {
        $this->em = $em;
    }

    public function getEntityManager() {
        if (null === $this->em) {
            $this->em = new PDOMySQL();
        }
        return $this->em;
    }
    
    public function __construct(){
        $this->template=new Template();
    }

    public function setHeader(array $options=[]){
        $header='';
        foreach ($options as $option) {
            foreach ($option as $key => $parameter) {
                $header.='<'.$key;
                if (!is_array($parameter)){
                    $header.='>'.$parameter.'</'.$key;
                }
                else{
                    foreach ($parameter as $option => $value) {
                        if (!is_array($value)){
                            $header.=' '.$option.'="'.$value.'"';
                        }
                    }
                    $header.=' /';
                }
                $header.='>';
            }
        }
        $this->header.=$header;
    }

    public function getHeader(){
        return $this->header;
    }
    
    public function setTemplate($template){        
        $module=explode('\\', $template);
        if ($module[0]!==$template){
            $this->tmp_template=mb_strtolower(str_replace(['Action','Controller','\\', $module[0]], '', $template)).'.phtml';
        }
        else {
            $this->tmp_template=$template.'.phtml';
        }
    }
    
    public function getTemplate(){
        return $this->tmp_template;
    }
    
    public function setTemplatePath($template){
        $this->setLayoutVariables([
            'theme'=>$template
        ]);
        $this->templatePath=$this->template->getTemplatePath().'/'.$template.'/';
    }
    
    public function getTemplatePath(){
        if ($this->templatePath===null){
            $template=$this->getEntityManager()->findByOne(Setting::class, ['key'=>'theme']);
            $this->setTemplatePath($template->__get('value'));
        }
        return $this->templatePath;
    }
    
    public function setLayout($layout){
        $this->layout=$layout.'.phtml';
    }
    
    public function getLayout(){
        return $this->layout;
    }
    
    protected function getLayoutPath(){
        return $this->getTemplatePath().$this->getLayout();
    }

    public function setVariable($name, $value){
        $this->variables[$name]=$value;
    }
    
    public function setVariables(array $variables){
        $this->variables=array_merge($this->variables, $variables);
    }
    
    public function getVariable($name){
        return isset($this->variables[$name])?$this->variables[$name]:null;
    }
    
    public function getVariables(){
        return $this->variables;
    }
        
    public function setLayoutVariables(array $variables){
        $this->layot_veriables= array_merge($this->layot_veriables, $variables);
    }
    
    public function getLayoutVariables(){
        return $this->layot_veriables;
    }
    
    public function header(){
        return $this->template;
    }
    
    protected function fetchPartial($template, array $params = null){
        if(!file_exists($template)){
            $template = $this->getTemplatePath().Config::getConfig('template')['view_manager']['exception_template'].'.phtml';
        }

        if ($params!==null){
            extract($params);
        }
        ob_start();
        require_once $template;
        return ob_get_clean();
    }
 
    public function renderPartial(){
        $tmpContentArray=$this->getVariables();
        $tmpContentArray['helper']=new LayoutHelper();

        return $this->fetchPartial($this->getTemplatePath().$this->getTemplate(), $tmpContentArray);
    }
 
    protected function fetch(){
        $tmpContentArray=$this->getVariables();
        $tmpContentArray['helper']=new LayoutHelper();

        $content = $this->fetchPartial($this->getTemplatePath().$this->getTemplate(), $tmpContentArray);
        $tmpArray=$this->getLayoutVariables();
        $tmpArray['header']=$this->getHeader();
        $tmpArray['content']=$content;
        $tmpArray['helper']=new LayoutHelper();

        return $this->fetchPartial($this->getLayoutPath(), $tmpArray);
    }
 
    public function render(){
        return $this->fetch();
    }
}