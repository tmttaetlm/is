<?php
namespace Controllers;

use Core\Controller;
use Core\View;
use Models\VisitModel;

/*
 * Fixed assets system Controller
 */

class VisitController extends Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->checkLogged();
        $this->model = new VisitModel($this->view);
    }

    public function actionIndex()
    {
        $res = $this->model->checkUser($this->model->user->getIin());
        $userPriveleges = $this->model->user->getPriveleges();
        if (!$res /*|| $this->model->user->getIin() == '920812350558'*/) {
            $data['error'] = "У вас нет доступа к этому разделу. Не установлена соответствующая преподавателю роль или Вы не являетесь преподавателем. Обратитесь к администратору системы.";
            $data['content'] = $this->view->generate('visit/noAccess',$data);
        } else {
            $data['radioName']='tabR';
            $data['tabItems']['standard']='Оценивание урока';
            $data['tabItems']['attestation']='Оценивание урока в рамках аттестации';

            /*$data['tabData']['standard'] = $this->view->generate('visit/languagePanel',$data);
            $data['tabData']['standard'] = $data['tabData']['standard'].$this->getStandardTab($userPriveleges);*/
            $data['tabData']['standard'] = $this->getStandardTab($userPriveleges);
            /*$data['tabData']['attestation'] = $this->view->generate('visit/languagePanel',$data);
            $data['tabData']['attestation'] = $data['tabData']['attestation'].$this->getAttestationTab($userPriveleges);*/
            $data['tabData']['attestation'] = $this->getAttestationTab($userPriveleges);

            $data['content'] = $this->view->generate('visit/languagePanel',$data);
            $data['content'] = $data['content'].$this->view->generate('framework/tabs',$data);
            $data['content'] = $data['content'].'<form id="dumpVisitResults" method="post"  action="/visit/getResultsDump">
                                                    <input type="hidden" name="rowId">
                                                    <input type="hidden" name="mode">
                                                    <input type="hidden" name="focus">
                                                 </form>';
        }
        $data['systemTitle'] = 'Система оценивания уроков';

        $data['content'] = $this->view->generate('framework/system',$data);
        $data['user'] = $this->model->user->getFullName();
        $data['admin'] = $this->model->user->checkAdmin();
        
        echo $this->view->generate('templateView',$data);
    }

    public function getStandardTab($userPriveleges)
    {
        $data['radioName']='tab';
        $data['tabItems']['myVisits']='План посещений';
        $data['tabItems']['myEvaluations']='Мои оценки';
        if (in_array("visitReportAccess", $userPriveleges)) {
            $data['tabItems']['Reports']='Мониторинг посещений';
        }
        if (in_array("visitManagementAccess", $userPriveleges)) {
            $data['tabItems']['Managements']='Управление';
        }

        $data['tabData']['myVisits'] = $this->view->generate('visit/standard/myVisits',$data);
        $data['tabData']['myVisits'] = $data['tabData']['myVisits'].$this->getVisitTable();
        $data['tabData']['myVisits'] = $data['tabData']['myVisits'];
        
        $data['tabData']['myEvaluations'] = $this->getEvaluateTable();
        $data['tabData']['Reports'] = $this->view->generate('visit/standard/reports',$data);
        $data['tabData']['Managements'] = $this->view->generate('visit/standard/managements',$data);

        $data['content'] = $this->view->generate('framework/tabs',$data);

        return $data['content'];
    }

    public function getAttestationTab($userPriveleges)
    {
        $data['radioName']='tab2';
        $data['tabItems']['myAVisits']='План посещений';
        $data['tabItems']['myAEvaluations']='Мои оценки';
        /*if (in_array("visitReportAccess", $userPriveleges)) {
            $data['tabItems']['AReports']='Мониторинг посещений';
        }*/
        if (in_array("visitManagementAccess", $userPriveleges)) {
            $data['tabItems']['AManagements']='Управление';
        }

        $data['tabData']['myAVisits'] = $this->getAttestationVisitTable();
        $data['tabData']['myAVisits'] = $data['tabData']['myAVisits'];

        $data['tabData']['myAEvaluations'] = $this->getAttestationEvaluatesTable();
        //$data['tabData']['AReports'] = $this->view->generate('visit/attestation/reports',$data);
        $data['tabData']['AManagements'] = $this->view->generate('visit/attestation/managements',$data);

        $data['content'] = $this->view->generate('framework/tabs',$data);

        return $data['content'];
    }

    public function getVisitTable()
    {
        $title = 'График моих посещений'; 
        $result = $this->model->getVisitsList();
        $columns = [
            'num' =>'№',
            'whoWasVisited' => 'Учитель',
            'visitDate' => 'Дата',
            'lessonNum'=>'Урок',
            'status'=> 'Статус',
            'result' => 'Результат'
        ];
        return $this->view->cTable($title,$columns,$result,'visitResults');
    }

    public function getEvaluateTable()
    {
        $title = 'Список моих оценок'; 
        $result = $this->model->getEvaluatesList();
        $columns = [
            'num' =>'№',
            'whoVisited' => 'Наблюдатель',
            'visitDate' => 'Дата',
            'lessonNum'=>'Урок',
            'status'=> 'Статус',
            'result' => 'Результат'
        ];
        return $this->view->cTable($title,$columns,$result,'visitResults');
    }

    public function actionGetStaffList()
    {
        return $this->model->getStaffList();
    }

    public function actionAddVisit()
    {
        if ($_POST['whoWasVisited'] == $this->model->user->getFullName()) { echo 'me'; }
        else {
            $res = $this->model->checkUser($this->model->getTeacherIin($_POST['whoWasVisited']));
            if (!$res) { echo ''; }
            else {
                $this->model->addVisit($_POST);
                echo $this->getVisitTable();
            }
        }
    }

    public function actionGetCriteriaDiscription()
    {
        $data = $this->model->getCriteriaDiscription($_POST['criteria'],$_POST['mark']);
        if (!empty($data)) {
            echo  $this->model->getTexts('DESCRIPTIONS',$data[0]['disc']);
        } else {
            echo "";
        }
    }

    public function actionSetVisitResults()
    {
        $this->model->setVisitResults($_POST);
        echo $this->getVisitTable();
    }

    public function actionGetVisitResults()
    {
        $default = $this->model->getDefaultCriteriasList();
        $criterias = $this->model->getCriteriaDiscription();
        $data = $this->model->getVisitResults($_POST);
        $subjects = $this->model->getSubjects();
        $grades = $this->model->getGrades();
        $params = [
            "def" => $default,
            "data" => $data,
            "subj" => $subjects,
            "grade" => $grades,
            "crit" => $criterias
        ];
        
        if ($_POST['className'] == 'myVisits') {
            echo $this->view->generate('visit/standard/patternForFill',$params);
        } else { 
            echo $this->view->generate('visit/standard/patternForShow',$params); 
        }
    }

    public function actionCheckEvaluates()
    {
        echo $this->model->checkEvaluates($_POST);
    }

    public function actionCheckConfirmations()
    {
        echo $this->model->checkConfirmations($_POST);
    }

    public function actionDeleteVisit()
    {
        $this->model->deleteVisit($_POST);
        if ($_POST['mode'] == 'standart') {
            echo $this->getVisitTable();
        } else {
            echo $this->getAttestationVisitTable();
        }
    }

    public function actionGetResultsDump()
    {
        $this->model->getResultsDump($_POST);
    }

    public function actionSetConfirmation()
    {
        $this->model->setConfirmation($_POST['rowId'],$_POST['side'],$_POST['mode']);
        if ($_POST['mode'] == 'standart') {
            if ($_POST['side'] == 'watcher') {
                echo $this->getVisitTable();
            } else {
                echo $this->getEvaluateTable();
            }
        } else {
            if ($_POST['side'] == 'watcher') {
                echo $this->getAttestationVisitTable();
            } else {
                echo $this->getAttestationEvaluateTable();
            }
        }
    }

    public function actionGetNumberOfVisits()
    {
        $data = $this->model->getNumberOfVisits($_POST);
        //$title = "Количество посещении за период с ".date('d.m.Y', strtotime($_POST['start']))." по ".date('d.m.Y', strtotime($_POST['end']));
        $title = "";
        $columns = [
            'status'=>'Статус',
            'number'=>'Количество'
        ];
        echo $this->view->cTable($title,$columns,$data,'commonNumberOfVisits');
    }

    public function actionGetPersonalVisits()
    {
        if ($_POST['details'] == '0') {
            $data = $this->model->getPersonalVisits($_POST);
            if ($_POST['visitType'] == 'WhoVisited') {
                $title = "Количество посещении преподавателя {$_POST['teacher']} как наблюдателя";
            } else {
                $title = "Количество посещении уроков преподавателя {$_POST['teacher']}";
            }
            $columns = [
                'num'=>'№',
                'who'=>$_POST['visitType'] == 'WhoVisited' ? 'Посещаемый' : 'Наблюдатель',
                'v_cnt'=>'Всего',
                'p_cnt'=>'Запланировано',
                'c_cnt'=>'Подтверждено',
                'o_cnt'=>'В процессе'
            ];
            echo $this->view->cTable($title,$columns,$data,'numberOfVisits');
        } else {
            if ($_POST['detailsDate'] == '0') {
                $data = $this->model->getAllVisitsInPeriod($_POST);
                if ($_POST['visitType'] == 'WhoVisited') {
                    $title = "Посещения преподавателя {$_POST['teacher']} как наблюдателя";
                } else {
                    $title = "Посещения уроков преподавателя {$_POST['teacher']}";
                }
                $columns = [
                    'num'=>'№',
                    'visitDate'=>'Дата посещения',
                    'person'=>$_POST['visitType'] == 'WhoVisited' ? 'Посещаемый' : 'Наблюдатель',
                    'lessonNum'=>'Урок',
                    'status'=>'Статус'
                ];
                echo $this->view->cTable($title,$columns,$data,'numberOfAllVisits');
            } else {
                $data = $this->model->getAllVisitsInDetails($_POST);
                if ($_POST['visitType'] == 'WhoVisited') {
                    $title = "Посещения преподавателя {$_POST['teacher']} как наблюдателя за {$_POST['date']}";
                } else {
                    $title = "Посещения уроков преподавателя {$_POST['teacher']} за {$_POST['date']}";
                }
                $columns = [
                    'num'=>'№',
                    'visitDate'=>'Дата посещения',
                    'person'=>$_POST['visitType'] == 'WhoVisited' ? 'Посещаемый' : 'Наблюдатель',
                    'lessonNum'=>'Урок',
                    'status'=>'Статус'
                ];
                echo $this->view->cTable($title,$columns,$data,'numberOfAllVisits');
            }
        }
        
    }

    public function actionGetAllVisits()
    {
        $data = $this->model->getAllVisits($_POST);
        $title = "Количество посещении за период с {$_POST['visitPeriodStart']} по {$_POST['visitPeriodEnd']}";
        $columns = [
            'num'=>'№',
            'visitDate'=>'Дата посещения',
            'whoVisited'=>'Наблюдатель',
            'whoWasVisited'=>'Посещаемый',
            'lessonNum'=>'Урок',
            'status'=>'Статус'
        ];
        echo $this->view->cTable($title,$columns,$data,'numberOfAllVisits');
    }

    public function actionGetReportsDump()
    {
        if (!empty($_POST['whoWasVisited'])) {
            $this->model->getDumpForReport1($_POST);
        } else {
            $this->model->getDumpForReport2();
        }
    } 

    public function actionManagePersonPurpose()
    {
        echo $this->model->managePersonPurpose($_POST);
    }

    public function actionSavePersonPurpose()
    {
        $this->model->savePersonPurpose($_POST);
    }

    public function getAttestationVisitTable()
    {
        $title = 'График моих посещений'; 
        $result = $this->model->getAttestationVisitsList();
        $columns = [
            'num' =>'№',
            'whoWasVisited' => 'Посещаемый',
            'visitDate' => 'Период',
            'focus' => 'Фокус оценивания',
            'status'=> 'Статус',
            'result' => 'Результат'
        ];
        return $this->view->cTable($title,$columns,$result,'visitAResults');
    }

    public function getAttestationEvaluatesTable()
    {
        $title = 'Список моих оценок'; 
        $result = $this->model->getAttestationEvaluatesList();
        $columns = [
            'num' =>'№',
            'whoVisited' => 'Наблюдатель',
            'visitDate' => 'Период',
            'focus' => 'Фокус оценивания',
            'status'=> 'Статус',
            'result' => 'Результат'
        ];
        return $this->view->cTable($title,$columns,$result,'visitAResults');
    }

    public function actionSaveSynod()
    {
        $this->model->saveSynod($_POST);
    }

    public function actionGetSynod()
    { 
        return $this->model->getSynod($_POST);;
    }

    public function actionGetAttestationVisitResults()
    {
        $data = $this->model->getAttestationVisitResults($_POST);
        $subjects = $this->model->getSubjects();
        $grades = $this->model->getGrades();
        $default = $this->model->getAttestationCriteriasList($data[0]['focus']);
        $params = [
            "def" => $default,
            "data" => $data,
            "subj" => $subjects,
            "grade" => $grades
        ];
        
        if ($_POST['className'] == 'myAVisits') {
            echo $this->view->generate('visit/attestation/patternForFill',$params);
        } else { 
            echo $this->view->generate('visit/attestation/patternForShow',$params); 
        }
    }

    public function actionSetAttestationVisitResults()
    {
        print_r($this->model->setAttestationVisitResults($_POST));
        //echo $this->getAttestationVisitTable();
    }

    public function actionGetAttestationResultsDump()
    {
        $this->model->getAttestationResultsDump($_POST);
    }

    public function actionGetVisitCount()
    {
        echo $this->model->getVisitCount($_POST);
    }
}