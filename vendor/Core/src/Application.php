<?php

namespace Core;

use Core\Router\Router;
use Core\Render\PhpRender;
use Core\Config\Config;

class Application {

    protected $router;
    
    public function __construct() {
        $this->router=new Router();
    }

    public function run(){
        $data=$this->router->getRouter();  
        $obj=new $data['controller']();    
        if (!isset($data['controller']) || $data['controller']==='' || $data['controller']==='App\Controller\ErrorController'){
            $result=call_user_func([(new \App\Controller\ErrorController()), 'notFoundAction']);   
            $result->setTemplatePath($result->header()->getActiveTemplate());  
        }
        else {
            $result=call_user_func([$obj, $data['action']]);
            if ($data['controller']==='App\Controller\DashboardController'){
                $result->setTemplatePath('dashboard');
                
            }
            else {               
                $result->setTemplatePath($result->header()->getActiveTemplate());
            }
        }
        if ($result->getTemplate()===null){
            $result->setTemplate($data['controller'].'/'.$data['action']);
        }        
        $render=new PhpRender($result);
        $render->render();
    }
}