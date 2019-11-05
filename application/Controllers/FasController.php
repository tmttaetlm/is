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
        $this->model->index();
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
        echo $this->model->InventoryFinish();
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
