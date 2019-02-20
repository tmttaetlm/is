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
    
    
    
}
