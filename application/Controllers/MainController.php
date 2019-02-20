<?php
namespace Controllers;

use Core\Controller;
use Models\MainModel;
use Core\View;

/*
Main system page controller
*/

Class MainController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->checkLogged();
        $this->model = new MainModel;
    }
    
    public function actionIndex()
    {	
        $data = $this->model->getData();
        $data['content'] = $this->view->generate('mainView');
        echo $this->view->generate('templateView',$data);
    }
}