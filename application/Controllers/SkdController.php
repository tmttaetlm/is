<?php
namespace Controllers;

use Core\Controller;
use Models\SkdModel;
use Models\UserModel;
use Core\View;
/*
Skd system controller
*/

Class SkdController extends Controller
{
    public function __construct() 
    {
        parent::__construct();
        $this->checkLogged();
        $this->model = new SkdModel;
    }
        
    public function actionIndex()
    {
        $data['radioName']='tab';
        $data['user'] = $this->model->user->getFullName();
        $data['admin'] = $this->model->user->checkAdmin();
        $data['tabItems'] = $this->model->getTabItems();
        $data['tabData'] = $this->view->getDataByTabItems($data['tabItems'],'skd');
        $data['content'] = $this->view->generate('framework/tabs',$data);
        $data['systemTitle'] = 'Система контроля посещаемости';
        $data['content'] = $this->view->generate('framework/system',$data);
        echo $this->view->generate('templateView',$data);
    }
        
    public function actionGetdata()
    {

       if (isset($_POST['load'])) {
          $data['skd'] = SkdModel::allLogs();
          $data['content'] = $this->view->generate('framework/system',$data);
          echo $this->view->generate('templateView',$data);
       }

       if (isset($_POST['userLogs'])) {
            $data['userLogs'] = SkdModel::userLogs();
            $data['content'] = $this->view->generate('framework/system', $data);
            echo $this->view->generate('templateView', $data);
        }   
    }
        
    public function actionUserlogs()
    {
        $iin= $this->model->user->getIin();           
        $data = SkdModel::getUserLogs(['iin'=>$iin]);
        $title = 'Мои проходы за месяц';
        $columns = [
            'num'=>'№',
            'DateTime'=>'Дата и время',
            'Mode'=>'Вход/выход',
        ];
        echo $this->view->cTable($title,$columns,$data);
    }
        
    public function actionUserentranceexit()
    {
        $iin= $this->model->user->getIin();            
        $data = SkdModel::getUserEntranceExit(['iin'=>$iin]);
        $title = 'Отчет Вход - Выход по сотруднику за месяц';
        $columns = [
            'num'=>'№',
            'Surname'=>'Фамилия',
            'Firstname'=>'Имя',
            'Division'=>'Подразделение',
            'Date'=>'Дата',
            'EntranceTime'=>'Вход',
            'LeavingTime'=>'Выход',
        ];
        echo $this->view->cTable($title,$columns,$data);
    }
        
    public function actionGetstudentslogs()
    {
        if (isset($_POST['reportType'])) {
            if ($_POST['reportType'] == 'entranceExit') {
                $data = SkdModel::getStudentsLogs($_POST['grade'],$_POST['date']);
                $title = "{$_POST['grade']} класс - отчет за {$_POST['date']}";
                $columns = [
                            'num'=>'№',
                            'Surname'=>'Фамилия',
                            'Firstname'=>'Имя',
                            'Division'=>'Класс',
                            'EntranceTime'=>'Вход',
                            'LeavingTime'=>'Выход',
                            ];
                echo $this->view->cTable($title,$columns,$data);
            }
            
            if ($_POST['reportType'] == 'whoIsAtSchool') {
                $data = SkdModel::getStudentsAtSchool($_POST['grade']);
                $title = "{$_POST['grade']} класс - отчет за ".date('d.m.Y');
                $columns = [
                            'num'=>'№',
                            'Surname'=>'Фамилия',
                            'Firstname'=>'Имя',
                            'Division'=>'Класс',
                            'Status'=>'Статус',
                           ];
                echo $this->view->cTable($title,$columns,$data);
            }
            if ($_POST['reportType'] == 'studentByPeriod') {
                $data = SkdModel::getStudentLogsByPeriod($_POST['studentID'],$_POST['startDate'],$_POST['endDate']);
                $title = "Отчет по учащемуся за период с {$_POST['startDate']} по {$_POST['endDate']}";
                $columns = [
                 'num'=>'№',
                 'Surname'=>'Фамилия',
                 'Firstname'=>'Имя',
                 'Division'=>'Класс',
                 'Date'=>'Дата',
                 'EntranceTime'=>'Вход',
                 'LeavingTime'=>'Выход',
                ];
                echo $this->view->cTable($title,$columns,$data);
            }

            if ($_POST['reportType'] == 'contactList') {
                $data = SkdModel::getStudentContactList($_POST['grade']);
                $title = "Список номеров телефонов родителей учащихся {$_POST['grade']} класса";
                $columns = [
                 'num'=>'№',
                 'Surname'=>'Фамилия',
                 'Firstname'=>'Имя',
                 'Contact1'=>'Номер телефона',
                 'ChatID1'=>'Привязан',
                 'Subscribe1'=>'Подписан',
                 'Contact2'=>'Номер телефона',
                 'ChatID2'=>'Привязан',
                 'Subscribe2'=>'Подписан'
                ];

                foreach ($data as $key=>$value){
                    $dataForInput = [
                        'name' => 'contact1',
                        'id' => $data[$key]['ID'],
                        'value' => $data[$key]['Contact1'],
                    ];
                    $result = $this->view->generate('framework/input', $dataForInput);
                    $data[$key]['Contact1'] = $result;
                    
                    $dataForInput = [
                        'name' => 'contact2',
                        'id' => $data[$key]['ID'],
                        'value' => $data[$key]['Contact2'],
                    ];
                    $result = $this->view->generate('framework/input', $dataForInput);
                    $data[$key]['Contact2'] = $result;
                }

                echo $this->view->cTable($title,$columns,$data,'mtCaption');
            }
        }
    }
        
        public function actionGetstudentslist()
        {
            if (isset($_POST['grade']))
            {
                $data = SkdModel::getStudentsList($_POST['grade']);
                echo $this->view->createSelectOptions($data,'FIO', 'UserID');
            }
           
        }
        
        public function actionGetstafflist()
        {
            if (isset($_POST['divisionId']))
            {
                $data = SkdModel::getStaffList($_POST['divisionId']);
                echo $this->view->createSelectOptions($data,'FIO', 'UserID');
            }
           
        }
        
        public function actionGetdivisionlist()
        {
            $data = SkdModel::getDivisionList();
            $data = $this->view->createSelectOptions($data,'Name', 'Id');
            $data = '<option data-id="">Все подразделения</option>'."\n".$data;
            echo $data;
        }
        
        public function actionGetstafflogs()
        {
            if ($_POST['staffReportType'] == 'staffEntranceExit')
            {
                if (isset($_POST['divisionId']))
                {
                    $data = SkdModel::getDivisionStaffLogs($_POST['date'],$_POST['divisionId']);
                }
                else
                {
                    $data = SkdModel::getStaffLogs($_POST['date']);
                }
                $title = "Отчет по сотрудникам за {$_POST['date']}";
                $columns = [
                 'num'=>'№',
                 'Surname'=>'Фамилия',
                 'Firstname'=>'Имя',
                 'Division'=>'Подразделение',
                 'EntranceTime'=>'Вход',
                 'LeavingTime'=>'Выход',
                 'Comment'=>'Комментарий'
                ];
                echo $this->view->cTable($title,$columns,$data);
            }
            
            if ($_POST['staffReportType'] == 'staffWhoIsAtSchool')
            {

                if (isset($_POST['divisionId']))
                {
                    $data = SkdModel::getDivisionStaffAtSchool($_POST['divisionId']);
                }
                else
                {
                    $data = SkdModel::getStaffAtSchool();
                }
                
                $title = "Кто в школе";
                
                $columns = [
                    'num'=>'№',
                    'Surname'=>'Фамилия',
                    'Firstname'=>'Имя',
                    'Division'=>'Подразделение',
                    'Status'=>'Статус',
                    'Comment'=>'Комментарий'
                    ];
                
                echo $this->view->cTable($title,$columns,$data);
                
            }
            
            if ($_POST['staffReportType'] == 'personByPeriod')
            {
                if ($_POST['typePersonByPeriod'] == 'enEx')
                {
                    $data = SkdModel::getStudentLogsByPeriod($_POST['personID'],$_POST['startDate'],$_POST['endDate']);
                    $title = "Отчет по сотруднику за период с {$_POST['startDate']} по {$_POST['endDate']}";
                    $columns = [
                        'num'=>'№',
                        'Surname'=>'Фамилия',
                        'Firstname'=>'Имя',
                        'Division'=>'Подразделение',
                        'Date'=>'Дата',
                        'EntranceTime'=>'Вход',
                        'LeavingTime'=>'Выход',
                        ];
                    echo $this->view->cTable($title,$columns,$data);
                }
                
                if ($_POST['typePersonByPeriod'] == 'trace')
                {
                    $data = SkdModel::getPersonLogs($_POST['personID'],$_POST['startDate'],$_POST['endDate']);
                    $columns = [
                        'num'=>'№',
                        'DateTime'=>'Дата и время',
                        'Mode'=>'Вход/выход',
                        ]; 
                    echo $this->view->cTable('Проходы сотрудника за указанный период',$columns, $data);
                }
            }
        }

        public function actionGetgeneraldata(){
            $data = SkdModel::getGeneralData();
            header("Content-type:application/json");
            echo json_encode($data);
        }

        public function actionGetgeneralcontrolreport(){
            $dataFromDb = SkdModel::getGCReport($_POST['option1'],$_POST['option2']);
            $columns = [
                'num'=>'№',
                'Name'=>'Фамилия',
                'Firstname'=>'Имя',
                'Division'=>'Подразделение',
                'IsInside'=>'Статус',
                'LogTime'=>'Время',
                'Accommodation'=>'Проживание',
                'Address'=>'Комментарий'
                ]; 
            
            if ($_POST['option1'] == "gcStaff") {
                unset($columns['Accommodation']);
            };

            //If user can edit comments, let show him Select
            if ($this->model->user->hasPrivilege('skdGeneralControlCanEditComments') and $_POST['option1']=='gcStaff'){
                
                foreach ($dataFromDb as $key=>$value){
                    $data = [
                        'name' => 'comment',
                        'id' => $dataFromDb[$key]['ID'],
                        'selected' => $dataFromDb[$key]['Address'],
                        'items' => [
                            $dataFromDb[$key]['Address'],
                            '',
                            'Б/С',
                            'Б/Л',
                            'Отпуск',
                            'Отгул',
                            'Командировка',
                            'Декрет',
                            'Академ',
                            'Забыл карту'
                        ],
                    ];
                    $result = $this->view->generate('framework/select', $data);
                    $dataFromDb[$key]['Address'] = $result;
                }
            };

            echo $this->view->cTable('Все сотрудники',$columns, $dataFromDb,'users');
            
        }

        public function actionGetgcexport(){
            SkdModel::getDump($_POST["who"],$_POST["where"]);
        }
        
        public function actionWritecomment()
        {
            if (isset($_POST['id'])){
                SkdModel::writeComment($_POST['id'],$_POST['comment']);
                echo 'ok';
            }
        }
        
        //MTdev
        public function actionWritecontact()
        {
            if (isset($_POST['id'])){
                $result = SkdModel::writeContact($_POST['id'],$_POST['contact'],$_POST['name']);
                echo $result;
            }
        }

}