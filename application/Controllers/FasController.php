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
       FasModel::getInvExport();
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
            'barcode'=>'Штрих-код',
            'dateFix'=>'Дата закрепления',
            'sn'=>'Серийный номер',
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

}
