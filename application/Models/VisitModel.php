<?php
namespace Models;

use Core\Model;
use Components\Ad;
use Components\Db;
use Components\DbSkd;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Model for Fixed Assets System
 */
class VisitModel extends Model 
{
    public function __construct($view) {
        parent::__construct();
        $this->view = $view;
    }

    public static function checkUser($iin)
    {
        $ad = Ad::getStaffListFromAD("teacher");
        
        foreach ($ad as $value) { 
            if ($iin == $value['iin']) {
                return true;
            }
        }
        return false;
    }

    public static function getTeacherIin($user)
    {
        $ad = Ad::getStaffListFromAD("teacher");

        foreach ($ad as $value) {
            if (array_search($user,$value)) {
                return $value['iin'];
                break;
            }
        }
    }

    public static function getTexts($section,$property) {
        $ini_params = parse_ini_file(ROOT.'/public/texts/'.$_COOKIE["lang"].'-lang.ini',true);
        return $ini_params[$section][$property];
    }

    public static function getStaffList()
    {
        $user = Ad::getStaffListFromAD("teacher");
        foreach($user as $row) {
            $data[] = $row['FIO']; 
        }
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        echo $data;
    }

    public function getResultsDump()
    {
        if ($_POST['focus'] == 'lso') {
                $this->getLSODumps($_POST, 'lso'.$_POST['mode']);
        } else {
            if ($_POST['mode'] == 'standart') {
                $this->getStandartResultsDump($_POST);
            } else {
                $this->getAttestationResultsDump($_POST);
            }
        }
    }

    public function addVisit($params)
    {
        $params['iinWhoWasVisited'] =$this->getTeacherIin($params['whoWasVisited']);
        $params['whoVisited'] = $this->user->getFullName();
        $params['iinWhoVisited'] = $this->user->getIin();
        $query = "
        INSERT INTO evaluationTeachers (visitDate,lessonNum,whoVisited,whoWasVisited,iinWhoVisited,iinWhoWasVisited,confirmations)
        VALUES (:visitDate,:lessonNum,:whoVisited,:whoWasVisited,:iinWhoVisited,:iinWhoWasVisited,'00');
        ";
        $db = Db::getDb();
        $db->selectQuery($query,$params);
    }

    public function sendEmailNotification($params)
    {
        $query = "SELECT login FROM user WHERE iin=:iin;";
        $db = Db::getDb();
        $mailto = $db->selectQuery($query,['iin' => $this->getTeacherIin($params['whoWasVisited'])]);
        
        /*$query = "SELECT * FROM evaluationTeachers WHERE iinWhoWasVisited=:iin;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,['iin' => $iin]);*/

        $recipient = $mailto[0]['login']; 
        $subject = "Уведомление системы оценивания уроков"; 
        $message = "<p style='font-size: 18px;'>На ваш урок запланировано посещение:</p>";
        $message = $message."<table style='border-collapse: separate; border-spacing: 3px; font-size: 18px;'>
                                <tr>
                                    <th style='padding: 5px; border: 1px solid black; background-color: #C5E1A5'>Наблюдатель</th>
                                    <th style='padding: 5px; border: 1px solid black; background-color: #C5E1A5'>Дата</th>
                                    <th style='padding: 5px; border: 1px solid black; background-color: #C5E1A5'>Урок</th>
                                </tr>
                                <tr>
                                    <td style='padding: 5px; border: 1px solid black; background-color: #F1F8E9'>".$this->user->getFullName()."</th>
                                    <td style='padding: 5px; border: 1px solid black; background-color: #F1F8E9'>".date("d.m.Y",strtotime($params['visitDate']))."</th>
                                    <td style='padding: 5px; border: 1px solid black; background-color: #F1F8E9'>".$params['lessonNum']."</th>
                                </tr>
                            </table>";
        $message = $message."<br><br>Это письмо сформировано и отправлено автоматически. Отвечать на него не нужно.";
        $headers = "From: Cистема оценивания уроков <is@kst.nis.edu.kz>"."\r\n".
                   "Reply-To: is@kst.nis.edu.kz"."\r\n".
                   "MIME-Version: 1.0"."\r\n".
                   "Content-Type: text/html;";
        echo mail($recipient, $subject, $message, $headers);
    }

    public function deleteVisit($params)
    {
        if ($params['mode'] == 'standart') {
            $query = "
            DELETE FROM isdb.evaluationTeachers
            WHERE id = :rowId
            ;";
        } else {
            $query = "
            DELETE FROM isdb.teachers_attestation
            WHERE id = :rowId
            ;"; 
        }
        $db = Db::getDb();
        $db->selectQuery($query,['rowId' => $params['rowId']]);
    }

    public function getVisitsList()
    {
        $params['iin'] = $this->user->getIin();
        //$params['today'] = date("Y-m-d");
        $query = "
        SELECT * FROM evaluationTeachers
        WHERE iinWhoVisited = :iin
        /*AND visitDate >= :today*/
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            if (strtotime($data[$i]['visitDate']) <= strtotime(date("d.m.Y"))) {
                $data[$i]['class'] = 'allowed';
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut">Выгрузить</button>';
                if (!is_null($data[$i]['evaluates']) && $data[$i]['evaluates'] != '' && $data[$i]['evaluates'] != '0000000000000000' && $data[$i]['lessonName'] != '' && $data[$i]['grade'] != '' && $data[$i]['theme'] != '' && $data[$i]['recommendation'] != '' && $data[$i]['purpose_review'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = '<i class="status on_waiting">Ожидает подтверждения</i>';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = '<i class="status confirmed">Подтверждено</i>';
                    } else {
                        $data[$i]['status'] = '<i class="status on_confirmation">На подтверждении</i>';
                    }
                } else {
                    $data[$i]['status'] = '<i class="status on_evaluating">На оценивании</i>';
                }
            } else {
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                $data[$i]['class'] = 'planned';
                $data[$i]['status'] = '<i class="status scheduled">Запланировано</i>';
            }
            if (substr($data[$i]['confirmations'],0,1) == "1") {
                $data[$i]['result'] .= '<button name="deleteResults" class="visitBut" disabled>Удалить</button>';
            } else {
                $data[$i]['result'] .= '<button name="deleteResults" class="visitBut">Удалить</button>';
            }
            $data[$i]['visitDate'] = date("d.m.Y", strtotime($data[$i]['visitDate']));
        }
        return $data;
    }

    public function getEvaluatesList()
    {
        $params['iin'] = $this->user->getIin();
        //$params['today'] = date("Y-m-d");
        $query = "
        SELECT * FROM evaluationTeachers
        WHERE iinWhoWasVisited = :iin
        /*AND visitDate >= :today*/
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            if (strtotime($data[$i]['visitDate']) <= strtotime(date("d.m.Y"))) {
                $data[$i]['class'] = 'allowed';
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut">Выгрузить</button>';
                if ((!is_null($data[$i]['evaluates']) && $data[$i]['evaluates'] != '' && $data[$i]['evaluates'] != '0000000000000000') && $data[$i]['lessonName'] != '' && $data[$i]['grade'] != '' && $data[$i]['theme'] != '' && $data[$i]['recommendation'] != '' && $data[$i]['purpose_review'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = '<i class="status on_waiting">Ожидает подтверждения</i>';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = '<i class="status confirmed">Подтверждено</i>';
                    } else {
                        $data[$i]['status'] = '<i class="status on_confirmation">На подтверждении</i>';
                    }
                } else {
                    $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                    $data[$i]['status'] = '<i class="status on_evaluating">На оценивании</i>';
                }
            } else {
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                $data[$i]['class'] = 'planned';
                $data[$i]['status'] = '<i class="status scheduled">Запланировано</i>';
            }
            $data[$i]['visitDate'] = date("d.m.Y", strtotime($data[$i]['visitDate']));
        }
        return $data;
    }

    public function getDefaultCriteriasList()
    {
        // создаем запрос к БД
        $query = "
        SELECT adt1.discription d1,adt2.discription d2,IFNULL(adt1.rowspan,1) rs
        FROM isdb.evaluationCriterias
        LEFT JOIN isdb.additionalTableForEvaluation adt1
        ON LEFT(r_names,1) = adt1.uid
        LEFT JOIN isdb.additionalTableForEvaluation adt2
        ON RIGHT(r_names,1) = adt2.uid
        ;";
        $db = Db::getDb(); // подключаемся к БД
        $data = $db->selectQuery($query,[]); // выполняем запрос к БД
        $data = $this->addRowNumbers($data); // нумерация строк в результате запроса

        return $data; // выводим результат запроса
    }

    public function getCriteriaDiscription($criteria = null,$mark = "*")
    {
        $data = [];
        if ($mark) {
            if ($criteria) {
                $param['id'] = $criteria; 
                $query = "SELECT ".$mark." disc FROM isdb.evaluationCriterias WHERE id = :id;";
            } else {
                $param = [];
                $query = "SELECT ".$mark." FROM isdb.evaluationCriterias;";
            };
            $db = Db::getDb();
            $data = $db->selectQuery($query,$param);
        }
        return $data;
    }

    public function setVisitResults($params)
    {
        $query = "
        UPDATE isdb.evaluationTeachers
        SET lessonName = :subject, theme = :topic, grade = :grade, evaluates = :marks, recommendation = :recommendation, purpose_review = :purpose_review
        WHERE id = :rowId
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);
        //return $data;
    }

    public function getVisitResults($post_params)
    {
        // создаем запрос к БД
        $query = "
        SELECT visitDate, whoVisited, whoWasVisited, (SELECT teacher_purpose FROM teachers_purposes WHERE teacher_iin = iinWhoWasVisited) AS purpose,
               grade, lessonName, theme, evaluates, recommendation, purpose_review, confirmations
        FROM isdb.evaluationTeachers
        WHERE id = :rowId
        ;";
        $db = Db::getDb(); // подключаемся к БД
        $data = $db->selectQuery($query,['rowId'=>$post_params['rowId']]); // выполняем запрос к БД с параметрами

        return $data; // выводим результат запроса
    }

    public function checkEvaluates($params)
    {
        if ($params['mode'] == 'standart') {
            $query = "
            SELECT evaluates, lessonName, theme, grade, recommendation, purpose_review, confirmations FROM isdb.evaluationTeachers
            WHERE id = :rowId
            ;";
            $db = Db::getDb();
            $data = $db->selectQuery($query,['rowId' => $params['rowId']]);

            if (!is_null($data[0]['evaluates']) && $data[0]['evaluates'] != '0000000000000000' && $data[0]['evaluates'] != '' && $data[0]['lessonName'] != '' && $data[0]['theme'] != '' && ($data[0]['grade'] != '' && strlen($data[0]['grade']) >= 2) && $data[0]['recommendation'] != '' && $data[0]['purpose_review'] != '') {
                if ($data[0]['confirmations'] == '11') {
                    return 'confirmed';
                } else if ($data[0]['confirmations'] == '10' || $data[0]['confirmations'] == '01') {
                    return 'half-confirmed';
                } else { return 'non-confirmed'; }
            } else { return 'none'; }
        } else {
            $query = "
            SELECT evaluates, lessonName, theme, grade, lesson_review, purpose_review, confirmations, focus FROM isdb.teachers_attestation
            WHERE id = :rowId
            ;";
            $db = Db::getDb();
            $data = $db->selectQuery($query,['rowId' => $params['rowId']]);

            $evaluates = '';
            if ($data[0]['focus'] == 'planning') { if ($data[0]['evaluates'] == '000000000000000') { $evaluates = 'null'; } }
            if ($data[0]['focus'] == 'teaching') { if ($data[0]['evaluates'] == '000000000000000000') { $evaluates = 'null'; } }
            if ($data[0]['focus'] == 'evaluating') { if ($data[0]['evaluates'] == '00000') { $evaluates = 'null'; } }
            if ($data[0]['focus'] == 'complex') { if ($data[0]['evaluates'] == '000000000000000000000000') { $evaluates = 'null'; } }

            if (!is_null($data[0]['evaluates']) && $evaluates != 'null' && ($data[0]['lessonName'] != '' && $data[0]['theme'] != '' && ($data[0]['grade'] != '' && strlen($data[0]['grade']) == 2) && $data[0]['lesson_review'] != '' && $data[0]['purpose_review'] != '')) {
                if ($data[0]['confirmations'] == '11') {
                    return 'confirmed';
                } else if ($data[0]['confirmations'] == '10' || $data[0]['confirmations'] == '01') {
                    return 'half-confirmed';
                } else { return 'non-confirmed'; }
            } else { return 'none'; }
        }
    }

    public function checkConfirmations($params)
    {
        if ($params['mode'] == 'standart') {
            $query = "
            SELECT confirmations FROM isdb.evaluationTeachers
            WHERE id = :rowId
            ;";
        } else {
            $query = "
            SELECT confirmations FROM isdb.teachers_attestation
            WHERE id = :rowId
            ;"; 
        }
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);

        if ($data[0]['confirmations'] != '11') {
            return 'full';
        } else if ($data[0]['confirmations'] != '10' || $data[0]['confirmations'] != '01') {
            return 'part';
        } else { return 'none'; }
    }

    public function getSubjects()
    {
        $params = [];
        $query = "
        SELECT discription AS subjects FROM isdb.additionalTableForEvaluation
        WHERE uid = 's'
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);
        return $data;
    }

    public static function getGrades()
    {
        $tsql = "
        SELECT DISTINCT right(Name,1) Litera
        FROM dbo.pDivision 
        WHERE Name LIKE '%[A-O]'
        ";
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql);
        return $data;
    }

    public function setConfirmation($param, $side, $mode)
    {
        if ($mode == 'standart') {
            $query = "
            SELECT confirmations
            FROM isdb.evaluationTeachers
            WHERE id = :rowId
            ;";
        } else {
            $query = "
            SELECT confirmations
            FROM isdb.teachers_attestation
            WHERE id = :rowId
            ;";
        }
        $db = Db::getDb();
        $data = $db->selectQuery($query,['rowId' => $param]);

        if ($side == 'watcher') {
            $confirm = '1'.substr($data[0]['confirmations'],1,1);
        }
        if ($side == 'presenter') {
            $confirm = substr($data[0]['confirmations'],0,1).'1';
        }
        
        if ($mode == 'standart') {
            $query = "
            UPDATE isdb.evaluationTeachers
            SET confirmations = ".$confirm."
            WHERE id = :rowId
            ;";
        } else {
            $query = "
            UPDATE isdb.teachers_attestation
            SET confirmations = ".$confirm."
            WHERE id = :rowId
            ;";
        }
        $db = Db::getDb();
        $db->selectQuery($query,['rowId' => $param]);
    }

    public static function getNumberOfVisits($params)
    {
        $localParams['start1'] = $params['start']; $localParams['end1'] = $params['end'];
        $localParams['start2'] = $params['start']; $localParams['end2'] = $params['end'];
        $localParams['start3'] = $params['start']; $localParams['end3'] = $params['end'];
        $localParams['start4'] = $params['start']; $localParams['end4'] = $params['end'];
        $query = "SELECT 'Всего' status, count(id) number
        FROM isdb.evaluationTeachers
        WHERE visitDate >= :start4 AND visitDate <= :end4
          AND whoVisited <> '' AND whoWasVisited <> ''
          AND whoVisited IS NOT NULL AND whoWasVisited IS NOT NULL
        UNION
        SELECT 'Запланировано', count(id)
        FROM isdb.evaluationTeachers
        WHERE visitDate >= now()
          AND visitDate >= :start2 AND visitDate <= :end2
          AND whoVisited <> '' AND whoWasVisited <> ''
          AND whoVisited IS NOT NULL AND whoWasVisited IS NOT NULL
        UNION
        SELECT 'Подтверждено', count(id)
        FROM isdb.evaluationTeachers
        WHERE LEFT(confirmations,1) = '1'
          AND visitDate >= :start1 AND visitDate <= :end1
          AND whoVisited <> '' AND whoWasVisited <> ''
          AND whoVisited IS NOT NULL AND whoWasVisited IS NOT NULL
        UNION
        SELECT 'В процессе', count(id)
        FROM isdb.evaluationTeachers
        WHERE visitDate <= now() AND LEFT(confirmations,1) = '0'
          AND visitDate >= :start3 AND visitDate <= :end3
          AND whoVisited <> '' AND whoWasVisited <> ''
          AND whoVisited IS NOT NULL AND whoWasVisited IS NOT NULL;
        ";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$localParams);
        return $data;
    }

    public function getPersonalVisits($params) {
        $localParam['iin1'] = $this->getTeacherIin($params['teacher']);
        $localParam['iin2'] = $this->getTeacherIin($params['teacher']);
        $localParam['iin3'] = $this->getTeacherIin($params['teacher']);
        $localParam['iin4'] = $this->getTeacherIin($params['teacher']);
        
        if ($params['visitType'] == 'WhoVisited') {
            $query = "SELECT DISTINCT evaluates.who, evaluates.cnt v_cnt, confirmed.cnt c_cnt, planned.cnt p_cnt, in_process.cnt o_cnt
            FROM (SELECT whoWasVisited who, count(whoWasVisited) cnt
                FROM isdb.evaluationTeachers
                WHERE iinWhoVisited = :iin1
                GROUP BY whoWasVisited) evaluates
            LEFT JOIN (SELECT whoWasVisited who, count(whoWasVisited) cnt
                    FROM isdb.evaluationTeachers
                    WHERE iinWhoVisited = :iin2 AND LEFT(confirmations,1) = '1'
                    GROUP BY whoWasVisited) confirmed
            ON evaluates.who = confirmed.who
            LEFT JOIN (SELECT whoWasVisited who, count(whoWasVisited) cnt
                    FROM isdb.evaluationTeachers
                    WHERE iinWhoVisited = :iin3 AND visitDate >= NOW()
                    GROUP BY whoWasVisited) planned
            ON evaluates.who = planned.who
            LEFT JOIN (SELECT  whoWasVisited who, count(whoWasVisited) cnt
		            FROM isdb.evaluationTeachers
		            WHERE iinWhoVisited = :iin4 AND visitDate <= now() AND LEFT(confirmations,1) = '0'
		                GROUP BY whoWasVisited) in_process
            ON evaluates.who = in_process.who;";
        } else {
            $query = "SELECT DISTINCT evaluates.who, evaluates.cnt v_cnt, confirmed.cnt c_cnt, planned.cnt p_cnt, in_process.cnt o_cnt
            FROM (SELECT whoVisited who, count(whoVisited) cnt
                FROM isdb.evaluationTeachers
                WHERE iinWhoWasVisited = :iin1
                GROUP BY whoVisited) evaluates
            LEFT JOIN (SELECT whoVisited who, count(whoVisited) cnt
                    FROM isdb.evaluationTeachers
                    WHERE iinWhoWasVisited = :iin2 AND LEFT(confirmations,1) = '1'
                    GROUP BY whoVisited) confirmed
            ON evaluates.who = confirmed.who
            LEFT JOIN (SELECT whoVisited who, count(whoVisited) cnt
                    FROM isdb.evaluationTeachers
                    WHERE iinWhoWasVisited = :iin3 AND visitDate >= NOW()
                    GROUP BY whoVisited) planned
            ON evaluates.who = planned.who
            LEFT JOIN (SELECT  whoVisited who, count(whoVisited) cnt
		            FROM isdb.evaluationTeachers
		            WHERE iinWhoWasVisited = :iin4 AND visitDate <= now() AND LEFT(confirmations,1) = '0'
		            GROUP BY whoVisited) in_process
            ON evaluates.who = in_process.who;";
        }
        $db = Db::getDb();
        $data = $db->selectQuery($query,$localParam);
        $data = $this->addRowNumbers($data);
        return $data;
    }

    public function getAllVisitsInPeriod($params) {
        $localParam['iin'] = $this->getTeacherIin($params['teacher']);
        if ($params['visitType'] == 'WhoVisited') {
            $query = "SELECT visitDate, whoWasVisited AS person, lessonNum, evaluates, theme, lessonName, grade, recommendation, confirmations
                      FROM isdb.evaluationTeachers WHERE iinWhoVisited=:iin ORDER BY visitDate";
        } else {
            $query = "SELECT visitDate, whoVisited AS person, lessonNum, evaluates, theme, lessonName, grade, recommendation, confirmations
                      FROM isdb.evaluationTeachers WHERE iinWhoWasVisited=:iin ORDER BY visitDate";
        }
        $db = Db::getDb();
        $data = $db->selectQuery($query,$localParam);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            if (strtotime($data[$i]['visitDate']) <= strtotime(date("d.m.Y"))) {
                if ($data[$i]['evaluates'] != '' && $data[$i]['evaluates'] != '0000000000000000' && $data[$i]['theme'] != '' && $data[$i]['lessonName'] != '' && $data[$i]['grade'] != '' && $data[$i]['recommendation'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = 'Ожидает подтверждения';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = 'Подтверждено';
                    } else {
                        $data[$i]['status'] = 'На подтверждении';
                    }
                } else {
                    $data[$i]['status'] = 'На оценивании';
                }
            } else {
                $data[$i]['status'] = 'Запланировано';
            }
        }
        return $data;
    }

    public function getAllVisitsInDetails($params) {
        $localParam['iin'] = $this->getTeacherIin($params['teacher']);
        $localParam['visitDate'] = $params['date'];
        if ($params['visitType'] == 'WhoVisited') {
            $query = "SELECT id, visitDate, whoWasVisited AS person, lessonNum, evaluates, theme, lessonName, grade, recommendation, confirmations
                      FROM isdb.evaluationTeachers WHERE iinWhoVisited=:iin AND visitDate=:visitDate";
        } else {
            $query = "SELECT id, visitDate, whoVisited AS person, lessonNum, evaluates, theme, lessonName, grade, recommendation, confirmations
                      FROM isdb.evaluationTeachers WHERE iinWhoWasVisited=:iin AND visitDate=:visitDate";
        }
        $db = Db::getDb();
        $data = $db->selectQuery($query,$localParam);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            if (strtotime($data[$i]['visitDate']) <= strtotime(date("d.m.Y"))) {
                if ($data[$i]['evaluates'] != '' && $data[$i]['evaluates'] != '0000000000000000' && $data[$i]['theme'] != '' && $data[$i]['lessonName'] != '' && $data[$i]['grade'] != '' && $data[$i]['recommendation'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = 'Ожидает подтверждения';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = 'Подтверждено';
                    } else {
                        $data[$i]['status'] = 'На подтверждении';
                    }
                } else {
                    $data[$i]['status'] = 'На оценивании';
                }
            } else {
                $data[$i]['status'] = 'Запланировано';
            }
        }
        return $data;
    }

    public function getAllVisits($params)
    {
        $query = "SELECT * FROM isdb.evaluationTeachers
                  WHERE visitDate >= :visitPeriodStart AND visitDate <= :visitPeriodEnd
                    AND whoVisited <> '' AND whoWasVisited <> ''
                  ORDER BY visitDate";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            if (strtotime($data[$i]['visitDate']) <= strtotime(date("d.m.Y"))) {
                if ($data[$i]['evaluates'] != '' && $data[$i]['evaluates'] != '0000000000000000' && $data[$i]['theme'] != '' && $data[$i]['lessonName'] != '' && $data[$i]['grade'] != '' && $data[$i]['recommendation'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = 'Ожидает подтверждения';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = 'Подтверждено';
                    } else {
                        $data[$i]['status'] = 'На подтверждении';
                    }
                } else {
                    $data[$i]['status'] = 'На оценивании';
                }
            } else {
                $data[$i]['status'] = 'Запланировано';
            }
        }
        return $data;
    }

    public function getStandartResultsDump($params)
    {
        require_once ROOT.'/application/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('Система наблюдения уроков')
        ->setLastModifiedBy('Система наблюдения уроков')
        ->setTitle('Результаты оценивания урока')
        ->setSubject('Результаты оценивания урока')
        ->setDescription('Результаты оценивания урока')
        ->setKeywords('office 2007 openxml php')
        ->setCategory('Отчет');

        // Add data from model
        $arrayData = self::getVisitResults($params);
        $arrayDefCriterias = self::getDefaultCriteriasList();
        $arrayCriteriaDiscription = self::getCriteriaDiscription();
        
        // Width for cells
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(40);

        // Height for cells
        $spreadsheet->getActiveSheet()->getRowDimension(1)->setRowHeight(50);

        // Put headers
        $spreadsheet->getActiveSheet()->mergeCells('A1:D1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', $this->getTexts('TABLE_HEADERS', 'caption'));
        $spreadsheet->getActiveSheet()->setCellValue('A2', $this->getTexts('TABLE_HEADERS', 'date'));
        $spreadsheet->getActiveSheet()->setCellValue('B2', $this->getTexts('TABLE_HEADERS', 'grade'));
        $spreadsheet->getActiveSheet()->setCellValue('C2', $this->getTexts('TABLE_HEADERS', 'subject'));
        $spreadsheet->getActiveSheet()->setCellValue('D2', $this->getTexts('TABLE_HEADERS', 'theme'));
        $spreadsheet->getActiveSheet()->setCellValue('A3', $arrayData[0]['visitDate']);
        $spreadsheet->getActiveSheet()->setCellValue('B3', $arrayData[0]['grade']);
        $spreadsheet->getActiveSheet()->setCellValue('C3', $arrayData[0]['lessonName']);
        $spreadsheet->getActiveSheet()->setCellValue('D3', $arrayData[0]['theme']);
        $spreadsheet->getActiveSheet()->mergeCells('A4:B4');
        $spreadsheet->getActiveSheet()->setCellValue('A4', $this->getTexts('TABLE_HEADERS', 'whoWasVisited'));
        $spreadsheet->getActiveSheet()->mergeCells('C4:D4');
        $spreadsheet->getActiveSheet()->setCellValue('C4', $arrayData[0]['whoWasVisited']);
        $spreadsheet->getActiveSheet()->mergeCells('A5:B5');
        $spreadsheet->getActiveSheet()->setCellValue('A5', $this->getTexts('TABLE_HEADERS', 'whoVisited'));
        $spreadsheet->getActiveSheet()->mergeCells('C5:D5');
        $spreadsheet->getActiveSheet()->setCellValue('C5', $arrayData[0]['whoVisited']);
        $spreadsheet->getActiveSheet()->mergeCells('A6:D6');
        $spreadsheet->getActiveSheet()->setCellValue('A6', $this->getTexts('TABLE_HEADERS', 'purpose'));
        $spreadsheet->getActiveSheet()->mergeCells('A7:D7');
        $spreadsheet->getActiveSheet()->setCellValue('A7', $arrayData[0]['purpose']);
        $l = strlen($arrayData[0]['purpose']) == 0 ? 24 : mb_strlen($arrayData[0]['purpose']);
        $spreadsheet->getActiveSheet()->getRowDimension(7)->setRowHeight($l/24*18.75);
        $spreadsheet->getActiveSheet()->mergeCells('A8:D8');
        $spreadsheet->getActiveSheet()->setCellValue('A8', $this->getTexts('TABLE_HEADERS', 'feedback'));
        
        // Put data into cells
        foreach ($arrayDefCriterias as $criteria) {
            $i = $criteria['num'] + 8;

            if ($criteria['rs'] > 1) {
                $spreadsheet->getActiveSheet()->mergeCells('A'.$i.':A'.($i+$criteria['rs']-1));
            }
            if (!is_null($criteria['d1'])) {
                $spreadsheet->getActiveSheet()->setCellValue('A'.$i, $this->getTexts('CRITERIAS',$criteria['d1']));
            }
            $spreadsheet->getActiveSheet()->setCellValue('B'.$i, $this->getTexts('CRITERIAS',$criteria['d2']));
            $spreadsheet->getActiveSheet()->setCellValue('C'.$i, substr($arrayData[0]['evaluates'],$criteria['num']-1,1));

            switch (substr($arrayData[0]['evaluates'],$criteria['num']-1,1)) {
                case '1':
                    $mark = $this->getTexts('DESCRIPTIONS',$arrayCriteriaDiscription[$criteria['num']-1]['one']);
                    break;
                case '2':
                    $mark = $this->getTexts('DESCRIPTIONS',$arrayCriteriaDiscription[$criteria['num']-1]['two']);
                    break;
                case '3':
                    $mark = $this->getTexts('DESCRIPTIONS',$arrayCriteriaDiscription[$criteria['num']-1]['three']);
                    break;
                case '4':
                    $mark = $this->getTexts('DESCRIPTIONS',$arrayCriteriaDiscription[$criteria['num']-1]['four']);
                    break;
                case '5':
                    $mark = $this->getTexts('DESCRIPTIONS',$arrayCriteriaDiscription[$criteria['num']-1]['five']);
                    break;
                default:
                    $mark = '';
                    break;
            }
            $spreadsheet->getActiveSheet()->setCellValue('D'.$i, $mark);
            $l = strlen($mark) == 0 ? 24 : mb_strlen($mark);
            $spreadsheet->getActiveSheet()->getRowDimension($i)->setRowHeight($l/24*18.75);
        }

        $spreadsheet->getActiveSheet()->mergeCells('A25:D25');
        $spreadsheet->getActiveSheet()->setCellValue('A25', $this->getTexts('TABLE_HEADERS', 'recommendation'));
        $spreadsheet->getActiveSheet()->mergeCells('A26:D26');
        $spreadsheet->getActiveSheet()->setCellValue('A26', $arrayData[0]['recommendation']);
        $l = strlen($arrayData[0]['recommendation']) == 0 ? 24 : mb_strlen($arrayData[0]['recommendation']);
        $spreadsheet->getActiveSheet()->getRowDimension(26)->setRowHeight($l/24*18.75);
        $spreadsheet->getActiveSheet()->mergeCells('A27:D27');
        $spreadsheet->getActiveSheet()->setCellValue('A27', $this->getTexts('TABLE_HEADERS', 'purpose_feedback'));
        $l = strlen($this->getTexts('TABLE_HEADERS', 'purpose_feedback')) == 0 ? 24 : mb_strlen($this->getTexts('TABLE_HEADERS', 'purpose_feedback'));
        $spreadsheet->getActiveSheet()->getRowDimension(27)->setRowHeight($l/24*18.75);
        $spreadsheet->getActiveSheet()->mergeCells('A28:D28');
        $spreadsheet->getActiveSheet()->setCellValue('A28', $arrayData[0]['purpose_review']);
        $l = strlen($arrayData[0]['purpose_review']) == 0 ? 24 : mb_strlen($arrayData[0]['purpose_review']);
        $spreadsheet->getActiveSheet()->getRowDimension(28)->setRowHeight($l/24*18.75);

        // Rename worksheet
        $spreadsheet->getActiveSheet()->setTitle('Результат оценки урока');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Результат оценки урока.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        $styleHeaders = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'bold' => true,
                'size' => 14
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $styleHeaderData = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'font' => [
                'size' => 14
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $styleData = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'font' => [
                'size' => 14
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $styleNumbers = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $styleFooterFont = [
            'font' => [
                'size' => 14
            ],
        ];
        $styleFooterAlign = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        $spreadsheet->getActiveSheet()->getStyle('A1:D28')->getAlignment()->setWrapText(true);

        $spreadsheet->getActiveSheet()->getStyle('C9:C24')->applyFromArray($styleNumbers);

        $spreadsheet->getActiveSheet()->getStyle('A1')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A2:D2')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A9:A24')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A4:B5')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A6:D6')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A8:D8')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A25:D25')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A27:D27')->applyFromArray($styleHeaders);
        
        $spreadsheet->getActiveSheet()->getStyle('A3:D3')->applyFromArray($styleHeaderData);
        $spreadsheet->getActiveSheet()->getStyle('C4:D5')->applyFromArray($styleHeaderData);

        $spreadsheet->getActiveSheet()->getStyle('A7:D7')->applyFromArray($styleData);
        $spreadsheet->getActiveSheet()->getStyle('D9:D24')->applyFromArray($styleData);
        $spreadsheet->getActiveSheet()->getStyle('B9:D24')->applyFromArray($styleData);
        $spreadsheet->getActiveSheet()->getStyle('A26:D26')->applyFromArray($styleData);
        $spreadsheet->getActiveSheet()->getStyle('A28:D28')->applyFromArray($styleData);

        //$spreadsheet->getActiveSheet()->getStyle('A26')->applyFromArray($styleFooterAlign);
        //$spreadsheet->getActiveSheet()->getStyle('A27')->applyFromArray($styleFooterAlign);
        //$spreadsheet->getActiveSheet()->getStyle('A26')->applyFromArray($styleFooterFont);
        //$spreadsheet->getActiveSheet()->getStyle('A27')->applyFromArray($styleFooterFont);
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function getDataForReport1($user)
    {
        $localParams['iin'] = $this->getTeacherIin($user);
        $query = "SELECT visitDate, whoVisited, evaluates FROM isdb.evaluationTeachers
                  WHERE iinWhoWasVisited = :iin
                    AND evaluates IS NOT null
                    AND evaluates <> '0000000000000000'
                  ORDER BY visitDate;
                 ";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$localParams);
        return $data;
    }

    public function getDumpForReport1($params)
    {
        require_once ROOT.'/application/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('Система наблюдения уроков')
        ->setLastModifiedBy('Система наблюдения уроков')
        ->setTitle('Индивидуальный отчет по критериям')
        ->setSubject('Индивидуальный отчет по критериям')
        ->setDescription('Индивидуальный отчет по критериям')
        ->setKeywords('office 2007 openxml php')
        ->setCategory('Отчет');

        // Add data from model
        $arrayData = self::getDataForReport1($params['whoWasVisited']);
        $arrayDefCriterias = self::getDefaultCriteriasList();
        $colCount = count($arrayData);
        
        // Cell styles
        $styleHeader = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                        'font' => ['bold' => true, 'size' => 14]];
        $styleTopHeaders = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                            'font' => ['bold' => true, 'size' => 12],
                            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]];
        $styleLeftHeaders = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                             'font' => ['bold' => true, 'size' => 12],
                             'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                             'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3E5F5']]];
        $styleData = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                      'font' => ['size' => 12],
                      'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                      'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]];
        $styleBackground = ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E1EDD0']]];
        $styleAverage = ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFECB3']]];

        // Column width
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(25);

        // Put headers
        $spreadsheet->getActiveSheet()->mergeCells('A1:Q1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Индивидуальный отчет по критериям: '.$params['whoWasVisited']);
        
        // Put data into cells
        $col = 66; //"B" letter's number in ASCII table
        foreach ($arrayDefCriterias as $criteria) {
            $spreadsheet->getActiveSheet()->setCellValue(chr($col).'2', $this->getTexts('CRITERIAS', $criteria['d2']));
            $spreadsheet->getActiveSheet()->getColumnDimension(chr($col))->setWidth(12);
        
            for ($k = 0; $k <= 15; $k++) { $averages[$k] = 0; $avCount[$k] = 0; };
            
            $row = 3;
            foreach ($arrayData as $data) {
                $spreadsheet->getActiveSheet()->setCellValue('A'.$row, $data['whoVisited'].chr(10).'('.$data['visitDate'].')');
                $spreadsheet->getActiveSheet()->getStyle('A'.$row)->applyFromArray($styleTopHeaders);
                
                $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row), substr($data['evaluates'],$col-66,1));
                $averages[$col-66] += (int)substr($data['evaluates'],$col-66,1);
                if (substr($data['evaluates'],$col-66,1) != '0') { $avCount[$col-66]++; }

                if ($row % 2 != 0) {
                    $spreadsheet->getActiveSheet()->getStyle('B'.($row).':Q'.($row))->applyFromArray($styleBackground);
                }

                $row++;
            }

            $spreadsheet->getActiveSheet()->setCellValue('A'.$row, 'Средний балл');
            $spreadsheet->getActiveSheet()->setCellValue('A'.($row+1), 'В процентах');
            if ($avCount[$col-66] != 0) {
                $avg = $averages[$col-66]/$avCount[$col-66];
                $spreadsheet->getActiveSheet()->setCellValue(chr($col).$row, number_format($avg,2,',',''));
                $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row+1), (number_format($avg*100/5,2,',','')).'%');
            } else {
                $spreadsheet->getActiveSheet()->setCellValue(chr($col).$row, "0");
                $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row+1), "0%");
            }
            
            $col++;
        }

        $spreadsheet->getActiveSheet()->getStyle('A2:Q30')->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getRowDimension(1)->setRowHeight(25);
        $spreadsheet->getActiveSheet()->getRowDimension(2)->setRowHeight(175);
        $spreadsheet->getActiveSheet()->getStyle('A1')->applyFromArray($styleHeader);
        $spreadsheet->getActiveSheet()->getStyle('A3:A'.($row+1))->applyFromArray($styleLeftHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A'.$row.':Q'.$row)->applyFromArray($styleAverage);
        $spreadsheet->getActiveSheet()->getStyle('A'.($row+1).':Q'.($row+1))->applyFromArray($styleAverage);
        $spreadsheet->getActiveSheet()->getStyle('B2:Q2')->getAlignment()->setTextRotation(90);
        $spreadsheet->getActiveSheet()->getStyle('B2:Q2')->applyFromArray($styleTopHeaders);
        $spreadsheet->getActiveSheet()->getStyle('B3:Q'.($row+1))->applyFromArray($styleData);

        // Rename worksheet
        $spreadsheet->getActiveSheet()->setTitle('Свод по критериям');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Индивидуальный отчет по критериям.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public static function getDataForReport2($post)
    {
        switch ($post['mode']) {
            case 'forDay':
                $where = "AND visitDate = :day";
                $param['day'] = $post['params'];
                break;
            case 'forMonth':
                $where = "AND month(visitDate) = :month";
                $param['month'] = $post['params'];
                break;
            case 'forPeriod':
                $where = "AND visitDate >= :start AND visitDate <= :end";
                $param['start'] = substr($post['params'], 0, strpos($post['params'], '|'));
                $param['end'] = substr($post['params'], strpos($post['params'], '|')+1, strlen($post['params']));
                break;
            case 'forAllTime':
                $where = '';
                $param = [];
                break;
        }
        $query = "SELECT evaluates, lessonName FROM isdb.evaluationTeachers
                  WHERE evaluates IS NOT null
                    AND evaluates <> '0000000000000000'
                    AND lessonName <> '' ".$where."
                  ORDER BY lessonName;
                 ";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$param);
        return $data;
    }

    public static function getActualLessons($post)
    {
        switch ($post['mode']) {
            case 'forDay':
                $where = "AND visitDate = :day";
                $param['day'] = $post['params'];
                break;
            case 'forMonth':
                $where = "AND month(visitDate) = :month";
                $param['month'] = $post['params'];
                break;
            case 'forPeriod':
                $where = "AND visitDate >= :start AND visitDate <= :end";
                $param['start'] = substr($post['params'], 0, strpos($post['params'], '|'));
                $param['end'] = substr($post['params'], strpos($post['params'], '|')+1, strlen($post['params']));
                break;
            case 'forAllTime':
                $where = '';
                $param = [];
                break;
        }
        $query = "SELECT DISTINCT lessonName FROM isdb.evaluationTeachers
                  WHERE evaluates IS NOT null
                    AND evaluates <> '0000000000000000'
                    AND lessonName <> '' ".$where."
                  ORDER BY lessonName;
                 ";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$param);
        return $data;       
    }

    public function getDumpForReport2($param)
    {
        require_once ROOT.'/application/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $spreadsheet = new Spreadsheet();

        // Cell styles
        $styleHeader = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                        'font' => ['bold' => true, 'size' => 14]];
        $styleTopHeaders = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                            'font' => ['bold' => true, 'size' => 12],
                            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]];
        $styleLeftHeaders = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
                             'font' => ['bold' => true, 'size' => 12],
                             'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                             'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3E5F5']]];
        $styleData = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
                      'font' => ['size' => 12],
                      'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                      'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]];
        $styleBackground = ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E1EDD0']]];
        $styleBoldBorder = ['borders' => ['top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM]]];
        $styleAverage = ['fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFECB3']]];

        // Set document properties
        $spreadsheet->getProperties()->setCreator('Система наблюдения уроков')
        ->setLastModifiedBy('Система наблюдения уроков')
        ->setTitle('Отчет по критериям в разрезе предметов')
        ->setSubject('Отчет по критериям в разрезе предметов')
        ->setDescription('Отчет по критериям в разрезе предметов')
        ->setKeywords('office 2007 openxml php')
        ->setCategory('Отчет');

        // Add data from model
        $arrayData = self::getDataForReport2($param);
        $arrayDefCriterias = self::getDefaultCriteriasList();
        $actualLessons = self::getActualLessons($param);
        $colCount = count($actualLessons);

        // Put headers
        $spreadsheet->getActiveSheet()->mergeCells('A1:Q1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Отчет по критериям в разрезе предметов');

        // Put data into cells
        $col = 66; //"B" letter's number in ASCII table
        foreach ($arrayDefCriterias as $criteria) {
            $spreadsheet->getActiveSheet()->setCellValue(chr($col).'2', $this->getTexts('CRITERIAS', $criteria['d2']));
            $spreadsheet->getActiveSheet()->getColumnDimension(chr($col))->setWidth(12);

            for ($n = 0; $n <= 15; $n++) { $common[$n] = 0; $eCount[$n] = 0; };

            $row = 3;
            foreach ($actualLessons as $lesson) {
                $average = 0;
                $spreadsheet->getActiveSheet()->mergeCells('A'.($row).':A'.($row+3));
                $spreadsheet->getActiveSheet()->setCellValue('A'.($row), $lesson['lessonName']);

                $c = 0;

                foreach ($arrayData as $data) {
                    if ($data['lessonName'] == $lesson['lessonName']) {
                        if (substr($data['evaluates'],$col-66,1) != '0') {
                            $c++;
                            $average += (int)substr($data['evaluates'],$col-66,1);
                        };
                    }
                }
                
                if ($c != 0) { 
                    $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row), $average);
                    $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row+1), $c);
                    $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row+2), number_format($average/$c, 1,',',''));
                    $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row+3), number_format($average/($c*5)*100, 2,',','').'%');
                    $common[$col-66] += $average;
                    $eCount[$col-66] += $c;
                } else {
                    $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row), $c);
                    $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row+1), $c);
                    $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row+2), number_format($c, 1,',',''));
                    $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row+3), $c.'%');
                    $common[$col-66] += (int)number_format($c, 1,',','');
                }

                if (intval($row/4) % 2 != 0) {
                    $spreadsheet->getActiveSheet()->getStyle('B'.($row).':Q'.($row+3))->applyFromArray($styleBackground);
                }

                $row = $row + 4;
            }

            $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row), $common[$col-66]);
            $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row+1), $eCount[$col-66]);
            $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row+2), number_format($common[$col-66]/$eCount[$col-66], 2,',',''));
            $spreadsheet->getActiveSheet()->setCellValue(chr($col).($row+3), number_format($common[$col-66]/($eCount[$col-66]*5)*100, 2,',','').'%');

            $col++;
        }
        
        $spreadsheet->getActiveSheet()->mergeCells('A'.($row).':A'.($row+3));
        $spreadsheet->getActiveSheet()->setCellValue('A'.($row), 'Средний балл');

        //Setting styles
        $spreadsheet->getActiveSheet()->getRowDimension(1)->setRowHeight(25);
        $spreadsheet->getActiveSheet()->getRowDimension(2)->setRowHeight(175);
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(25);
        $spreadsheet->getActiveSheet()->getStyle('A1')->applyFromArray($styleHeader);
        $spreadsheet->getActiveSheet()->getStyle('A2:Q'.($row+3))->getAlignment()->setWrapText(true);
        $spreadsheet->getActiveSheet()->getStyle('A3:A'.($row+3))->applyFromArray($styleLeftHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A'.$row.':Q'.($row+3))->applyFromArray($styleAverage);
        $spreadsheet->getActiveSheet()->getStyle('B2:Q2')->getAlignment()->setTextRotation(90);
        $spreadsheet->getActiveSheet()->getStyle('B2:Q2')->applyFromArray($styleTopHeaders);
        $spreadsheet->getActiveSheet()->getStyle('B3:Q'.($row+3))->applyFromArray($styleData);
    
        for ($c = 0; $c <= count($actualLessons); $c++) {
            $r = $c*4+3;
            $spreadsheet->getActiveSheet()->getStyle('A'.($r).':Q'.($r))->applyFromArray($styleBoldBorder);
        };

        // Rename worksheet
        $spreadsheet->getActiveSheet()->setTitle('Свод по критериям');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Отчет по критериям в разрезе предметов.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function managePersonPurpose($post_params)
    {
        //$params['iin'] = $this->getTeacherIin($post_params['person']);
        $query = "SELECT CONCAT(IF(teacher_purpose IS NULL,'',teacher_purpose),';',IF(teachers_level IS NULL,'',teachers_level),';',IF(teachers_level_up IS NULL,'',teachers_level_up)) AS purposes
                  FROM isdb.teachers_purposes WHERE id = :id;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$post_params);

        if (!empty($data)) { 
            return $data[0]['purposes']; 
        } else {
            return 'empty';
        }
    }

    public function savePersonPurpose($params)
    {
        if ($params['mode'] == 'new') {
            $prm['iin'] = $this->getTeacherIin($params['person']);
            $prm['person'] = $params['person'];
            $query = "INSERT INTO isdb.teachers_purposes (teacher_iin, teacher_fio)
                      VALUES (:iin, :person);";
            $db = Db::getDb();
            $data = $db->selectQuery($query,$prm);
        } else {
            $prm['id'] = $params['id'];
            $prm['purpose'] = $params['purpose'];
            $prm['cur_level'] = $params['cur_level'];
            $prm['up_level'] = $params['up_level'];
            $query = "UPDATE isdb.teachers_purposes
                      SET teacher_purpose = :purpose, teachers_level = :cur_level, teachers_level_up = :up_level
                      WHERE id = :id
            ;";
            $db = Db::getDb();
            $data = $db->IUDQuery($query,$prm);
        }

        return 'OK';
    }

    public function deletePersonPurpose($params)
    {
        $query = "DELETE FROM isdb.teachers_purposes WHERE id = :id;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);

        return 'OK';
    }

    public function getAttestationVisitsList()
    {
        $params['iin'] = $this->user->getIin();
        $query = "
        SELECT * FROM teachers_attestation
        WHERE iinWhoVisited = :iin
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            if (strtotime($data[$i]['visitDateFrom']) <= strtotime(date("d.m.Y"))) {
                $data[$i]['class'] = 'allowed';
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut">Выгрузить</button>';
                if ($data[$i]['evaluates'] != '' && $data[$i]['lesson_review'] != '' && $data[$i]['purpose_review'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = '<i class="status on_waiting">Ожидает подтверждения</i>';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = '<i class="status confirmed">Подтверждено</i>';
                    } else {
                        $data[$i]['status'] = '<i class="status on_confirmation">На подтверждении</i>';
                    }
                } else {
                    $data[$i]['status'] = '<i class="status on_evaluating">На оценивании</i>';
                }
                if (strtotime($data[$i]['visitDateTo']) <= strtotime(date("d.m.Y")) && $data[$i]['confirmations'] == "00") {
                    $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                    $data[$i]['status'] = '<i class="status deadline_is_out">Срок посещения истёк</i>';
                }
            } else {
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                $data[$i]['class'] = 'planned';
                $data[$i]['status'] = '<i class="status scheduled">Запланировано</i>';
            }
            $data[$i]['visitDate'] = date("d.m.Y", strtotime($data[$i]['visitDateFrom'])).' - '.date("d.m.Y", strtotime($data[$i]['visitDateTo']));
            switch ($data[$i]['focus']) {
                case 'planning':
                    $data[$i]['focus'] = 'Планирование';
                    break;
                case 'teaching':
                    $data[$i]['focus'] = 'Преподавание';
                    break;
                case 'evaluating':
                    $data[$i]['focus'] = 'Оценивание учебных достижений';
                    break;
                case 'complex':
                    $data[$i]['focus'] = 'Комплексный анализ урока';
                    break;
            }
        }
        return $data;
    }

    public function getAttestationEvaluatesList()
    {
        $params['iin'] = $this->user->getIin();
        $query = "
        SELECT * FROM teachers_attestation
        WHERE iinWhoWasVisited = :iin
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            if (strtotime($data[$i]['visitDateFrom']) <= strtotime(date("d.m.Y")) || strtotime($data[$i]['visitDateTo']) >= strtotime(date("d.m.Y"))) {
                $data[$i]['class'] = 'allowed';
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut">Выгрузить</button>';
                if ($data[$i]['evaluates'] != '' && $data[$i]['lesson_review'] != '' && $data[$i]['purpose_review'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = '<i class="status on_waiting">Ожидает подтверждения</i>';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = '<i class="status confirmed">Подтверждено</i>';
                    } else {
                        $data[$i]['status'] = '<i class="status on_confirmation">На подтверждении</i>';
                    }
                } else {
                    $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                    $data[$i]['status'] = '<i class="status on_evaluating">На оценивании</i>';
                }
            } else {
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                $data[$i]['class'] = 'planned';
                if (strtotime($data[$i]['visitDateFrom']) >= strtotime(date("d.m.Y"))) {
                    $data[$i]['status'] = '<i class="status scheduled">Запланировано</i>';
                }
                if (strtotime($data[$i]['visitDateTo']) <= strtotime(date("d.m.Y"))) {
                    $data[$i]['status'] = '<i class="status deadline_is_out">Срок посещения истёк</i>';
                }
            }
            $data[$i]['visitDate'] = date("d.m.Y", strtotime($data[$i]['visitDateFrom'])).' - '.date("d.m.Y", strtotime($data[$i]['visitDateTo']));
            switch ($data[$i]['focus']) {
                case 'planning':
                    $data[$i]['focus'] = 'Планирование';
                    break;
                case 'teaching':
                    $data[$i]['focus'] = 'Преподавание';
                    break;
                case 'evaluating':
                    $data[$i]['focus'] = 'Оценивание учебных достижений';
                    break;
                case 'complex':
                    $data[$i]['focus'] = 'Комплексный анализ урока';
                    break;
            }
        }
        return $data;
    }

    public function getSynod($params)
    {
        $query = "SELECT focus, visitDateFrom, visitDateTo, whoVisited, iinWhoVisited
                  FROM isdb.teachers_attestation
                  WHERE iinWhoWasVisited = :iin
                  ORDER BY groupId DESC;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,['iin' => $this->getTeacherIin($params['person'])]);
        $str = '';
        //print_r($data);
        foreach ($data as $key => $value) {
            $str .= implode('|', $value).'|';
        }
        echo $str;
    }

    public function saveSynod($post_params)
    {
        if (array_key_exists('id', $post_params)) {
            $maxGroupId = $post_params['id'];
            $all_params = [['visitDateFrom' =>  $post_params['p_date_from'],
                            'visitDateTo' =>  $post_params['p_date_to'],
                            'iinWhoVisited' => $this->getTeacherIin($post_params['p_person']),
                            'WhoVisited' => $post_params['p_person'],
                            'focus' => 'planning',
                            'groupId' => $maxGroupId],
                           ['visitDateFrom' =>  $post_params['t_date_from'],
                            'visitDateTo' =>  $post_params['t_date_to'],
                            'iinWhoVisited' => $this->getTeacherIin($post_params['t_person']),
                            'WhoVisited' => $post_params['t_person'],
                            'focus' => 'teaching',
                            'groupId' => $maxGroupId],
                           ['visitDateFrom' =>  $post_params['e_date_from'],
                            'visitDateTo' =>  $post_params['e_date_to'],
                            'iinWhoVisited' => $this->getTeacherIin($post_params['e_person']),
                            'WhoVisited' => $post_params['e_person'],
                            'focus' => 'evaluating',
                            'groupId' => $maxGroupId],
                           ['visitDateFrom' =>  $post_params['c_date_from'],
                            'visitDateTo' =>  $post_params['c_date_to'],
                            'iinWhoVisited' => $this->getTeacherIin($post_params['c_person']),
                            'WhoVisited' => $post_params['c_person'],
                            'focus' => 'complex',
                            'groupId' => $maxGroupId]];
            foreach ($all_params as $key => $value) {
                $query = "UPDATE isdb.teachers_attestation
                            SET visitDateFrom = :visitDateFrom,
                                visitDateTo = :visitDateTo,
                                iinWhoVisited = :iinWhoVisited,
                                WhoVisited = :WhoVisited
                            WHERE groupId = :groupId AND focus = :focus;";
                $db = Db::getDb();
                $data = $db->selectQuery($query,$all_params[$key]);            
            }
        } else {
            $maxGroupId = $this->getMaxGroupId();
            $all_params = [['visitDateFrom' =>  $post_params['p_date_from'],
                            'visitDateTo' =>  $post_params['p_date_to'],
                            'iinWhoWasVisited' => $this->getTeacherIin($post_params['person']),
                            'WhoWasVisited' => $post_params['person'],
                            'iinWhoVisited' => $this->getTeacherIin($post_params['p_person']),
                            'WhoVisited' => $post_params['p_person'],
                            'focus' => 'planning',
                            'groupId' => $maxGroupId],
                        ['visitDateFrom' =>  $post_params['t_date_from'],
                            'visitDateTo' =>  $post_params['t_date_to'],
                            'iinWhoWasVisited' => $this->getTeacherIin($post_params['person']),
                            'WhoWasVisited' => $post_params['person'],
                            'iinWhoVisited' => $this->getTeacherIin($post_params['t_person']),
                            'WhoVisited' => $post_params['t_person'],
                            'focus' => 'teaching',
                            'groupId' => $maxGroupId],
                        ['visitDateFrom' =>  $post_params['e_date_from'],
                            'visitDateTo' =>  $post_params['e_date_to'],
                            'iinWhoWasVisited' => $this->getTeacherIin($post_params['person']),
                            'WhoWasVisited' => $post_params['person'],
                            'iinWhoVisited' => $this->getTeacherIin($post_params['e_person']),
                            'WhoVisited' => $post_params['e_person'],
                            'focus' => 'evaluating',
                            'groupId' => $maxGroupId],
                        ['visitDateFrom' =>  $post_params['c_date_from'],
                            'visitDateTo' =>  $post_params['c_date_to'],
                            'iinWhoWasVisited' => $this->getTeacherIin($post_params['person']),
                            'WhoWasVisited' => $post_params['person'],
                            'iinWhoVisited' => $this->getTeacherIin($post_params['c_person']),
                            'WhoVisited' => $post_params['c_person'],
                            'focus' => 'complex',
                            'groupId' => $maxGroupId]];
            foreach ($all_params as $key => $value) {
                $query = "INSERT INTO isdb.teachers_attestation (visitDateFrom, visitDateTo, iinWhoWasVisited, WhoWasVisited, iinWhoVisited, WhoVisited, focus, groupId, confirmations)
                        VALUES (:visitDateFrom, :visitDateTo, :iinWhoWasVisited, :WhoWasVisited, :iinWhoVisited, :WhoVisited, :focus, :groupId, '00');";
                $db = Db::getDb();
                $data = $db->selectQuery($query,$all_params[$key]);            
            }

            $lso_params = ['groupId' => $maxGroupId, 'iinWhoWasVisited' => $this->getTeacherIin($post_params['person']), 'whoWasVisited' => $post_params['person']];
            $query = "INSERT INTO isdb.teachers_lso (id, iinWhoWasVisited, WhoWasVisited) VALUES (:groupId, :iinWhoWasVisited, :WhoWasVisited);";
            $db = Db::getDb();
            $data = $db->selectQuery($query,$all_params[$key]);
        }    
        
        //return $data;
    }

    public function sendEmailNotificationA($params)
    {
        $query = "SELECT visitDateFrom, visitDateTo, iinWhoWasVisited, whoWasVisited, iinWhoVisited, whoVisited, focus, u1.login login1, u2.login login2
                  FROM isdb.teachers_attestation a
                  LEFT JOIN isdb.user u1 ON u1.iin=a.iinWhoVisited
                  LEFT JOIN isdb.user u2 ON u2.iin=a.iinWhoWasVisited
                  WHERE iinWhoWasVisited=:iin1 AND (iinWhoVisited=:iin2 OR iinWhoVisited=:iin3 OR iinWhoVisited=:iin4 OR iinWhoVisited=:iin5)
                  ORDER BY id DESC LIMIT 4;";
        $db = Db::getDb();
        $mailto = $db->selectQuery($query,['iin1' => $this->getTeacherIin($params['person']),
                                           'iin2' => $this->getTeacherIin($params['p_person']),
                                           'iin3' => $this->getTeacherIin($params['t_person']),
                                           'iin4' => $this->getTeacherIin($params['e_person']),
                                           'iin5' => $this->getTeacherIin($params['c_person'])]);
        print_r($mailto);

        $table = "<table style='border-collapse: separate; border-spacing: 3px; font-size: 18px;'>
                    <tr>
                        <th style='padding: 5px; border: 1px solid black; background-color: #C5E1A5'>Наблюдатель</th>
                        <th style='padding: 5px; border: 1px solid black; background-color: #C5E1A5'>Период</th>
                        <th style='padding: 5px; border: 1px solid black; background-color: #C5E1A5'>Фокус оценивания</th>
                    </tr>";

        foreach ($mailto as $key => $value) {
            switch ($value['focus']) {
                case 'planning':
                    $focus = "Планирование";
                    break;
                case 'teaching':
                    $focus = "Преподавание";
                    break;
                case 'evaluating':
                    $focus = "Оценивание учебных достижений";
                    break;
                case 'complex':
                    $focus = "Комплексный анализ урока";
                    break;
            }
            $recipient = $value['login1']; 
            $subject = "Уведомление системы оценивания уроков"; 
            $message = "<p style='font-size: 18px;'>Вам необходимо посетить урок:</p>";
            $message = $message."<table style='border-collapse: separate; border-spacing: 3px; font-size: 18px;'>
                                    <tr>
                                        <th style='padding: 5px; border: 1px solid black; background-color: #C5E1A5'>Учитель</th>
                                        <th style='padding: 5px; border: 1px solid black; background-color: #C5E1A5'>Период</th>
                                        <th style='padding: 5px; border: 1px solid black; background-color: #C5E1A5'>Фокус оценивания</th>
                                    </tr>
                                    <tr>
                                        <td style='padding: 5px; border: 1px solid black; background-color: #F1F8E9'>".$value['whoWasVisited']."</th>
                                        <td style='padding: 5px; border: 1px solid black; background-color: #F1F8E9'>".date("d.m.Y",strtotime($value['visitDateFrom']))." - ".date("d.m.Y",strtotime($value['visitDateTo']))."</th>
                                        <td style='padding: 5px; border: 1px solid black; background-color: #F1F8E9'>".$focus."</th>
                                    </tr>
                                </table>";
            $message = $message."<br><br>Это письмо сформировано и отправлено автоматически. Отвечать на него не нужно.";
            $headers = "From: Cистема оценивания уроков <is@kst.nis.edu.kz>"."\r\n".
                    "Reply-To: is@kst.nis.edu.kz"."\r\n".
                    "MIME-Version: 1.0"."\r\n".
                    "Content-Type: text/html;";
            mail($recipient, $subject, $message, $headers);

            $table = $table."<tr>
                                <td style='padding: 5px; border: 1px solid black; background-color: #F1F8E9'>".$value['whoVisited']."</th>
                                <td style='padding: 5px; border: 1px solid black; background-color: #F1F8E9'>".date("d.m.Y",strtotime($value['visitDateFrom']))." - ".date("d.m.Y",strtotime($value['visitDateTo']))."</th>
                                <td style='padding: 5px; border: 1px solid black; background-color: #F1F8E9'>".$focus."</th>
                            </tr>";
        }

        $table = $table."</table>";
        $recipient = $mailto[0]['login2']; 
        $subject = "Уведомление системы оценивания уроков"; 
        $message = "<p style='font-size: 18px;'>На ваш урок назначено посещение:</p>".$table;
        $message = $message."<br><br>Это письмо сформировано и отправлено автоматически. Отвечать на него не нужно.";
        $headers = "From: Cистема оценивания уроков <is@kst.nis.edu.kz>"."\r\n".
                    "Reply-To: is@kst.nis.edu.kz"."\r\n".
                    "MIME-Version: 1.0"."\r\n".
                    "Content-Type: text/html;";
        mail($recipient, $subject, $message, $headers);
    }

    public static function getNumberOfAttestationVisits($params)
    {
        $localParams['start1'] = $params['start']; $localParams['end1'] = $params['end'];
        $localParams['start2'] = $params['start']; $localParams['end2'] = $params['end'];
        $localParams['start3'] = $params['start']; $localParams['end3'] = $params['end'];
        $localParams['start4'] = $params['start']; $localParams['end4'] = $params['end'];
        $query = "SELECT 'Всего' status, count(id) number
        FROM isdb.teachers_attestation
        WHERE visitDateFrom >= :start1 AND visitDateTo <= :end1
          AND whoVisited <> '' AND whoWasVisited <> ''
          AND whoVisited IS NOT NULL AND whoWasVisited IS NOT NULL
        UNION
        SELECT 'Запланировано', count(id)
        FROM isdb.teachers_attestation
        WHERE visitDateFrom >= now()
          AND visitDateFrom >= :start2 AND visitDateTo <= :end2
          AND whoVisited <> '' AND whoWasVisited <> ''
          AND whoVisited IS NOT NULL AND whoWasVisited IS NOT NULL
        UNION
        SELECT 'Подтверждено', count(id)
        FROM isdb.teachers_attestation
        WHERE LEFT(confirmations,1) = '1'
          AND visitDateFrom >= :start3 AND visitDateTo <= :end3
          AND whoVisited <> '' AND whoWasVisited <> ''
          AND whoVisited IS NOT NULL AND whoWasVisited IS NOT NULL
        UNION
        SELECT 'В процессе', count(id)
        FROM isdb.teachers_attestation
        WHERE visitDateFrom <= now() AND LEFT(confirmations,1) = '0'
          AND visitDateFrom >= :start4 AND visitDateTo <= :end4
          AND whoVisited <> '' AND whoWasVisited <> ''
          AND whoVisited IS NOT NULL AND whoWasVisited IS NOT NULL;
        ";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$localParams);
        return $data;
    }

    public function getAllAttestationVisits($params)
    {
        $query = "SELECT * FROM isdb.teachers_attestation
                  WHERE visitDateFrom >= :visitPeriodStart AND visitDateTo <= :visitPeriodEnd
                    AND whoVisited <> '' AND whoWasVisited <> ''";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            $data[$i]['visitDate'] = date("d.m.Y", strtotime($data[$i]['visitDateFrom'])).' - '. date("d.m.Y", strtotime($data[$i]['visitDateTo']));
            switch ($data[$i]['focus']) {
                case 'planning':
                    $data[$i]['focus'] = 'Планирование';
                    break;
                case 'teaching':
                    $data[$i]['focus'] = 'Преподавание';
                    break;
                case 'evaluating':
                    $data[$i]['focus'] = 'Оценивание учебных достижений';
                    break;
                case 'complex':
                    $data[$i]['focus'] = 'Комплексный анализ урока';
                    break;
            }
            if (strtotime($data[$i]['visitDateFrom']) <= strtotime(date("d.m.Y"))) {
                if ($data[$i]['evaluates'] != '' && $data[$i]['evaluates'] != '0000000000000000' && $data[$i]['theme'] != '' && $data[$i]['lessonName'] != ''
                 && $data[$i]['grade'] != '' && $data[$i]['lesson_review'] != ''  && $data[$i]['purpose_review'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = 'Ожидает подтверждения';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = 'Подтверждено';
                    } else {
                        $data[$i]['status'] = 'На подтверждении';
                    }
                } else {
                    $data[$i]['status'] = 'На оценивании';
                }
            } else {
                if (strtotime($data[$i]['visitDateFrom']) >= strtotime($params['visitPeriodEnd'])) {
                    $data[$i]['status'] = 'Дата оценивания прошла';
                } else {
                    $data[$i]['status'] = 'Запланировано';
                }
            }
        }
        return $data;
    }

    public function getPersonalAttestationVisits($params)
    {
        $localParam['iin1'] = $this->getTeacherIin($params['teacher']);
        $localParam['iin2'] = $this->getTeacherIin($params['teacher']);
        $localParam['iin3'] = $this->getTeacherIin($params['teacher']);
        $localParam['iin4'] = $this->getTeacherIin($params['teacher']);
        
        if ($params['visitType'] == 'WhoVisited') {
            $query = "SELECT DISTINCT evaluates.who, evaluates.cnt v_cnt, confirmed.cnt c_cnt, planned.cnt p_cnt, in_process.cnt o_cnt
                      FROM (SELECT whoWasVisited who, count(whoWasVisited) cnt
                            FROM isdb.teachers_attestation
                            WHERE iinWhoVisited = :iin1
                            GROUP BY whoWasVisited) evaluates
                      LEFT JOIN (SELECT whoWasVisited who, count(whoWasVisited) cnt
                                 FROM isdb.teachers_attestation
                                 WHERE iinWhoVisited = :iin2 AND LEFT(confirmations,1) = '1'
                                 GROUP BY whoWasVisited) confirmed
                      ON evaluates.who = confirmed.who
                      LEFT JOIN (SELECT whoWasVisited who, count(whoWasVisited) cnt
                                 FROM isdb.teachers_attestation
                                 WHERE iinWhoVisited = :iin3 AND visitDateFrom >= NOW()
                                 GROUP BY whoWasVisited) planned
                      ON evaluates.who = planned.who
                      LEFT JOIN (SELECT  whoWasVisited who, count(whoWasVisited) cnt
                                 FROM isdb.teachers_attestation
                                 WHERE iinWhoVisited = :iin4 AND visitDateFrom <= now() AND LEFT(confirmations,1) = '0'
                                 GROUP BY whoWasVisited) in_process
                      ON evaluates.who = in_process.who;";
        } else {
            $query = "SELECT DISTINCT evaluates.who, evaluates.cnt v_cnt, confirmed.cnt c_cnt, planned.cnt p_cnt, in_process.cnt o_cnt
                      FROM (SELECT whoVisited who, count(whoVisited) cnt
                            FROM isdb.teachers_attestation
                            WHERE iinWhoWasVisited = :iin1
                            GROUP BY whoVisited) evaluates
                      LEFT JOIN (SELECT whoVisited who, count(whoVisited) cnt
                                 FROM isdb.teachers_attestation
                                 WHERE iinWhoWasVisited = :iin2 AND LEFT(confirmations,1) = '1'
                                 GROUP BY whoVisited) confirmed
                      ON evaluates.who = confirmed.who
                      LEFT JOIN (SELECT whoVisited who, count(whoVisited) cnt
                                 FROM isdb.teachers_attestation
                                 WHERE iinWhoWasVisited = :iin3 AND visitDateFrom >= NOW()
                                 GROUP BY whoVisited) planned
                      ON evaluates.who = planned.who
                      LEFT JOIN (SELECT  whoVisited who, count(whoVisited) cnt
                                 FROM isdb.teachers_attestation
                                 WHERE iinWhoWasVisited = :iin4 AND visitDateFrom <= now() AND LEFT(confirmations,1) = '0'
                                 GROUP BY whoVisited) in_process
                      ON evaluates.who = in_process.who;";
        }
        $db = Db::getDb();
        $data = $db->selectQuery($query,$localParam);
        $data = $this->addRowNumbers($data);
        return $data;
    }

    public function getAllAttestationVisitsInPeriod($params) {
        $localParam['iin'] = $this->getTeacherIin($params['teacher']);
        if ($params['visitType'] == 'WhoVisited') {
            $query = "SELECT visitDateFrom, visitDateTo, visitDate, whoWasVisited AS person, focus, evaluates, theme, lessonName, grade, lesson_review, purpose_review, confirmations
                      FROM isdb.teachers_attestation WHERE iinWhoVisited=:iin";
        } else {
            $query = "SELECT visitDateFrom, visitDateTo, visitDate, whoVisited AS person, focus, evaluates, theme, lessonName, grade, lesson_review, purpose_review, confirmations
                      FROM isdb.teachers_attestation WHERE iinWhoWasVisited=:iin";
        }
        $db = Db::getDb();
        $data = $db->selectQuery($query,$localParam);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            $data[$i]['period'] = date("d.m.Y", strtotime($data[$i]['visitDateFrom'])).' - '. date("d.m.Y", strtotime($data[$i]['visitDateTo']));
            switch ($data[$i]['focus']) {
                case 'planning':
                    $data[$i]['focus'] = 'Планирование';
                    break;
                case 'teaching':
                    $data[$i]['focus'] = 'Преподавание';
                    break;
                case 'evaluating':
                    $data[$i]['focus'] = 'Оценивание учебных достижений';
                    break;
                case 'complex':
                    $data[$i]['focus'] = 'Комплексный анализ урока';
                    break;
            }
            if (strtotime($data[$i]['visitDateFrom']) <= strtotime(date("d.m.Y"))) {
                if ($data[$i]['evaluates'] != '' && $data[$i]['evaluates'] != '0000000000000000' && $data[$i]['theme'] != '' && $data[$i]['lessonName'] != ''
                 && $data[$i]['grade'] != '' && $data[$i]['lesson_review'] != ''  && $data[$i]['purpose_review'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = 'Ожидает подтверждения';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = 'Подтверждено';
                    } else {
                        $data[$i]['status'] = 'На подтверждении';
                    }
                } else {
                    $data[$i]['status'] = 'На оценивании';
                }
            } else {
                $data[$i]['status'] = 'Запланировано';
            }
        }
        return $data;
    }

    public function getAllAttestationVisitsInDetails($params) {
        $localParam['iin'] = $this->getTeacherIin($params['teacher']);
        $localParam['visitDate'] = $params['date'];
        if ($params['visitType'] == 'WhoVisited') {
            $query = "SELECT visitDateFrom, visitDateTo, visitDate, whoWasVisited AS person, focus, evaluates, theme, lessonName, grade, lesson_review, purpose_review, confirmations
                      FROM isdb.teachers_attestation WHERE iinWhoVisited=:iin AND visitDateFrom<=:visitDate";
        } else {
            $query = "SELECT visitDateFrom, visitDateTo, visitDate, whoWasVisited AS person, focus, evaluates, theme, lessonName, grade, lesson_review, purpose_review, confirmations
                      FROM isdb.teachers_attestation WHERE iinWhoWasVisited=:iin AND visitDateTo>=:visitDate";
        }
        $db = Db::getDb();
        $data = $db->selectQuery($query,$localParam);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            $data[$i]['period'] = date("d.m.Y", strtotime($data[$i]['visitDateFrom'])).' - '. date("d.m.Y", strtotime($data[$i]['visitDateTo']));
            switch ($data[$i]['focus']) {
                case 'planning':
                    $data[$i]['focus'] = 'Планирование';
                    break;
                case 'teaching':
                    $data[$i]['focus'] = 'Преподавание';
                    break;
                case 'evaluating':
                    $data[$i]['focus'] = 'Оценивание учебных достижений';
                    break;
                case 'complex':
                    $data[$i]['focus'] = 'Комплексный анализ урока';
                    break;
            }
            if (strtotime($data[$i]['visitDateFrom']) <= strtotime(date("d.m.Y"))) {
                if ($data[$i]['evaluates'] != '' && $data[$i]['evaluates'] != '0000000000000000' && $data[$i]['theme'] != '' && $data[$i]['lessonName'] != ''
                 && $data[$i]['grade'] != '' && $data[$i]['lesson_review'] != ''  && $data[$i]['purpose_review'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = 'Ожидает подтверждения';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = 'Подтверждено';
                    } else {
                        $data[$i]['status'] = 'На подтверждении';
                    }
                } else {
                    $data[$i]['status'] = 'На оценивании';
                }
            } else {
                $data[$i]['status'] = 'Запланировано';
            }
        }
        return $data;
    }

    public function getMaxGroupId()
    {
        $query = "SELECT DISTINCT MAX(groupId) AS gId FROM isdb.teachers_attestation;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,[]);
        if (!is_null($data[0]['gId'])) { return $data[0]['gId']+1; } else { return '0'; }
    }

    public function getAttestationVisitResults($post_params)
    {
        $query = "
        SELECT visitDateFrom, visitDateTo, whoVisited, whoWasVisited, (SELECT teacher_purpose FROM teachers_purposes WHERE teacher_iin = iinWhoWasVisited) AS purpose,
               visitDate, grade, lessonName, theme, evaluates, lesson_review, purpose_review, confirmations, focus
        FROM isdb.teachers_attestation
        WHERE id = :rowId
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,['rowId'=>$post_params['rowId']]);

        return $data;
    }

    public function getAttestationCriteriasList($focus)
    {
        $query = "
        SELECT ".$focus." AS criteria, ".substr($focus,0,1)."_rs AS rs, ".substr($focus,0,1)."_markable AS markable
        FROM isdb.teachers_attestation_criterias
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,[]);
        $data = $this->addRowNumbers($data);

        return $data; 
    }

    public function setAttestationVisitResults($params)
    {
        $query = "
        UPDATE isdb.teachers_attestation
        SET visitDate = :visitDate, lessonName = :subject, theme = :topic, grade = :grade, 
            evaluates = :marks, lesson_review = :lesson_review, purpose_review = :purpose_review
        WHERE id = :rowId
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);
        return $data;
    }

    public function getAttestationResultsDump($params)
    { 
        require_once ROOT.'/application/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('Система наблюдения уроков')
        ->setLastModifiedBy('Система наблюдения уроков')
        ->setTitle('Результаты оценивания урока')
        ->setSubject('Результаты оценивания урока')
        ->setDescription('Результаты оценивания урока')
        ->setKeywords('office 2007 openxml php')
        ->setCategory('Отчет');

        // Add data from model
        $arrayData = self::getAttestationVisitResults(['rowId' => $params['rowId']]);
        $arrayCriterias = self::getAttestationCriteriasList($arrayData[0]['focus']);
        
        // Width for cells
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(9);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(16);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(9);

        // Height for cells
        $spreadsheet->getActiveSheet()->getRowDimension(1)->setRowHeight(25);

        // Put headers
        $spreadsheet->getActiveSheet()->mergeCells('A1:H1');
        switch ($arrayData[0]['focus']) {
            case 'planning':
                $spreadsheet->getActiveSheet()->setCellValue('A1', $this->getTexts('ATTESTATION_TABLE_HEADERS', 'planning_caption'));
                break;
            case 'teaching':
                $spreadsheet->getActiveSheet()->setCellValue('A1', $this->getTexts('ATTESTATION_TABLE_HEADERS', 'teaching_caption'));
                break;
            case 'evaluating':
                $spreadsheet->getActiveSheet()->setCellValue('A1', $this->getTexts('ATTESTATION_TABLE_HEADERS', 'evaluating_caption'));
                break;
            case 'complex':
                $spreadsheet->getActiveSheet()->setCellValue('A1', $this->getTexts('ATTESTATION_TABLE_HEADERS', 'complex_caption'));
                break;
        }

        $spreadsheet->getActiveSheet()->mergeCells('A2:B2');
        $spreadsheet->getActiveSheet()->setCellValue('A2', $this->getTexts('TABLE_HEADERS', 'date'));
        $spreadsheet->getActiveSheet()->mergeCells('C2:D2');
        $spreadsheet->getActiveSheet()->setCellValue('C2', $this->getTexts('TABLE_HEADERS', 'grade'));
        $spreadsheet->getActiveSheet()->mergeCells('E2:F2');
        $spreadsheet->getActiveSheet()->setCellValue('E2', $this->getTexts('TABLE_HEADERS', 'subject'));
        $spreadsheet->getActiveSheet()->mergeCells('G2:H2');
        $spreadsheet->getActiveSheet()->setCellValue('G2', $this->getTexts('ATTESTATION_TABLE_HEADERS', 'theme'));
        $spreadsheet->getActiveSheet()->mergeCells('A3:B3');
        $spreadsheet->getActiveSheet()->setCellValue('A3', $arrayData[0]['visitDate']);
        $spreadsheet->getActiveSheet()->mergeCells('C3:D3');
        $spreadsheet->getActiveSheet()->setCellValue('C3', $arrayData[0]['grade']);
        $spreadsheet->getActiveSheet()->mergeCells('E3:F3');
        $spreadsheet->getActiveSheet()->setCellValue('E3', $arrayData[0]['lessonName']);
        $spreadsheet->getActiveSheet()->mergeCells('G3:H3');
        $spreadsheet->getActiveSheet()->setCellValue('G3', $arrayData[0]['theme']);
        $spreadsheet->getActiveSheet()->mergeCells('A4:D4');
        $spreadsheet->getActiveSheet()->setCellValue('A4', $this->getTexts('ATTESTATION_TABLE_HEADERS', 'whoWasVisited'));
        $spreadsheet->getActiveSheet()->mergeCells('E4:H4');
        $spreadsheet->getActiveSheet()->setCellValue('E4', $arrayData[0]['whoWasVisited']);
        $spreadsheet->getActiveSheet()->mergeCells('A5:H5');
        $spreadsheet->getActiveSheet()->setCellValue('A5', $this->getTexts('TABLE_HEADERS', 'purpose'));
        $spreadsheet->getActiveSheet()->mergeCells('A6:H6');
        $spreadsheet->getActiveSheet()->setCellValue('A6', $arrayData[0]['purpose']);
        $l = strlen($arrayData[0]['purpose']) == 0 ? 24 : mb_strlen($arrayData[0]['purpose']);
        $spreadsheet->getActiveSheet()->getRowDimension(6)->setRowHeight($l/24*18.75);
        $spreadsheet->getActiveSheet()->mergeCells('A7:D7');
        $spreadsheet->getActiveSheet()->setCellValue('A7', $this->getTexts('ATTESTATION_TABLE_HEADERS', 'whoVisited'));
        $spreadsheet->getActiveSheet()->mergeCells('E7:H7');
        $spreadsheet->getActiveSheet()->setCellValue('E7', $arrayData[0]['whoVisited']);

        $spreadsheet->getActiveSheet()->setCellValue('A8', '№');
        $spreadsheet->getActiveSheet()->mergeCells('B8:H8');
        switch ($arrayData[0]['focus']) {
            case 'planning':
                $spreadsheet->getActiveSheet()->setCellValue('B8', $this->getTexts('ATTESTATION_TABLE_HEADERS', 'planning_header'));
                break;
            case 'teaching':
                $spreadsheet->getActiveSheet()->setCellValue('B8', $this->getTexts('ATTESTATION_TABLE_HEADERS', 'teaching_header'));
                break;
            case 'evaluating':
                $spreadsheet->getActiveSheet()->setCellValue('B8', $this->getTexts('ATTESTATION_TABLE_HEADERS', 'evaluating_header'));
                break;
            case 'complex':
                $spreadsheet->getActiveSheet()->setCellValue('B8', $this->getTexts('ATTESTATION_TABLE_HEADERS', 'complex_header'));
                break;
        }
        
        // Put data into cells
        $k=0; $j=0; $n=0;
        for ($i = 0; $i < count($arrayCriterias); $i++) {
            if ($arrayCriterias[$i]['criteria'] != '') {
                if (!is_null($arrayCriterias[$i]['rs'])) {
                    $k++;
                    $spreadsheet->getActiveSheet()->mergeCells('A'.($i+9).':A'.($i+9+$arrayCriterias[$i]['rs']-1));
                    $spreadsheet->getActiveSheet()->setCellValue('A'.($i+9), $k);
                }
                if ($arrayCriterias[$i]['markable']) {
                    $spreadsheet->getActiveSheet()->mergeCells('B'.($i+9).':G'.($i+9));
                    $spreadsheet->getActiveSheet()->setCellValue('B'.($i+9), $this->getTexts('ATTESTATION_CRITERIAS', $arrayCriterias[$i]['criteria']));
                    if (substr($arrayData[0]['evaluates'],$j,1) == '1') {
                        $spreadsheet->getActiveSheet()->setCellValue('H'.($i+9), 'chk');
                    }
                    $j++;
                } else {
                    $spreadsheet->getActiveSheet()->mergeCells('B'.($i+9).':H'.($i+9));
                    $spreadsheet->getActiveSheet()->setCellValue('B'.($i+9), $this->getTexts('ATTESTATION_CRITERIAS', $arrayCriterias[$i]['criteria']));
                };
                $n++;
            }
        }

        $spreadsheet->getActiveSheet()->mergeCells('A'.($n+9).':H'.($n+9));
        $spreadsheet->getActiveSheet()->setCellValue('A'.($n+9), $this->getTexts('ATTESTATION_TABLE_HEADERS', 'lesson_feedback'));
        $spreadsheet->getActiveSheet()->mergeCells('A'.($n+10).':H'.($n+10));
        $spreadsheet->getActiveSheet()->setCellValue('A'.($n+10), $arrayData[0]['lesson_review']);
        $l = strlen($arrayData[0]['lesson_review']) == 0 ? 24 : mb_strlen($arrayData[0]['lesson_review']);
        $spreadsheet->getActiveSheet()->getRowDimension($n+10)->setRowHeight($l/24*18.75);
        $spreadsheet->getActiveSheet()->mergeCells('A'.($n+11).':H'.($n+11));
        $spreadsheet->getActiveSheet()->setCellValue('A'.($n+11), $this->getTexts('TABLE_HEADERS', 'purpose_feedback'));
        $l = strlen($this->getTexts('TABLE_HEADERS', 'purpose_feedback')) == 0 ? 24 : mb_strlen($this->getTexts('TABLE_HEADERS', 'purpose_feedback'));
        $spreadsheet->getActiveSheet()->getRowDimension($n+11)->setRowHeight($l/24*18.75);
        $spreadsheet->getActiveSheet()->mergeCells('A'.($n+12).':H'.($n+12));
        $spreadsheet->getActiveSheet()->setCellValue('A'.($n+12), $arrayData[0]['purpose_review']);
        $l = strlen($arrayData[0]['purpose_review']) == 0 ? 24 : mb_strlen($arrayData[0]['purpose_review']);
        $spreadsheet->getActiveSheet()->getRowDimension($n+12)->setRowHeight($l/24*18.75);

        // Rename worksheet
        $spreadsheet->getActiveSheet()->setTitle('Результат оценки урока');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Результат оценки урока.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        $styleHeaders = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'bold' => true,
                'size' => 14
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $styleHeaderData = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'font' => [
                'size' => 14
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $styleData = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'font' => [
                'size' => 14
            ],
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $styleNumbers = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
            ],
        ];

        $spreadsheet->getActiveSheet()->getStyle('A1:D28')->getAlignment()->setWrapText(true);

        $spreadsheet->getActiveSheet()->getStyle('C9:C24')->applyFromArray($styleNumbers);

        $spreadsheet->getActiveSheet()->getStyle('A1:H2')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A4:D4')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A5:H5')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A7:D7')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A8:H8')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A9:A'.($n+8))->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A9:A'.($n+8))->applyFromArray($styleNumbers);
        $spreadsheet->getActiveSheet()->getStyle('A'.($n+9).':H'.($n+9))->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A'.($n+11).':H'.($n+11))->applyFromArray($styleHeaders);
        
        $spreadsheet->getActiveSheet()->getStyle('A3:D3')->applyFromArray($styleHeaderData);
        $spreadsheet->getActiveSheet()->getStyle('A6:H6')->applyFromArray($styleHeaderData);
        $spreadsheet->getActiveSheet()->getStyle('E3:H3')->applyFromArray($styleHeaderData);
        $spreadsheet->getActiveSheet()->getStyle('E4:H4')->applyFromArray($styleHeaderData);
        $spreadsheet->getActiveSheet()->getStyle('E7:H7')->applyFromArray($styleHeaderData);

        $spreadsheet->getActiveSheet()->getStyle('B9:H'.(count($arrayCriterias)-2))->applyFromArray($styleData);
        $spreadsheet->getActiveSheet()->getStyle('A'.($n+10).':H'.($n+10))->applyFromArray($styleData);
        $spreadsheet->getActiveSheet()->getStyle('A'.($n+12).':H'.($n+12))->applyFromArray($styleData);
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function getVisitCount($param)
    {
        $params['iin'] =$this->getTeacherIin($param['person']);
        $params['date'] = $param['date'];
        $query = "
        SELECT COUNT(*) AS count FROM isdb.evaluationTeachers
        WHERE iinWhoWasVisited = :iin
          AND visitDate = :date
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);

        echo $data[0]['count'];
    }

    public function getLSOTable($param) {
        if (!empty($param)) {
            if ($param['person'] == '' && $param['period'] == '') { $where = ''; } else {
                $where = "WHERE ";
                if ($param['person'] != '') { $where =  $where."a.whoWasVisited = :person"; } else { unset($param['person']); }
                if ($param['period'] != '') {
                    if (array_key_exists('person', $param)) { if ($param['person'] != '') { $where =  $where." AND "; }; };
                    $where =  $where."b.period = :period"; 
                } else {
                    unset($param['period']);
                }
            }
        } else {
            $where = '';
        };
        $query="SELECT MIN(a.groupId) AS id, 'lso' AS focus, b.period, MIN(a.visitDateFrom) AS dateFrom, MAX(a.visitDateTo) AS dateTo, a.whoWasVisited,
                    (SELECT whoVisited FROM isdb.teachers_attestation WHERE focus = 'planning' AND groupId = a.groupId) AS planning,
                    (SELECT whoVisited FROM isdb.teachers_attestation WHERE focus = 'teaching' AND groupId = a.groupId) AS teaching,
                    (SELECT whoVisited FROM isdb.teachers_attestation WHERE focus = 'evaluating' AND groupId = a.groupId) AS evaluating,
                    (SELECT whoVisited FROM isdb.teachers_attestation WHERE focus = 'complex' AND groupId = a.groupId) AS complex
                FROM isdb.teachers_attestation a
                LEFT JOIN (SELECT a.groupId,
                                  (CASE WHEN MIN(a.visitDateFrom)>=(SELECT dateTimeValue AS dateTimeValue FROM isdb.info WHERE id = 4) AND MAX(a.visitDateTo)<=(SELECT dateTimeValue AS dateTimeValue FROM isdb.info WHERE id = 5) THEN 1
                                        WHEN MIN(a.visitDateFrom)>=(SELECT dateTimeValue AS dateTimeValue FROM isdb.info WHERE id = 6) AND MAX(a.visitDateTo)<=(SELECT dateTimeValue AS dateTimeValue FROM isdb.info WHERE id = 7) THEN 2
                                        ELSE 0
                                   END) AS period
                            FROM isdb.teachers_attestation a
                            GROUP BY a.whoWasVisited, a.groupId
                            ORDER BY a.groupId DESC) b ON a.groupId = b.groupId
                ".$where."
                GROUP BY a.whoWasVisited, a.groupId, b.period
                ORDER BY a.groupId DESC;";
        //return $query;
        $db = Db::getDb();
        $data = $db->selectQuery($query,$param);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            $data[$i]['class'] = 'allowed';
            $data[$i]['result'] = '<button name="saveToPDF" class="visitBut">Выгрузить</button>';
            if ($data[$i]['period'] == 1) {
                $data[$i]['half_year'] = 'I полугодие';
            } else if ($data[$i]['period'] == 2) {
                $data[$i]['half_year'] = 'II полугодие';
            } else if ($data[$i]['period'] == 0) {
                $data[$i]['half_year'] = 'Не указаны периоды полугодии.';
            }
        }
        return $data;
    }

    public function getLSOResults($param) {
        $query = "SELECT a.whoWasVisited,
                    (SELECT lesson_review FROM isdb.teachers_attestation WHERE focus = 'planning' AND groupId = a.groupId) AS planning_lesson_review,
                    (SELECT lesson_review FROM isdb.teachers_attestation WHERE focus = 'teaching' AND groupId = a.groupId) AS teaching_lesson_review,
                    (SELECT lesson_review FROM isdb.teachers_attestation WHERE focus = 'evaluating' AND groupId = a.groupId) AS evaluating_lesson_review,
                    (SELECT lesson_review FROM isdb.teachers_attestation WHERE focus = 'complex' AND groupId = a.groupId) AS complex_lesson_review,
                    (SELECT teacher_purpose FROM isdb.teachers_purposes WHERE teacher_iin = a.iinWhoWasVisited) AS purpose,
                    (SELECT teachers_level FROM isdb.teachers_purposes WHERE teacher_iin = a.iinWhoWasVisited) AS cur_level,
                    (SELECT teachers_level_up FROM isdb.teachers_purposes WHERE teacher_iin = a.iinWhoWasVisited) AS up_level,
                    (SELECT position FROM isdb.teachers_lso WHERE id = a.groupId) AS position,
                    (SELECT first_recommendation FROM isdb.teachers_lso WHERE id = a.groupId) AS first_recommendation,
                    (SELECT first_correction FROM isdb.teachers_lso WHERE id = a.groupId) AS first_correction,
                    (SELECT second_recommendation FROM isdb.teachers_lso WHERE id = a.groupId) AS second_recommendation,
                    (SELECT second_correction FROM isdb.teachers_lso WHERE id = a.groupId) AS second_correction,
                    (SELECT second_comment FROM isdb.teachers_lso WHERE id = a.groupId) AS second_comment,
                    (SELECT all_recommendation FROM isdb.teachers_lso WHERE id = a.groupId) AS all_recommendation,
                    (SELECT q1 FROM isdb.teachers_lso WHERE id = a.groupId) AS q1, (SELECT q2 FROM isdb.teachers_lso WHERE id = a.groupId) AS q2, (SELECT q3 FROM isdb.teachers_lso WHERE id = a.groupId) AS q3,
                    (SELECT q4 FROM isdb.teachers_lso WHERE id = a.groupId) AS q4, (SELECT q5 FROM isdb.teachers_lso WHERE id = a.groupId) AS q5, (SELECT q6 FROM isdb.teachers_lso WHERE id = a.groupId) AS q6,
                    (SELECT q7 FROM isdb.teachers_lso WHERE id = a.groupId) AS q7, (SELECT q8 FROM isdb.teachers_lso WHERE id = a.groupId) AS q8, (SELECT q9 FROM isdb.teachers_lso WHERE id = a.groupId) AS q9,
                    (SELECT q10 FROM isdb.teachers_lso WHERE id = a.groupId) AS q10, (SELECT q11 FROM isdb.teachers_lso WHERE id = a.groupId) AS q11, (SELECT q12 FROM isdb.teachers_lso WHERE id = a.groupId) AS q12
                FROM isdb.teachers_attestation a
                WHERE a.groupId = :rowId
                GROUP BY a.whoWasVisited, a.groupId, purpose, cur_level, up_level
                ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,['rowId'=>$param['rowId']]);
        $data = $this->addRowNumbers($data);
        for ($i=0; $i<count($data); $i++) {
            if ($param['period'] == 1) {
                $data[$i]['half_year'] = 'first_half_year';
            } else if ($param['period'] == 2) {
                $data[$i]['half_year'] = 'second_half_year';
            }
        }
        return $data;
    }

    public function saveLSO($param) {
        $localParam['rowId'] = $param['rowId'];
        $localParam['job'] = $param['job'];
        if ($param['period'] == 1) {
            $values = 'first_recommendation = :summary, first_correction = :correction';
            $localParam['summary'] =  $param['summary'];
            $localParam['correction'] =  $param['correction'];
        } else if ($param['period'] == 2) {
            $values = 'second_recommendation = :summary, second_correction = :correction, second_comment = :comment, all_recommendation = :recommendation, ';
            $values = $values.'q1 = :q1, q2 = :q2, q3 = :q3, q4 = :q4, q5 = :q5, q6 = :q6, q7 = :q7, q8 = :q8, q9 = :q9, q10 = :q10, q11 = :q11, q12 = :q12';
            $localParam['summary'] =  $param['summary'];
            $localParam['correction'] =  $param['correction'];
            $localParam['comment'] =  $param['comment'];
            $localParam['recommendation'] =  $param['recommendation'];
            $localParam['q1'] = $param['q1']; $localParam['q2'] = $param['q2']; $localParam['q3'] = $param['q3'];
            $localParam['q4'] = $param['q4']; $localParam['q5'] = $param['q5']; $localParam['q6'] = $param['q6'];
            $localParam['q7'] = $param['q7']; $localParam['q8'] = $param['q8']; $localParam['q9'] = $param['q9'];
            $localParam['q10'] = $param['q10']; $localParam['q11'] = $param['q11']; $localParam['q12'] = $param['q12'];
        }
        $query = "UPDATE isdb.teachers_lso
                  SET position = :job, ".$values."
                  WHERE id = :rowId;";
        //print_r($localParam); exit;
        $db = Db::getDb();
        $data = $db->selectQuery($query,$localParam);
    }

    public function getLSODumps($params, $mode) {
        require_once ROOT.'/application/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('Система наблюдения уроков')
        ->setLastModifiedBy('Система наблюдения уроков')
        ->setTitle('Результаты оценивания урока')
        ->setSubject('Результаты оценивания урока')
        ->setDescription('Результаты оценивания урока')
        ->setKeywords('office 2007 openxml php')
        ->setCategory('Отчет');

        // Add data from model
        $arrayData = self::getLSOResults(['rowId'=>$params['rowId'], 'period'=>$params['mode']]);
        
        // Width for cells
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(3);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setWidth(27);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setWidth(21);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(21);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setWidth(21);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setWidth(21);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setWidth(21);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setWidth(3);

        // Height for cells
        $spreadsheet->getActiveSheet()->getRowDimension(1)->setRowHeight(25);
        $spreadsheet->getActiveSheet()->getRowDimension(8)->setRowHeight(30);
        $spreadsheet->getActiveSheet()->getRowDimension(10)->setRowHeight(70);
        $spreadsheet->getActiveSheet()->getRowDimension(11)->setRowHeight(10);
        $spreadsheet->getActiveSheet()->getRowDimension(12)->setRowHeight(70);
        $spreadsheet->getActiveSheet()->getRowDimension(13)->setRowHeight(10);
        $spreadsheet->getActiveSheet()->getRowDimension(14)->setRowHeight(70);
        $spreadsheet->getActiveSheet()->getRowDimension(15)->setRowHeight(10);
        $spreadsheet->getActiveSheet()->getRowDimension(16)->setRowHeight(70);
        $spreadsheet->getActiveSheet()->getRowDimension(17)->setRowHeight(10);
        $spreadsheet->getActiveSheet()->getRowDimension(19)->setRowHeight(10);
        if ($mode == 'lso1') {
            $spreadsheet->getActiveSheet()->getRowDimension(18)->setRowHeight(70);
            $spreadsheet->getActiveSheet()->getRowDimension(20)->setRowHeight(70);
            $spreadsheet->getActiveSheet()->getRowDimension(21)->setRowHeight(10);
        } else {
            $spreadsheet->getActiveSheet()->getRowDimension(18)->setRowHeight(100);
            $spreadsheet->getActiveSheet()->getRowDimension(20)->setRowHeight(15);
            $spreadsheet->getActiveSheet()->getRowDimension(21)->setRowHeight(40);
            $spreadsheet->getActiveSheet()->getRowDimension(24)->setRowHeight(5);
            $spreadsheet->getActiveSheet()->getRowDimension(33)->setRowHeight(5);
            $spreadsheet->getActiveSheet()->getRowDimension(42)->setRowHeight(5);
            $spreadsheet->getActiveSheet()->getRowDimension(50)->setRowHeight(120);
            $spreadsheet->getActiveSheet()->getRowDimension(52)->setRowHeight(70);
        }

        // Merge cells
        $spreadsheet->getActiveSheet()->mergeCells('A1:H1');
        $spreadsheet->getActiveSheet()->mergeCells('A7:H7');
        $spreadsheet->getActiveSheet()->mergeCells('A9:H9');
        $spreadsheet->getActiveSheet()->mergeCells('B8:G8');
        $spreadsheet->getActiveSheet()->mergeCells('B10:G10');
        $spreadsheet->getActiveSheet()->mergeCells('B12:G12');
        $spreadsheet->getActiveSheet()->mergeCells('B14:G14');
        $spreadsheet->getActiveSheet()->mergeCells('B16:G16');
        $spreadsheet->getActiveSheet()->mergeCells('B18:G18');
        if ($mode == 'lso1') {
            $spreadsheet->getActiveSheet()->mergeCells('B20:G20');
        } else {
            $spreadsheet->getActiveSheet()->mergeCells('B21:G21');
            $spreadsheet->getActiveSheet()->mergeCells('B23:G23');
            $spreadsheet->getActiveSheet()->mergeCells('B32:G32');
            $spreadsheet->getActiveSheet()->mergeCells('B41:G41');
            $spreadsheet->getActiveSheet()->mergeCells('B50:G50');
            $spreadsheet->getActiveSheet()->mergeCells('B52:G52');
            $spreadsheet->getActiveSheet()->mergeCells('C30:G30');
            $spreadsheet->getActiveSheet()->mergeCells('C39:G39');
            $spreadsheet->getActiveSheet()->mergeCells('C48:G48');
        }
        $spreadsheet->getActiveSheet()->mergeCells('B2:C2');
        $spreadsheet->getActiveSheet()->mergeCells('B3:C3');
        $spreadsheet->getActiveSheet()->mergeCells('B4:C4');
        $spreadsheet->getActiveSheet()->mergeCells('B5:C5');
        $spreadsheet->getActiveSheet()->mergeCells('D2:G2');
        $spreadsheet->getActiveSheet()->mergeCells('D3:G3');
        $spreadsheet->getActiveSheet()->mergeCells('D4:G4');
        $spreadsheet->getActiveSheet()->mergeCells('D5:G5');

        // Put headers
        $spreadsheet->getActiveSheet()->setCellValue('A1', $this->getTexts('LSO_TABLE_HEADER', 'lso'));
        $spreadsheet->getActiveSheet()->setCellValue('B2', $this->getTexts('LSO_TABLE_HEADER', 'fio'));
        $spreadsheet->getActiveSheet()->setCellValue('B3', $this->getTexts('LSO_TABLE_HEADER', 'job_info'));
        $spreadsheet->getActiveSheet()->setCellValue('B4', $this->getTexts('LSO_TABLE_HEADER', 'cur_level'));
        $spreadsheet->getActiveSheet()->setCellValue('B5', $this->getTexts('LSO_TABLE_HEADER', 'up_level'));

        $today = getdate();
        $years = $today['mon'] > 5 ? ($today['year'].' - '.($today['year']+1)) : (($today['year']-1).' - '.$today['year']);
        $text = str_replace('*year*', $years, $this->getTexts('LSO_TABLE_HEADER', 'purpose'));
        $spreadsheet->getActiveSheet()->setCellValue('A7', $text);

        if ($params['mode'] == 1) {
            $spreadsheet->getActiveSheet()->setCellValue('A9', $this->getTexts('LSO_TABLE_HEADER', 'first_half_year'));
        } else if ($params['mode'] == 2) {
            $spreadsheet->getActiveSheet()->setCellValue('A9', $this->getTexts('LSO_TABLE_HEADER', 'second_half_year'));
        }
        
        // Put data into cells
        $spreadsheet->getActiveSheet()->setCellValue('D2', $arrayData[0]['whoWasVisited']);
        $spreadsheet->getActiveSheet()->setCellValue('D3', $arrayData[0]['position']);
        $spreadsheet->getActiveSheet()->setCellValue('D4', $this->getTexts('TEACHERS_LEVELS', $arrayData[0]['cur_level']));
        $spreadsheet->getActiveSheet()->setCellValue('D5', $this->getTexts('TEACHERS_LEVELS', $arrayData[0]['up_level']));
        $spreadsheet->getActiveSheet()->setCellValue('B8', $arrayData[0]['purpose']);
        $spreadsheet->getActiveSheet()->setCellValue('B10', $arrayData[0]['planning_lesson_review']);
        $spreadsheet->getActiveSheet()->setCellValue('B12', $arrayData[0]['teaching_lesson_review']);
        $spreadsheet->getActiveSheet()->setCellValue('B14', $arrayData[0]['evaluating_lesson_review']);
        $spreadsheet->getActiveSheet()->setCellValue('B16', $arrayData[0]['complex_lesson_review']);
        if ($mode == 'lso1') {
            $spreadsheet->getActiveSheet()->setCellValue('B18', $arrayData[0]['first_recommendation']);
            $spreadsheet->getActiveSheet()->setCellValue('B20', $arrayData[0]['first_correction']);
        } else {
            $spreadsheet->getActiveSheet()->setCellValue('B18', $arrayData[0]['second_recommendation']);
            $spreadsheet->getActiveSheet()->setCellValue('B21', $arrayData[0]['second_comment']);
            $spreadsheet->getActiveSheet()->setCellValue('B50', $arrayData[0]['second_correction']);
            $spreadsheet->getActiveSheet()->setCellValue('B52', $arrayData[0]['all_recommendation']);
            for ($i=0; $i < 3; $i++) {
                $spreadsheet->getActiveSheet()->setCellValue('B'.(23+$i*9), $this->getTexts('LSO_QUESTIONS', 'caption'.($i+1)));
                for ($j=1; $j <= 5; $j++) {
                    $spreadsheet->getActiveSheet()->setCellValue(chr(66+$j).(25+$i*9), $this->getTexts('LSO_QUESTIONS', 'q'.($j+($i*5))));
                }
                for ($k=1; $k <= 4; $k++) {
                    $spreadsheet->getActiveSheet()->setCellValue('B'.((25+$i*9)+$k), $this->getTexts('LSO_QUESTIONS', 'ans'.$k));
                    $cents = $arrayData[0]['q'.($i*4+$k)] != '' ? explode('|', $arrayData[0]['q'.($i*4+$k)]) : [];
                    for ($j=1; $j <= 5; $j++) {
                        if (!empty($cents)) { $spreadsheet->getActiveSheet()->setCellValue(chr(66+$j).((25+$i*9)+$k), $cents[$j-1]); }
                    }
                }
                $spreadsheet->getActiveSheet()->setCellValue('B'.(30+($i*9)), $this->getTexts('LSO_QUESTIONS', 'cnt'));
                if (!empty($cents)) { $spreadsheet->getActiveSheet()->setCellValue('C'.(30+$i*9), $cents[5]); }
            }
            $spreadsheet->getActiveSheet()->setCellValue('C5', $this->getTexts('TEACHERS_LEVELS', $arrayData[0]['up_level']));
        }

        $styleHeaders = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                         'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
                         'font' => ['bold' => true, 'size' => 14],];
        $styleAllBorders = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],];
        $styleAllBordersWhiteBackground = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                                           'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
                                           'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],
                                           'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor' => ['rgb' => 'FFFFFF']]];
        $styleAllBordersWhiteBackgroundLeft = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                               'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
                                               'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],
                                               'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor' => ['rgb' => 'FFFFFF']]];
        $styleOutBordersColorfulBackground = ['borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],
                                              'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                                         'startColor' => ['rgb' => 'CCEEFF']]];
        $styleOutBordersWhiteBackground = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                                                           'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,],
                                           'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],
                                           'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor' => ['rgb' => 'FFFFFF']]];
        $styleOutBordersWhiteBackgroundCenter = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                                           'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
                                           'borders' => ['outline' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],
                                           'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,'startColor' => ['rgb' => 'FFFFFF']]];

        $spreadsheet->getActiveSheet()->getStyle('A1:H53')->getAlignment()->setWrapText(true);

        $spreadsheet->getActiveSheet()->getStyle('A1:H1')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A7:H7')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A9:H9')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('B2:G5')->applyFromArray($styleAllBorders);
        if ($mode == 'lso1') {
            $spreadsheet->getActiveSheet()->getStyle('A7:H21')->applyFromArray($styleOutBordersColorfulBackground);
            $spreadsheet->getActiveSheet()->getStyle('B20:G20')->applyFromArray($styleOutBordersWhiteBackground);
        } else {
            $spreadsheet->getActiveSheet()->getStyle('A7:H19')->applyFromArray($styleOutBordersColorfulBackground);
            $spreadsheet->getActiveSheet()->getStyle('A20:H53')->applyFromArray($styleOutBordersColorfulBackground);
            $spreadsheet->getActiveSheet()->getStyle('B21:G21')->applyFromArray($styleOutBordersWhiteBackground);
            $spreadsheet->getActiveSheet()->getStyle('B23:G23')->applyFromArray($styleOutBordersWhiteBackgroundCenter);
            $spreadsheet->getActiveSheet()->getStyle('B32:G32')->applyFromArray($styleOutBordersWhiteBackgroundCenter);
            $spreadsheet->getActiveSheet()->getStyle('B41:G41')->applyFromArray($styleOutBordersWhiteBackgroundCenter);
            $spreadsheet->getActiveSheet()->getStyle('B50:G50')->applyFromArray($styleOutBordersWhiteBackground);
            $spreadsheet->getActiveSheet()->getStyle('B52:G52')->applyFromArray($styleOutBordersWhiteBackground);
            $spreadsheet->getActiveSheet()->getStyle('C25:G29')->applyFromArray($styleAllBordersWhiteBackground);
            $spreadsheet->getActiveSheet()->getStyle('B26:B30')->applyFromArray($styleAllBordersWhiteBackgroundLeft);
            $spreadsheet->getActiveSheet()->getStyle('C34:G38')->applyFromArray($styleAllBordersWhiteBackground);
            $spreadsheet->getActiveSheet()->getStyle('B35:B39')->applyFromArray($styleAllBordersWhiteBackgroundLeft);
            $spreadsheet->getActiveSheet()->getStyle('C43:G47')->applyFromArray($styleAllBordersWhiteBackground);
            $spreadsheet->getActiveSheet()->getStyle('B44:B48')->applyFromArray($styleAllBordersWhiteBackgroundLeft);
            $spreadsheet->getActiveSheet()->getStyle('C30:G30')->applyFromArray($styleAllBordersWhiteBackgroundLeft);
            $spreadsheet->getActiveSheet()->getStyle('C39:G39')->applyFromArray($styleAllBordersWhiteBackgroundLeft);
            $spreadsheet->getActiveSheet()->getStyle('C48:G48')->applyFromArray($styleAllBordersWhiteBackgroundLeft);
        }
        $spreadsheet->getActiveSheet()->getStyle('B8:G8')->applyFromArray($styleOutBordersWhiteBackground);
        $spreadsheet->getActiveSheet()->getStyle('B10:G10')->applyFromArray($styleOutBordersWhiteBackground);
        $spreadsheet->getActiveSheet()->getStyle('B12:G12')->applyFromArray($styleOutBordersWhiteBackground);
        $spreadsheet->getActiveSheet()->getStyle('B14:G14')->applyFromArray($styleOutBordersWhiteBackground);
        $spreadsheet->getActiveSheet()->getStyle('B16:G16')->applyFromArray($styleOutBordersWhiteBackground);
        $spreadsheet->getActiveSheet()->getStyle('B18:G18')->applyFromArray($styleOutBordersWhiteBackground);
        

        // Rename worksheet
        $spreadsheet->getActiveSheet()->setTitle('Результат оценки урока');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Результат оценки урока.xlsx"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public function getAllSavedTeachers()
    {
        $query = "SELECT id AS oid, teacher_fio AS item FROM isdb.teachers_purposes ORDER BY teacher_fio;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,[]);
        return $data;
    }

    public function getAllTeachersWithSynod()
    {
        $query = "SELECT max(groupId) AS oid, whoWasVisited AS item FROM isdb.teachers_attestation GROUP BY whoWasVisited ORDER BY whoWasVisited;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,[]);
        return $data;
    }

    public function setHalfYearPeriods($params)
    {
        if ($params['period'] == 1) {
            $query = "UPDATE isdb.info SET dateTimeValue = :fpStart WHERE id = 4;";
            $db = Db::getDb();
            $data = $db->selectQuery($query,['fpStart' => $params['firstPeriodStart']]);
            $query = "UPDATE isdb.info SET dateTimeValue = concat(:fpEnd, ' 23:59:59') WHERE id = 5;";
            $db = Db::getDb();
            $data = $db->selectQuery($query,['fpEnd' => $params['firstPeriodEnd']]);
        }
        if ($params['period'] == 2) {
            $query = "UPDATE isdb.info SET dateTimeValue = :spStart WHERE id = 6;";
            $db = Db::getDb();
            $data = $db->selectQuery($query,['spStart' => $params['secondPeriodStart']]);
            $query = "UPDATE isdb.info SET dateTimeValue = concat(:spEnd, ' 23:59:59') WHERE id = 7;";
            $db = Db::getDb();
            $data = $db->selectQuery($query,['spEnd' => $params['secondPeriodEnd']]);
        }
    }

    public function getHalfYearPeriods()
    {
        $query = "SELECT DATE_FORMAT(dateTimeValue, '%Y-%m-%d') AS dateTimeValue FROM isdb.info WHERE id BETWEEN 4 AND 7 ORDER BY id;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,[]);
        return $data[0]['dateTimeValue'].';'.$data[1]['dateTimeValue'].'|'.$data[2]['dateTimeValue'].';'.$data[3]['dateTimeValue'];
    }
}