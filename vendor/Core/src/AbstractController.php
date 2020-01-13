<?php

namespace Core;

use Core\Model\ViewModel;

abstract class AbstractController {
    
    public abstract function indexAction();
            
    public abstract function notFoundAction();
    
}