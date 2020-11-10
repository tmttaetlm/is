<?php

namespace Controllers;
use Core\Controller;
use Core\View;
use Models\UserModel;
use Models\AdminModel;

/**
 * Admin panel controller
 */
class AdminController extends Controller{
    
    public function __construct() 
    {
        parent::__construct();
        $this->checkLogged();
        $this->model = new AdminModel;
        $this->model->user->canAccess('adminPanel');
    }
    
    public function actionIndex()
    {
        $data['radioName']='tab';
        
        $data['tabItems']['userControl'] = 'Управление пользователями';
        if ($this->model->user->getIin() == '841208350084'||$this->model->user->getIin() == '920812350558') {
            $data['tabItems']['roleSettings']='Настройка ролей';
        }
        
        
        $data['admin'] = $this->model->user->checkAdmin();
        $data['users'] = $this->model->getUserList();
        $data['roles'] = $this->model->getRoles2();
        $data['tabData']['userControl'] = $this->view->generate('admin/userList',$data);
        $data['permissions'] = $this->model->getAllPermissions();
        $data['tabData']['roleSettings'] = $this->view->generate('admin/roleSettings',$data);
        $data['systemTitle'] = 'Панель администратора';
        $data['content'] = $this->view->generate('framework/tabs',$data);
        $data['content'] = $this->view->generate('framework/system',$data);
        $data['admin'] =  $this->model->user->checkAdmin();
        $data['user'] = $this->model->user->getFullName();
        echo $this->view->generate('templateView',$data);
    }

    public function actionDeleteuser()
    {
        if (isset($_POST['iin'])){
            $this->model->user->deleteUser($_POST['iin']);
        }
        $data['roles'] = $this->model->getRoles2();
        $data['users'] = $this->model->user->getAllUsers();
        echo $this->view->generate('admin/userList',$data);
    }

    public function actionUpdateuserlist() 
    {
        $data['roles'] = $this->model->getRoles2();
        $data['users'] = $this->model->user->getAllUsers();
        echo $this->view->generate('admin/userList',$data);
    }
    
    public function actionChangeuserrole()
    {
        if (isset($_POST['iin'])){
            echo $this->model->user->changeRole($_POST['iin'],$_POST['role']);
        }
    }

    public function actionAddrole()
    {
        if (isset($_POST['role'])){
            echo $this->model->addRole($_POST['role']);
        }
    }

    public function actionGetroles()
    {
        $this->model->getRoles();
    }

    public function actionDeleterole()
    {
        if (isset($_POST['id'])) {
            echo $this->model->deleteRole($_POST['id']);
        }
    }

    public function actionSetprivtorole()
    {
        if (isset($_POST['mode'])) {
            $this->model->setPrivToRole($_POST['mode'],$_POST['roleId'],$_POST['privId']);
        }
    }

    public function actionGetpermissionsbyrole() {
        if (isset($_POST['roleId'])){
            $this->model->getPermissionsByRole($_POST['roleId']);
        }
    }

    public function actionGetallprivileges() {
        $data = $this->model->getAllPermissions();
        $permissions = [];
        foreach($data as $perm){
            $permissions[] = $perm['access'];
        }
        header('Content-Type: application/json');
        echo json_encode($permissions);
    }

    public function actionDeleteOldEntries(){
        echo $this->model->deleteOldEntries($_POST['date']);
    }
}
