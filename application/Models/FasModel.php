<?php
namespace Models;

use Core\Model;
use Components\Db;

/**
 * Model for Fixed Assets System
 */
class FasModel extends Model 
{
    public function __construct($view) {
        parent::__construct();
        $this->view = $view;
    }
    
    private function getFixedAssets($iin) {
        $db = Db::getDb();
	return $db->selectQuery("SELECT * FROM fixedAsset WHERE iin = :iin ORDER BY description",['iin'=>$iin]);
    }
    
    private function getModificationDate() 
    {
        $db = Db::getDb();
        $result = $db->selectQuery("SELECT dateTimeValue FROM info WHERE `key` = '1cFileLastUpdate';");
        return strtotime($result[0]['dateTimeValue']);
    }


    public function index() {
        
        $data['tabItems']['monitoring']='Мои ОС';
        $userPriveleges = $this->user->getPriveleges();
        if (in_array("fasCanSeach", $userPriveleges)) {
            $data['tabItems']['seach'] = 'Поиск в БД';
        }

        //set the title of the table
        $title = 'ОC закрепленные за сотрудником: <u>'.$this->user->getFullName().'</u>'; 
        //get user fixed assets from the Data Base 
        $result = $this->getFixedAssets($this->user->getIin());
        $result = $this->addRowNumbers($result);
        $columns = [
            'num'=>'№',
            'invNumber'=>'Инвентарный номер',
            'barcode'=>'Штрих-код',
            'description'=>'Описание',
            'location'=>'Местонахождение',
            'dateFix'=>'Дата закрепления',
            'sn'=>'Серийный номер',
            
            ];
        $updateInfo = '<p class="fasUpdateInfo">Согласно сведениям из ИС 1С:Бухгалтерия. Последняя синхронизация: '.date('d.m.Y H:i',$this->getModificationDate())."</p>\n<br>";
        $data['tabData']['monitoring'] = $updateInfo.$this->view->cTable($title,$columns, $result,'fasResultTable');
        $data['lastUpdate'] = date('d.m.Y H:i',$this->getModificationDate());
        $data['tabData']['seach'] = $this->view->generate('fas/seach',$data);
        $data['content'] = $this->view->generate('framework/tabs',$data);
        $data['systemTitle'] = 'Учет основных средств';
        $data['content'] = $this->view->generate('framework/system',$data);
        $data['user'] = $this->user->getFullName();
        $data['admin'] = $this->user->checkAdmin();
        echo $this->view->generate('templateView',$data);
        
    }
    
    public function seachByInvNumber($invNumber) {
        $db = Db::getDb();
	return $db->selectQuery("SELECT * FROM fixedAsset WHERE invNumber = :invNumber",['invNumber'=>$invNumber]);
    }
    
    public function seachByBarcode($barcode) {
        $db = Db::getDb();
	return $db->selectQuery("SELECT * FROM fixedAsset WHERE barcode = :barcode",['barcode'=>$barcode]);
    }
    
    public function seachByPerson($person) {
        $db = Db::getDb();
	return $db->selectQuery("SELECT * FROM fixedAsset WHERE person = :person ORDER BY description",['person'=>$person]);  
    }
    
    public function seachByLocation($location) {
        $db = Db::getDb();
	return $db->selectQuery("SELECT * FROM fixedAsset WHERE location = :location ORDER BY person,description",['location'=>$location]);
    }

    public function seachByFixedAsset($fixedAsset) {
        $db = Db::getDb();
	return $db->selectQuery("SELECT * FROM fixedAsset WHERE description = :fixedAsset",['fixedAsset'=>$fixedAsset]);
    }     
    
    public function getPeople() {
        $db = Db::getDb();
        $data = $db->selectQuery("SELECT DISTINCT person FROM fixedAsset");
        foreach($data as $row) {
            $data2[] = $row['person']; 
        }
        $data2 = json_encode($data2, JSON_UNESCAPED_UNICODE);
        echo $data2;
    }
    
    public function getLocationList()
    {
        $db = Db::getDb();
        $data = $db->selectQuery("SELECT DISTINCT location FROM fixedAsset");
        foreach($data as $row) {
            $data2[] = $row['location']; 
        }
        $data2 = json_encode($data2, JSON_UNESCAPED_UNICODE);
        echo $data2;
    }
    
    public function getFixedAssetList()
    {
        $db = Db::getDb();
        $data = $db->selectQuery("SELECT DISTINCT description FROM fixedAsset");
        foreach($data as $row) {
            $data2[] = $row['description']; 
        }
        $data2 = json_encode($data2, JSON_UNESCAPED_UNICODE);
        echo $data2;
    }  
    
    
}
