<?php
/*
 * Class User
 */

namespace Core;
use Components\Ad;
use Components\Db;

class User 
{
    private $firstName, $lastName ,$iin ,$login, $role, $privileges; 

    public function __construct() 
    {
        $this->getUserFromDb();
    }
    
    //Get user data from DB, by user iin from session 
    private function getUserFromDb()
    {
        if (isset($_SESSION['userIin']))
        {
            $db = Db::getDb();
            $result = $db->selectQuery('SELECT * FROM user WHERE iin = :iin', ['iin'=>$_SESSION['userIin']]);
            $this->firstName = $result[0]['firstName'];
            $this->lastName = $result[0]['lastName'];
            $this->iin = $result[0]['iin'];
            $this->login = $result[0]['login'];
            $this->role = $result[0]['role'];
            $this->roleId = $result[0]['roleId'];
        };
    }
    
    //Signing (athorize by AD)
    public function signIn($login,$password)
    {
        $user = Ad::getDataFromAD($login,$password);
        if ($user)
        {
            $this->saveUser($user);
            $_SESSION['userIin'] = $user['iin'];
            return true;
        }
        else 
        {
            return false;
        }
    }

    
    //Saves user data to DB
    private function saveUser(array $user)
    {
        $db = Db::getDb();
        $result = $db->selectQuery('SELECT * FROM user WHERE iin = :iin', ['iin'=>$user['iin']]);
        //If this user are not in DB, save him...
        if (!count($result))
        {
            $query = "INSERT INTO user (iin, login, firstName, lastName) VALUES (:iin, :login, :firstName, :lastName)";
            $db->IUDquery($query,$user);
        }
    }
    
    public function isAuth(){
        //Checks is the user authorized?
        if (!isset($_SESSION['user']))
        {
            if (isset($_POST['ajax']))
            {
                exit('Время сессии истекло, <a href="/">выполните вход</a>');
            }

            if(!(($controllerName == 'user') && ($actionName =='signin')))
            {
                $controllerName = 'user';
                $actionName = 'login';    
            }
        }
    }
    
    //Getters and setters
    public function getRole()
    {
        return $this->role;
    }
    
    public function getIin()
    {
        return $this->iin;
    }
    
    public function getFullName()
    {
        return $this->lastName.' '.$this->firstName;
    }
    
    public function canAccess($role)
    {
        $priveleges = $this->getPriveleges();
        if (in_array($role, $priveleges))
        {
            return true;
        }
        else 
        {
            header("Location:/user/noAccess");
        }

    }
    

    public function getAllUsers()
    {
        $db = Db::getDb();
        $result = $db->selectQuery('SELECT * FROM user ORDER BY lastName');
        return $result;
    }
    
    public function changeRole($iin,$role)
    {
        $db = Db::getDb();
        $result = $db->IUDQuery('UPDATE user SET roleId = :role WHERE iin=:iin', ['iin'=>$iin, 'role'=>$role]);
        return $result;
    }
    
    public static function deleteUser($iin)
    {
        $db = Db::getDb();
        $result = $db->IUDQuery('DELETE FROM user WHERE iin=:iin', ['iin'=>$iin]);
        return $result;
    }

    public function getUserRole() {
        $db = Db::getDb();
        $query = "SELECT role.name FROM role
        INNER JOIN user 
        ON user.roleId = role.id
        WHERE user.roleId = :id";
        $result = $db->selectQuery($query,['id'=>$this->roleId]);
        var_dump($result);
    }



    public function getPriveleges() {
        $db = Db::getDb();
        $query = "SELECT permission.access FROM permission
        INNER JOIN  rolePermission 
        ON rolePermission.idPermission = permission.id
        WHERE rolePermission.idRole = :id";
        $result = $db->selectQuery($query, ['id'=>$this->roleId]);
        $data = [];
        foreach ($result as $key=>$value) {
            $data[] = $value['access'];
        }
        return $data;
    }

    public function checkAdmin() {
        if (in_array('adminPanel', $this->getPriveleges())){
            return true;
        }
        else return false;
    }
}
