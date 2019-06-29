<?php

namespace Models;

use Core\Model;
use Components\Db;
use Components\DbSkd;


/**
 * 
 */

class AdminModel extends Model{
    
    public function __construct(){
        parent::__construct();
    }
    
    
    public function getUserList()
    {
        return $this->user->getAllUsers();
    }

    public function addRole($role)
    {
        $db = Db::getDb();
        $result = $db->selectQuery("SELECT EXISTS(SELECT * FROM role WHERE name=:role limit 1) as myCheck",['role'=>$role]);
        if (!$result[0]['myCheck']) {
            $db->IUDquery("INSERT  INTO role (name) VALUES (:role)",['role'=>$role]);
            return "Новая роль создана";
        }
        else {  
            return "Такая роль уже есть!";
        }
    }

    public function getRoles() 
    {
        $result = '';
        $db = Db::getDb();
        $data = $db->selectQuery("SELECT * FROM role ORDER BY name");
        foreach ($data as $role) {
            $result = $result."<option data-id={$role['id']}>{$role['name']}</option>\n";
        }
        echo $result;
    }

    public function getRoles2() 
    {
        $result = '';
        $db = Db::getDb();
        return $db->selectQuery("SELECT * FROM role ORDER BY name");
    }
    public function deleteRole($id) {
        $db = Db::getDb();
        $db->IUDquery("DELETE FROM rolePermission WHERE idRole = :id", ['id'=>$id]);
        $db->IUDquery("DELETE FROM role WHERE id = :id ", ['id'=>$id]);
        return 'Выбранная роль удалена';
    } 

    public function getAllPermissions() {
        $db = Db::getDb();
        $data = $db->selectQuery("SELECT * FROM permission ORDER BY description");
        return $data;
    }
    
    public function getPermissionsByRole($roleId) {
        $db = Db::getDb();
        $query = "
        SELECT permission.access
        FROM permission
        INNER JOIN rolePermission ON rolePermission.idPermission = permission.id
        WHERE rolePermission.idRole = :roleId";
        $data = $db->selectQuery($query,['roleId'=>$roleId]);
        $permissons = [];
        foreach ($data as $permisson){
            $permissons[] = $permisson['access'];
        }
        header('Content-Type: application/json');
        echo json_encode($permissons);
    }
    
    public function setPrivToRole($mode,$roleId,$privId) {
        $db = Db::getDb();
        if ($mode == 'enable') {
            $db->IUDquery("INSERT IGNORE INTO rolePermission (idRole,idPermission) VALUES (:idRole,:idPermission)", ['idRole'=>$roleId,'idPermission'=>$privId]);
        }

        if ($mode == 'disable') {
            $db->IUDquery("DELETE FROM rolePermission WHERE idRole = :idRole AND idPermission = :idPermission", ['idRole'=>$roleId,'idPermission'=>$privId]);
        }
    }

    public static function deleteOldEntries($date){
        $date = str_replace('-', '', $date); 
        $date.= ' 00:00:00';
        $params['date'] = $date;
        $tsql = "DELETE FROM dbo.GateLog WHERE DateTime <= :date";
        $db = DbSkd::getInstance();
        $result = $data = $db->updateQuery($tsql,$params);
        return $result;
    }
    
}
