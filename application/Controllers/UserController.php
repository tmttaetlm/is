<?php
namespace Controllers;

use Core\Controller;
use Components\Ad;
use Models\UserModel;
use Core\View;


Class UserController extends Controller
{
    public function __construct() 
    {
        parent::__construct();
        $this->model = new UserModel;
        $this->view = new View;
    }
    
    public function actionLogin() 
    {
        if (isset($_SESSION['userIin'])) {
            header("Location:/main/index");
        }
        $data['user'] = null;
        $data['admin'] = false;
        $data['content'] = $this->view->generate('user/login');
        echo $this->view->generate('templateView',$data);
    }

    public function actionSignin() 
    {
        if (isset($_POST['signIn'])) {
            $data['msg'] = $this->model->signIn($_POST);
            $data['user'] = null;
            $data['admin'] = false;
            $data['content'] = $this->view->generate('user/login',$data);
            echo $this->view->generate('templateView',$data);
        }
       
    }

    public function actionNoaccess($msg = null) 
    {
        $data['msg'] = $msg;
        $data['content'] = $this->view->generate('user/noAccess',$data);
        $data['user'] = $this->model->user->getFullName();
        $data['admin'] = $this->model->user->checkAdmin();
        echo $this->view->generate('templateView',$data);
    }

    public function actionLogout() 
    {
        unset($_SESSION['userIin']);
        session_destroy();
        header("Location:/main/index");
    }
}