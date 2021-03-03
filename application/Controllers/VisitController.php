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
        if (!$res && !(in_array("visitPDOAccess", $userPriveleges)) /*|| $this->model->user->getIin() == '920812350558'*/) {
            $data['error'] = "У вас нет доступа к этому разделу. Не установлена соответствующая преподавателю роль или Вы не являетесь преподавателем. Обратитесь к администратору системы.";
            $data['content'] = $this->view->generate('visit/noAccess',$data);
        } else {
            $data['radioName']='tabR';
            $data['tabItems']['standard']='Наблюдение урока';
            $data['tabItems']['attestation']='Наблюдение урока в рамках аттестации';
            $data['tabItems']['management']='Управление';

            $data['tabData']['standard'] = $this->getStandardTab($userPriveleges);
            $data['tabData']['attestation'] = $this->getAttestationTab($userPriveleges);
            if (in_array("visitManagementAccess", $userPriveleges)) {
                $data['tabData']['management'] = $this->view->generate('visit/management',$data);
            }

            $data['content'] = $this->view->generate('visit/languagePanel',$data);
            $data['content'] = $data['content'].$this->view->generate('framework/tabs',$data);
            $data['content'] = $data['content'].'<form id="dumpVisitResults" method="post"  action="/visit/getResultsDump">
                                                    <input type="hidden" name="rowId">
                                                    <input type="hidden" name="mode">
                                                    <input type="hidden" name="focus">
                                                 </form>';
        }
        $data['systemTitle'] = 'Система наблюдения уроков';

        $data['content'] = $this->view->generate('framework/system',$data);
        $data['user'] = $this->model->user->getFullName();
        $data['admin'] = $this->model->user->checkAdmin();
        
        echo $this->view->generate('templateView',$data);
    }

    public function getStandardTab($userPriveleges)
    {
        $data['radioName']='tab';
        $data['tabItems']['myVisits']='План посещений';
        $data['tabItems']['myEvaluations']='Результаты наблюдения';
        if (in_array("visitReportAccess", $userPriveleges)) {
            $data['tabItems']['Reports']='Мониторинг посещений';
        }
        /*if (in_array("visitManagementAccess", $userPriveleges)) {
            $data['tabItems']['Managements']='Управление';
        }*/

        $data['tabData']['myVisits'] = $this->view->generate('visit/standard/myVisits',$data);
        $data['tabData']['myVisits'] = $data['tabData']['myVisits'].$this->getVisitTable();
        $data['tabData']['myEvaluations'] = $this->getEvaluateTable();
        $data['tabData']['Reports'] = $this->view->generate('visit/standard/reports',$data);
        //$data['tabData']['Managements'] = $this->view->generate('visit/standard/managements',$data);

        $data['content'] = $this->view->generate('framework/tabs',$data);

        return $data['content'];
    }

    public function getAttestationTab($userPriveleges)
    {
        $data['radioName']='tab2';
        $data['tabItems']['myAVisits']='План посещений';
        $data['tabItems']['myAEvaluations']='Результаты наблюдения';
        if (in_array("visitLSOAccess", $userPriveleges)) {
            $data['tabItems']['LSO']='ЛШО';
        }
        if (in_array("visitReportAccess", $userPriveleges)) {
            $data['tabItems']['AReports']='Мониторинг посещений';
        }
        if (in_array("visitManagementAccess", $userPriveleges)) {
            $data['tabItems']['AManagements']='Управление наблюдателями';
        }

        $data['tabData']['myAVisits'] = $this->getAttestationVisitTable();
        $data['tabData']['myAEvaluations'] = $this->getAttestationEvaluatesTable();
        $data['tabData']['LSO'] = $this->view->generate('visit/attestation/lso',$data);
        $data['tabData']['LSO'] = $data['tabData']['LSO'].$this->getLSOTable([]);
        $data['tabData']['AReports'] = $this->view->generate('visit/attestation/reports',$data);
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

    public function getLSOTable($param)
    {
        $title = '';
        $result = $this->model->getLSOTable($param);
        $columns = [
            'num'=>'№',
            'whoWasVisited'=>'Учитель',
            'planning'=>'"Планирование"',
            'teaching'=>'"Преподавание"',
            'evaluating'=>'"Оценивание учебных достижений"',
            'complex'=>'"Комплексный анализ урока"',
            'half_year'=>'Период',
            'result'=>'Результат'
        ];
        return $this->view->cTable($title,$columns,$result,'lsoTable');
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
            $userPriveleges = $this->model->user->getPriveleges();
            if (!$res && !(in_array("visitPDOAccess", $userPriveleges))) { echo ''; }
            else {
                $this->model->addVisit($_POST);
                echo $this->getVisitTable();
            }
        }
    }

    public function actionSendEmailNotification()
    {
        $this->model->sendEmailNotification($_POST);
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
                'who'=>$_POST['visitType'] == 'WhoVisited' ? 'Учитель' : 'Наблюдатель',
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
                    'person'=>$_POST['visitType'] == 'WhoVisited' ? 'Учитель' : 'Наблюдатель',
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
                    'person'=>$_POST['visitType'] == 'WhoVisited' ? 'Учитель' : 'Наблюдатель',
                    'lessonNum'=>'Урок',
                    'status'=>'Статус',
                    'load' =>'Результат'
                ];
                for ($i=0; $i<count($data); $i++) {
                    $data[$i]['load'] = '<button name="saveToPDFforRSh" class="visitBut">Выгрузить</button>';
                }
                echo $this->view->cTable($title,$columns,$data,'numberOfAllVisits');
            }
        }
        
    }

    public function actionGetAllVisits()
    {
        $data = $this->model->getAllVisits($_POST);
        $title = "Количество посещении за период с ".date('d.m.Y',strtotime($_POST['visitPeriodStart']))." по ".date('d.m.Y',strtotime($_POST['visitPeriodEnd']));
        $columns = [
            'num'=>'№',
            'visitDate'=>'Дата посещения',
            'whoVisited'=>'Наблюдатель',
            'whoWasVisited'=>'Учитель',
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

    public function actionDeletePersonPurpose()
    {
        $this->model->deletePersonPurpose($_POST);
    }

    public function getAttestationVisitTable()
    {
        $title = 'График моих посещений'; 
        $result = $this->model->getAttestationVisitsList();
        $columns = [
            'num' =>'№',
            'whoWasVisited' => 'Учитель',
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

    public function actionSendEmailNotificationA()
    {
        $this->model->sendEmailNotificationA($_POST);
    }

    public function actionGetSynod()
    { 
        return $this->model->getSynod($_POST);
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

    public function actionGetNumberOfAttestationVisits()
    {
        $data = $this->model->getNumberOfAttestationVisits($_POST);
        //$title = "Количество посещении за период с ".date('d.m.Y', strtotime($_POST['start']))." по ".date('d.m.Y', strtotime($_POST['end']));
        $title = "";
        $columns = [
            'status'=>'Статус',
            'number'=>'Количество'
        ];
        echo $this->view->cTable($title,$columns,$data,'commonNumberOfVisits');
    }

    public function actionGetAllAttestationVisits()
    {
        $data = $this->model->getAllAttestationVisits($_POST);
        $title = "Количество посещении за период с ".date('d.m.Y',strtotime($_POST['visitPeriodStart']))." по ".date('d.m.Y',strtotime($_POST['visitPeriodEnd']));
        $columns = [
            'num'=>'№',
            'visitDate'=>'Период',
            'whoVisited'=>'Наблюдатель',
            'whoWasVisited'=>'Учитель',
            'focus'=>'Фокус оценивания',
            'status'=>'Статус'
        ];
        echo $this->view->cTable($title,$columns,$data,'numberOfAllAttestationVisits');
    }

    public function actionGetPersonalAttestationVisits()
    {
        if ($_POST['details'] == '0') {
            $data = $this->model->getPersonalAttestationVisits($_POST);
            if ($_POST['visitType'] == 'WhoVisited') {
                $title = "Количество посещении преподавателя {$_POST['teacher']} как наблюдателя";
            } else {
                $title = "Количество посещении уроков преподавателя {$_POST['teacher']}";
            }
            $columns = [
                'num'=>'№',
                'who'=>$_POST['visitType'] == 'WhoVisited' ? 'Учитель' : 'Наблюдатель',
                'v_cnt'=>'Всего',
                'p_cnt'=>'Запланировано',
                'c_cnt'=>'Подтверждено',
                'o_cnt'=>'В процессе'
            ];
            echo $this->view->cTable($title,$columns,$data,'numberOfVisits');
        } else {
            if ($_POST['detailsDate'] == '0') {
                $data = $this->model->getAllAttestationVisitsInPeriod($_POST);
                if ($_POST['visitType'] == 'WhoVisited') {
                    $title = "Посещения преподавателя {$_POST['teacher']} как наблюдателя";
                } else {
                    $title = "Посещения уроков преподавателя {$_POST['teacher']}";
                }
                $columns = [
                    'num'=>'№',
                    'period'=>'Период',
                    'visitDate'=>'Дата посещения',
                    'person'=>$_POST['visitType'] == 'WhoVisited' ? 'Учитель' : 'Наблюдатель',
                    'focus'=>'Фокус оценивания',
                    'status'=>'Статус'
                ];
                echo $this->view->cTable($title,$columns,$data,'numberOfAllVisits');
            } else {
                $data = $this->model->getAllAttestationVisitsInDetails($_POST);
                if ($_POST['visitType'] == 'WhoVisited') {
                    $title = "Посещения преподавателя {$_POST['teacher']} как наблюдателя за {$_POST['date']}";
                } else {
                    $title = "Посещения уроков преподавателя {$_POST['teacher']} за {$_POST['date']}";
                }
                $columns = [
                    'num'=>'№',
                    'period'=>'Период',
                    'visitDate'=>'Дата посещения',
                    'person'=>$_POST['visitType'] == 'WhoVisited' ? 'Учитель' : 'Наблюдатель',
                    'focus'=>'Фокус оценивания',
                    'status'=>'Статус',
                    'load' =>'Результат'
                ];
                for ($i=0; $i<count($data); $i++) {
                    $data[$i]['load'] = '<button name="saveToPDFforRSh" class="visitBut">Выгрузить</button>';
                }
                echo $this->view->cTable($title,$columns,$data,'numberOfAllVisits');
            }
        }
    }

    public function actionGetLSOSearchResults()
    {
        echo $this->getLSOTable($_POST);
    }

    public function actionGetLSOResults()
    {
        $data = $this->model->getLSOResults($_POST);
        if ($_POST['period'] == 1) {
            echo $this->view->generate('visit/attestation/patternLSO1',$data);
        } else if ($_POST['period'] == 2) {
            echo $this->view->generate('visit/attestation/patternLSO2',$data);
        }
    }

    public function actionSaveLSO()
    {
        $data = $this->model->saveLSO($_POST);
        echo $this->getLSOTable($_POST);
    }

    public function actionGetAllSavedTeachers()
    {
        $data = [
            'id' => 'selectTeacher',
            'size' => '9',
            'items' => $this->model->getAllSavedTeachers()
        ];

        if (!empty($_POST)) {
            $data['selected'] = $_POST['person'];
        }
        echo $this->view->generate('framework/select', $data).'<button id="deleteTeacher">Удалить</button>';
    }

    public function actionGetAllTeachersWithSynod()
    {
        $data = [
            'id' => 'selectTeacherA',
            'size' => '9',
            'items' => $this->model->getAllTeachersWithSynod()
        ];

        if (!empty($_POST)) {
            $data['selected'] = $_POST['person'];
        }
        echo $this->view->generate('framework/select', $data)/*.'<button id="deleteTeacher">Удалить</button>'*/;
    }

    public function actionSetHalfYearPeriods()
    {
        echo $this->model->setHalfYearPeriods($_POST);
    }

    public function actionGetHalfYearPeriods()
    {
        echo $this->model->getHalfYearPeriods();
    }
}