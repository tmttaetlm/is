<?php
namespace Core;

use Core\View;

/*
Base class Controller
*/

class Controller {
    
    protected $model;
    protected $view;
    
    public function __construct() 
    {
        $this->view = new View;
    }

    protected function checkLogged() {

    if (!isset($_SESSION['userIin'])) {
        header("Location:/user/login");    
    }
    
    }
    
}

