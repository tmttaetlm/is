<?php
namespace Controllers;

use Core\Controller;
use Core\View;
use Models\SkdModel;
use Models\FasModel;

/*
 * Fixed assets system Controller
 */

class FasController extends Controller
{
    
    public function __construct()
    {
        parent::__construct();
        $this->checkLogged();
        $this->model = new FasModel($this->view);
    }
    
    public function actionIndex()
    {
        //$this->model->index();

        $updateInfo = '<p class="fasUpdateInfo">Согласно сведениям из ИС 1С:Бухгалтерия. Последняя синхронизация: '.date('d.m.Y H:i',$this->model->getModificationDate())."</p>\n<br>";
        $data['lastUpdate'] = date('d.m.Y H:i',$this->model->getModificationDate());
        $data['inventoryStartedAt'] = date('d.m.Y H:i',$this->model->getStartedDate());
        $data['tabItems']['monitoring']='Мои ОС';
        $userPriveleges = $this->model->user->getPriveleges();
        if (in_array("fasCanSeach", $userPriveleges)) {
            $data['tabItems']['seach'] = 'Поиск в БД';
        }
        //Inventory
        if ($this->model->getInventoryStatus()) {
            $data['tabItems']['inventory'] = 'Инвентаризация';
            $data['inventoryData'] = $this->model->getInventoryData();
            $data['inventoryFinished'] = $this->model->checkInventoryFinished("");
            $data['tabData']['inventory'] =  $this->view->generate('fas/inventory',$data);
        }

        //InventoryControl
        if (in_array("fasInvControl", $userPriveleges)) {
            $data['tabItems']['inventoryControl'] = 'Инв. контроль';
            $data['canInvStart'] = $this->model->user->hasPrivilege('fasInvStart');
            $data['tabData']['inventoryControl'] =  $this->view->generate('fas/inventoryControl',$data);
        }


        //set the title of the table
        $title = 'ОC закрепленные за сотрудником: <u>'.$this->model->user->getFullName().'</u>'; 
        //get user fixed assets from the Data Base 
        $result = $this->model->getFixedAssets($this->model->user->getIin());
        $result = $this->model->addRowNumbers($result);
        $columns = [
            'num'=>'№',
            'invNumber'=>'Инвентарный номер',
            'description'=>'Описание',
            'location'=>'Местонахождение',
            'dateFix'=>'Дата закрепления',
            'sn'=>'Серийный номер',
            'comment'=>'Комментарий',
            ];
        
        $data['tabData']['monitoring'] = $updateInfo.$this->view->cTable($title,$columns, $result,'fasResultTable');
        
        $data['tabData']['seach'] = $this->view->generate('fas/seach',$data);
        $data['content'] = $this->view->generate('framework/tabs',$data);
        $data['systemTitle'] = 'Учет основных средств';
        $data['content'] = $this->view->generate('framework/system',$data);
        $data['user'] = $this->model->user->getFullName();
        $data['admin'] = $this->model->user->checkAdmin();
        
        echo $this->view->generate('templateView',$data);
    }
    
    public function actionSeach()
    {
        $columns = [
                'num' =>'№',
                'invNumber' => 'Инвентарный номер',
                'description' => 'Наименование ОС',
                'person'=>'Ответственный',
                'location'=>'Местонахождение',
                'barcode'=>'Штрих-код',
                'dateFix'=>'Дата закрепления',
                'sn'=>'Серийный номер',
                'comment'=>'Комментарий',
            ];
    
        if (isset($_POST['invNumber'])){
            $data = $this->model->seachByInvNumber($_POST['invNumber']);
            $data  = $this->model->addRowNumbers($data );
            echo $this->view->cTable('Результаты поиска:',$columns,$data);
        }
        
        if (isset($_POST['barcode'])){
            $data = $this->model->seachByBarcode($_POST['barcode']);
            $data  = $this->model->addRowNumbers($data );
            echo $this->view->cTable('Результаты поиска:',$columns,$data);
        }        
        
        if (isset($_POST['person'])){
            $data = $this->model->seachByPerson($_POST['person']);
            $data  = $this->model->addRowNumbers($data );
           
            echo $this->view->cTable('Результаты поиска:',$columns,$data);
        }
        
        if (isset($_POST['location'])){
            $data = $this->model->seachByLocation($_POST['location']);
            $data  = $this->model->addRowNumbers($data );
            echo $this->view->cTable('Результаты поиска:',$columns,$data);
        }
        
        if (isset($_POST['fixedAsset'])){
            $data = $this->model->seachByFixedAsset($_POST['fixedAsset']);
            $data  = $this->model->addRowNumbers($data );
            echo $this->view->cTable('Результаты поиска:',$columns,$data);
        }        
            
    }
    
    public function actionGetpeoplelist()
    {
        return $this->model->getPeople();
    }

    public function actionGetlocationlist()
    {
        return $this->model->getLocationList();
    }

    public function actionGetfixedassetlist()
    {
        return $this->model->getFixedAssetList();
    }    
    
    public function actionGetInventoryData()
    {
        echo $this->model->getInventoryData();
    }

    public function actionInventoryFinish()
    {
        echo $this->model->InventoryFinish($_POST['person']);
    }
    public function actionCancelInventoryFinish()
    {
        echo $this->model->CancelInventoryFinish($_POST['person']);
    }
    public function actionCheckInventoryFinish()
    {
        echo $this->model->checkInventoryFinished($_POST['person']);
    }

    public function actionGetFasRooms()
    {
        echo $this->model->getFasRooms();
    }
    public function actionGetFasComments()
    {
        echo $this->model->getFasComments();
    }
    public function actionInventoryChangeLocation()
    {
        $this->model->inventoryChangeLocation($_POST['id'],$_POST['locationCode']);
    }
    public function actionInventoryChangeComment()
    {
        $this->model->inventoryChangeComment($_POST['id'],$_POST['commentId']);
    }

    //Inventory control

    public function actionGetinvexport()
    {
        if (isset($_POST['invReportType'])){
            if ($_POST['invReportType']=='people'){
                FasModel::getInvPeopleExport($_POST['invReportType']);
            }elseif($_POST['invReportType']=='movement'){
                FasModel::getInvMovement();
            }
            elseif(($_POST['invReportType']=='allAssets') || ($_POST['invReportType']=='unscannedAssets') || ($_POST['invReportType']=='unfixedAssets')){
                FasModel::getInvExport($_POST['invReportType']);
            }  
        }  
        
    }

    public function actionGetInvpeoplelist()
    {
        return $this->model->getInvPeople();
    }
    
    public function actionInvSeach(){
        $columns = [
            'num' =>'№',
            'invNumber' => 'Инвентарный номер',
            'description' => 'Наименование ОС',
            'person'=>'Ответственный',
            'location'=>'Местонахождение',
            'newLocation'=>'Новое местонахождение',
            'whoScanned'=>'Инвентаризатор',
            'barcode'=>'Штрих-код',
            //'dateFix'=>'Дата закрепления',
            'barcodeScanned'=>'Штрих-код отсканирован',
        ];
        if (isset($_POST['person'])){
            $data = $this->model->invSeachByPerson($_POST['person']);
            $data  = $this->model->addRowNumbers($data );
            echo $this->view->cTable('Результаты поиска:',$columns,$data,'users');
        }
        if (isset($_POST['invNumber'])){
            $data = $this->model->invSeachByInvNumber($_POST['invNumber']);
            $data  = $this->model->addRowNumbers($data );
            echo $this->view->cTable('Результаты поиска:',$columns,$data,'users');
        }
        if (isset($_POST['location'])){
            $data = $this->model->invSeachByLocation($_POST['location']);
            $data  = $this->model->addRowNumbers($data );
            echo $this->view->cTable('Результаты поиска:',$columns,$data);
        }
        
        if (isset($_POST['fixedAsset'])){
            $data = $this->model->invSeachByFixedAsset($_POST['fixedAsset']);
            $data  = $this->model->addRowNumbers($data );
            echo $this->view->cTable('Результаты поиска:',$columns,$data);
        }  
    }
    
    public function actionInvChangeOwner(){
        if (isset($_POST['invNumber'])){
            echo $this->model->invChangeOwner($_POST['invNumber'],$_POST['newOwner']);
        }
    }

    public function actionGetInvlocationlist()
    {
        return $this->model->getInvLocationList();
    }

    public function actionGetInvfixedassetlist()
    {
        return $this->model->getInvFixedAssetList();
    }   
    
    public function actionInvChangeScannedStatus()
    {
        if (isset($_POST['id'])){
            $this->model->InvChangeScannedStatus($_POST['id'],$_POST['status'],$_SESSION['userIin']);
        }
    }
    
    public function actionInvTransmitAssets()
    {
        if (isset($_POST['invTransmittingPerson'])){
            echo $this->model->transmitAssets($_POST['invTransmittingPerson'],$_POST['invReceivingPerson']);
        }
    }
    
    public function actionStartInventory(){
        echo $this->model->startInventory();
    }   
    
    public function actionStopInventory(){
        echo $this->model->stopInventory();
    }   
}
