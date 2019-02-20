<?php
namespace Models;
use Core\Model;
use Components\Ad;
use Components\Db;

class UserModel extends Model 
{
    public function __construct() {
        parent::__construct();
    }   
    public function signIn($data)
    {
        $result = $this->user->signIn($data['login'],$data['password']);
        
        if ($result) 
        {
            header('Location: /');
            exit;
        } 
        else
        {
           return 'Неверное имя пользователя или пароль';
        }
    }

}
               
