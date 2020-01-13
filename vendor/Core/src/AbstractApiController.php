<?php

namespace Core;

/**
 * Description of AbstractApiController
 *
 * @author Sergey Tevs
 */

use Core\Model\JsonModel;

abstract class AbstractApiController {
    
    public abstract function indexAction();
    
    public function notFoundAction(){
        $view=new JsonModel();
        $view->setTemplate('error/api/404');
        return $view;
    }
}
