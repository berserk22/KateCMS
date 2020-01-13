<?php

namespace App\Controller;

use Core\AbstractController;
use Core\Model\ViewModel;
use Core\MySQL\PDOMySQL;

class ErrorController extends AbstractController {

    protected $em;

    protected function setEntityManager(PDOMySQL $em) {
        $this->em = $em;
    }

    protected function getEntityManager() {
        if (null === $this->em) {
            $this->em = new PDOMySQL();
        }
        return $this->em;
    }

    public function indexAction(){
        $view=new ViewModel();
        $view->setHeader(['title'=>'Page Not Found']);
        $view->setLayout('layout/layout');
        return $view;
    }

    public function notFoundAction(){
        $view=new ViewModel();
        $view->setHeader(['title'=>'Page Not Found']);
        $view->setLayout('layout/layout');
        return $view;
    }

    public function errorAction(){
        $view=new ViewModel();
        $view->setHeader(['title'=>'Error!!!']);
    }

    public function apiAction(){
        $view=new JsonModel();
        $view->setStatusCode(404);
        return $view;
    }

}