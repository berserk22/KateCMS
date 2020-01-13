<?php

namespace Core\Render;

class PhpRender {
    
    protected $view_model;

    public function __construct($view_model = null) {
        if ($view_model!==null){
            $this->view_model=$view_model;
        }
    }
    
    public function render(){
        if ($this->view_model->getLayout()!=='.phtml') echo $this->view_model->render();
        else echo $this->view_model->renderPartial();
    }
        
    public function JsonRender(){
        if ($this->view_model!==null){
            echo json_encode($this->view_model->render(), JSON_UNESCAPED_UNICODE);
        }
    }
    
}