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

    public static function checkUser($user)
    {
        $ad = Ad::getStaffListFromAD("teacher");

        foreach ($ad as $value) {
            if (in_array($user,$value)) {
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
        $ini_params = parse_ini_file('/home/developer/Code/PHP/is/public/texts/'.$_COOKIE["lang"].'-lang.ini',true);
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
        if ($_POST['mode'] == 'standart') {
            $this->getStandartResultsDump($_POST);
        } else {
            $this->getAttestationResultsDump($_POST);
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
                if ($data[$i]['evaluates'] != '' && $data[$i]['evaluates'] != '0000000000000000' && $data[$i]['theme'] != '' && $data[$i]['lessonName'] != '' && $data[$i]['grade'] != '' && $data[$i]['recommendation'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = '<i class="status">Ожидает подтверждения</i>';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = '<i class="status">Подтверждено</i>';
                    } else {
                        $data[$i]['status'] = '<i class="status">На подтверждении</i>';
                    }
                } else {
                    $data[$i]['status'] = '<i class="status">На оценивании</i>';
                }
            } else {
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                $data[$i]['class'] = 'planned';
                $data[$i]['status'] = '<i class="status">Запланировано</i>';
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
                if (($data[$i]['evaluates'] != '' && $data[$i]['evaluates'] != '0000000000000000')|| $data[$i]['theme'] != '' || $data[$i]['lessonName'] != '' || $data[$i]['grade'] != '') {
                    $data[$i]['result'] = '<button name="saveToPDF" class="visitBut">Выгрузить</button>';
                    $data[$i]['class'] = 'allowed';
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = '<i class="status">Ожидает подтверждения</i>';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = '<i class="status">Подтверждено</i>';
                    } else {
                        $data[$i]['status'] = '<i class="status">На подтверждении</i>';
                    }
                } else {
                    $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                    $data[$i]['class'] = 'planned';
                    $data[$i]['status'] = '<i class="status">Нет оценок</i>';
                }
            } else {
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                $data[$i]['class'] = 'planned';
                $data[$i]['status'] = '<i class="status">Запланировано</i>';
            }
            $data[$i]['visitDate'] = date("d.m.Y", strtotime($data[$i]['visitDate']));
        }
        return $data;
    }

    public function getDefaultCriteriasList()
    {
        $query = "
        SELECT adt1.discription d1,adt2.discription d2,IFNULL(adt1.rowspan,1) rs
        FROM isdb.evaluationCriterias
        LEFT JOIN isdb.additionalTableForEvaluation adt1
        ON LEFT(r_names,1) = adt1.uid
        LEFT JOIN isdb.additionalTableForEvaluation adt2
        ON RIGHT(r_names,1) = adt2.uid
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,[]);
        $data = $this->addRowNumbers($data);

        return $data;
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
        $db->selectQuery($query,$params);
    }

    public function getVisitResults($post_params)
    {
        $query = "
        SELECT visitDate, whoVisited, whoWasVisited, (SELECT teacher_purpose FROM teachers_purposes WHERE teacher_iin = iinWhoWasVisited) AS purpose,
               grade, lessonName, theme, evaluates, recommendation, purpose_review, confirmations
        FROM isdb.evaluationTeachers
        WHERE id = :rowId
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,['rowId'=>$post_params['rowId']]);

        return $data;
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

            if ($data[0]['evaluates'] != '0000000000000000' && !is_null($data[0]['evaluates']) && $data[0]['lessonName'] != '' && $data[0]['theme'] != '' && $data[0]['grade'] != '' && $data[0]['recommendation'] != '' && $data[0]['purpose_review'] != '') {
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

            if (!is_null($data[0]['evaluates']) && $evaluates != 'null' && ($data[0]['lessonName'] != '' && $data[0]['theme'] != '' && $data[0]['grade'] != '' && $data[0]['lesson_review'] != '' && $data[0]['purpose_review'] != '')) {
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
        SELECT subjects FROM isdb.additionalTableForEvaluation
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
        UNION
        SELECT 'Запланировано', count(id)
        FROM isdb.evaluationTeachers
        WHERE visitDate >= now()
          AND visitDate >= :start2 AND visitDate <= :end2
        UNION
        SELECT 'Подтверждено', count(id)
        FROM isdb.evaluationTeachers
        WHERE LEFT(confirmations,1) = '1'
          AND visitDate >= :start1 AND visitDate <= :end1
        UNION
        SELECT 'В процессе', count(id)
        FROM isdb.evaluationTeachers
        WHERE visitDate <= now() AND LEFT(confirmations,1) = '0'
          AND visitDate >= :start3 AND visitDate <= :end3;
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

    public function getStandartResultsDump($params)
    {
        require_once ROOT.'/application/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('Система оценивания уроков')
        ->setLastModifiedBy('Система оценивания уроков')
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
        $spreadsheet->getProperties()->setCreator('Система оценивания уроков')
        ->setLastModifiedBy('Система оценивания уроков')
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
        $styleTopHeaders = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
                            'font' => ['bold' => true,
                                       'size' => 12],
                            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],
                        ];
        $styleLeftHeaders = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
                             'font' => ['bold' => true,
                                        'size' => 12],
                             'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],
                        ];
        $styleData = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],
                      'font' => ['size' => 12],
                      'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                      'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
                     ];

        // Column width
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(40);

        // Put headers
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Индивидуальный отчет по критериям: '.$params['whoWasVisited']);
        
        // Put data into cells
        foreach ($arrayDefCriterias as $criteria) {
            $i = $criteria['num']+2;
            $spreadsheet->getActiveSheet()->setCellValue('A'.$i, $this->getTexts('CRITERIAS', $criteria['d2']));
            $spreadsheet->getActiveSheet()->getRowDimension($i)->setRowHeight(-1);
        }
        for ($k = 0; $k <= 15; $k++) { $averages[$k] = 0; };
        $i = 66; //"B" letter's number in ASCII table
        foreach ($arrayData as $data) {
            $spreadsheet->getActiveSheet()->getColumnDimension(chr($i))->setWidth(21);
            $spreadsheet->getActiveSheet()->setCellValue(chr($i).'2', $data['whoVisited'].chr(10).'('.$data['visitDate'].')');
            $spreadsheet->getActiveSheet()->getStyle(chr($i).'2')->applyFromArray($styleTopHeaders);
            for ($j = 0; $j <= 15; $j++) {
                $spreadsheet->getActiveSheet()->setCellValue(chr($i).($j+3), substr($data['evaluates'],$j,1));
                $averages[$j] += (int)substr($data['evaluates'],$j,1);
            }
            $i++;
        }
        $spreadsheet->getActiveSheet()->setCellValue(chr($i).'2', 'Средний балл');
        $spreadsheet->getActiveSheet()->getStyle(chr($i).'2')->applyFromArray($styleTopHeaders);
        for ($j = 0; $j <= 15; $j++) {
            $spreadsheet->getActiveSheet()->setCellValue(chr($i).($j+3), number_format($averages[$j]/$colCount,1,',',''));
            $spreadsheet->getActiveSheet()->getStyle(chr($i).($j+3))->applyFromArray($styleData);
            $spreadsheet->getActiveSheet()->getColumnDimension(chr($i))->setWidth(21);
        }

        $spreadsheet->getActiveSheet()->getStyle('A2:'.chr($colCount+66).'18')->getAlignment()->setWrapText(true);

        $spreadsheet->getActiveSheet()->getStyle('A1')->applyFromArray(['font'=>['bold'=>true,'size'=>14]]);
        $spreadsheet->getActiveSheet()->getStyle('A3:A18')->applyFromArray($styleLeftHeaders);
        $spreadsheet->getActiveSheet()->getStyle('B3:'.chr($colCount+65).'18')->applyFromArray($styleData);

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

    public static function getDataForReport2()
    {
        $query = "SELECT evaluates, lessonName FROM isdb.evaluationTeachers
                  WHERE evaluates IS NOT null
                    AND evaluates <> '0000000000000000'
                    AND lessonName <> ''
                  ORDER BY lessonName;
                 ";
        $db = Db::getDb();
        $data = $db->selectQuery($query);
        return $data;
    }

    public static function getActualLessons()
    {
        $query = "SELECT DISTINCT lessonName FROM isdb.evaluationTeachers
                  WHERE evaluates IS NOT null
                    AND evaluates <> '0000000000000000'
                    AND lessonName <> ''
                  ORDER BY lessonName;
                 ";
        $db = Db::getDb();
        $data = $db->selectQuery($query);
        return $data;       
    }

    public function getDumpForReport2()
    {
        require_once ROOT.'/application/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('Система оценивания уроков')
        ->setLastModifiedBy('Система оценивания уроков')
        ->setTitle('Отчет по критериям в разрезе предметов')
        ->setSubject('Отчет по критериям в разрезе предметов')
        ->setDescription('Отчет по критериям в разрезе предметов')
        ->setKeywords('office 2007 openxml php')
        ->setCategory('Отчет');

        // Add data from model
        $arrayData = self::getDataForReport2();
        $arrayDefCriterias = self::getDefaultCriteriasList();
        $actualLessons = self::getActualLessons();
        $colCount = count($actualLessons);
        
        // Cell styles
        $styleTopHeaders = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
                            'font' => ['bold' => true,
                                       'size' => 12],
                            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],
                        ];
        $styleLeftHeaders = ['alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
                             'font' => ['bold' => true,
                                        'size' => 12],
                             'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],
                        ];
        $styleData = ['borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,],],
                      'font' => ['size' => 12],
                      'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
                                      'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,],
                     ];

        // Column width
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setWidth(40);

        // Put headers
        $spreadsheet->getActiveSheet()->getRowDimension(1)->setRowHeight(25);
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Отчет по критериям в разрезе предметов');
        
        // Put data into cells
        foreach ($arrayDefCriterias as $criteria) {
            $i = $criteria['num']+2;
            $spreadsheet->getActiveSheet()->setCellValue('A'.$i, $this->getTexts('CRITERIAS', $criteria['d2']));
            $spreadsheet->getActiveSheet()->getRowDimension($i)->setRowHeight(-1);
        }
        for ($n = 0; $n <= 15; $n++) { $common[$n] = 0; };
        $spreadsheet->getActiveSheet()->getRowDimension(2)->setRowHeight(33);
        $i = 66; //"B" letter's number in ASCII table
        foreach ($actualLessons as $lesson) {
            $spreadsheet->getActiveSheet()->getColumnDimension(chr($i))->setWidth(11);
            $spreadsheet->getActiveSheet()->getColumnDimension(chr($i+1))->setWidth(11);
            $spreadsheet->getActiveSheet()->mergeCells(chr($i).'2:'.chr($i+1).'2');
            $spreadsheet->getActiveSheet()->setCellValue(chr($i).'2', $lesson['lessonName']);
            $spreadsheet->getActiveSheet()->getStyle(chr($i).'2:'.chr($i+1).'2')->applyFromArray($styleTopHeaders);
            for ($k = 0; $k <= 15; $k++) { $averages[$k] = 0; };
            for ($j = 0; $j <= 15; $j++) {
                $c = 0;
                foreach ($arrayData as $data) {
                    if ($data['lessonName'] == $lesson['lessonName']) {
                        if (substr($data['evaluates'],$j,1) != '0') { $c++; };
                        $averages[$j] += (int)substr($data['evaluates'],$j,1);
                    }
                }
                if ($c != 0) { 
                    $spreadsheet->getActiveSheet()->setCellValue(chr($i).($j+3), number_format($averages[$j]/$c, 1,',',''));
                    $spreadsheet->getActiveSheet()->setCellValue(chr($i+1).($j+3), number_format($averages[$j]/($c*5)*100, 2,',','').'%');
                    $common[$j] += (int)number_format($averages[$j]/$c, 1,',','');
                } else {
                    $spreadsheet->getActiveSheet()->setCellValue(chr($i).($j+3), number_format($c, 1,',',''));
                    $spreadsheet->getActiveSheet()->setCellValue(chr($i+1).($j+3), $c.'%');
                    $common[$j] += (int)number_format($c, 1,',','');
                }
            }
            $i=$i+2;
        }
        $spreadsheet->getActiveSheet()->mergeCells(chr($i).'2:'.chr($i+1).'2');
        $spreadsheet->getActiveSheet()->setCellValue(chr($i).'2', 'Средний балл');
        $spreadsheet->getActiveSheet()->getStyle(chr($i).'2:'.chr($i+1).'2')->applyFromArray($styleTopHeaders);
        $spreadsheet->getActiveSheet()->getColumnDimension(chr($i))->setWidth(11);
        $spreadsheet->getActiveSheet()->getColumnDimension(chr($i+1))->setWidth(11);
        for ($j = 0; $j <= 15; $j++) {
            $spreadsheet->getActiveSheet()->setCellValue(chr($i).($j+3), number_format($common[$j]/$colCount, 2,',',''));
            $spreadsheet->getActiveSheet()->setCellValue(chr($i+1).($j+3), number_format($common[$j]/($colCount*5)*100, 2,',','').'%');
        }

        $spreadsheet->getActiveSheet()->getStyle('A2:'.chr($colCount+75).'18')->getAlignment()->setWrapText(true);

        $spreadsheet->getActiveSheet()->getStyle('A1')->applyFromArray(['font'=>['bold'=>true,'size'=>14]]);
        $spreadsheet->getActiveSheet()->getStyle('A3:A18')->applyFromArray($styleLeftHeaders);
        $spreadsheet->getActiveSheet()->getStyle('B3:'.chr($colCount+74).'18')->applyFromArray($styleData);

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
        $params['iin'] = $this->getTeacherIin($post_params['person']);
        $query = "
        SELECT teacher_purpose FROM isdb.teachers_purposes
        WHERE teacher_iin = :iin
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);

        if (!empty($data)) { 
            return $data[0]['teacher_purpose']; 
        } else {
            return 'empty';
        }
    }

    public function savePersonPurpose($post_params)
    {
        $params['iin'] = $this->getTeacherIin($post_params['person']);
        $query = "
        SELECT teacher_purpose FROM isdb.teachers_purposes
        WHERE teacher_iin = :iin
        ;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,$params);

        if (empty($data)) {
            $params['person'] = $post_params['person'];
            $params['purpose'] = $post_params['purpose'];
            $query = "
            INSERT INTO isdb.teachers_purposes (teacher_iin, teacher_fio, teacher_purpose)
            VALUES (:iin, :person, :purpose)
            ;";
            $db = Db::getDb();
            $data = $db->selectQuery($query,$params);
        } else {
            $params['purpose'] = $post_params['purpose'];
            $query = "
            UPDATE isdb.teachers_purposes
            SET teacher_purpose = :purpose
            WHERE teacher_iin = :iin
            ;";
            $db = Db::getDb();
            $data = $db->IUDQuery($query,$params);
        }

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
            if (strtotime($data[$i]['visitDateFrom']) <= strtotime(date("d.m.Y")) && strtotime($data[$i]['visitDateTo']) >= strtotime(date("d.m.Y"))) {
                $data[$i]['class'] = 'allowed';
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut">Выгрузить</button>';
                if ($data[$i]['evaluates'] != '' && $data[$i]['lesson_review'] != '' && $data[$i]['purpose_review'] != '') {
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = '<i class="status">Ожидает подтверждения</i>';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = '<i class="status">Подтверждено</i>';
                    } else {
                        $data[$i]['status'] = '<i class="status">На подтверждении</i>';
                    }
                } else {
                    $data[$i]['status'] = '<i class="status">На оценивании</i>';
                }
            } else {
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                $data[$i]['class'] = 'planned';
                $data[$i]['status'] = '<i class="status">Запланировано</i>';
            }
            if (substr($data[$i]['confirmations'],0,1) == "1") {
                $data[$i]['result'] .= '<button name="deleteResults" class="visitBut" disabled>Удалить</button>';
            } else {
                $data[$i]['result'] .= '<button name="deleteResults" class="visitBut">Удалить</button>';
            }
            $data[$i]['visitDate'] = date("d.m.Y", strtotime($data[$i]['visitDateFrom'])).' - '.date("d.m.Y", strtotime($data[$i]['visitDateTo']));
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
                if ($data[$i]['evaluates'] != '' && $data[$i]['lesson_review'] != '' && $data[$i]['purpose_review'] != '') {
                    $data[$i]['result'] = '<button name="saveToPDF" class="visitBut">Выгрузить</button>';
                    $data[$i]['class'] = 'allowed';
                    if ($data[$i]['confirmations'] == "00") {
                        $data[$i]['status'] = '<i class="status">Ожидает подтверждения</i>';
                    }
                    else if ($data[$i]['confirmations'] == "11") {
                        $data[$i]['status'] = '<i class="status">Подтверждено</i>';
                    } else {
                        $data[$i]['status'] = '<i class="status">На подтверждении</i>';
                    }
                } else {
                    $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                    $data[$i]['class'] = 'planned';
                    $data[$i]['status'] = '<i class="status">Нет оценок</i>';
                }
            } else {
                $data[$i]['result'] = '<button name="saveToPDF" class="visitBut" disabled>Выгрузить</button>';
                $data[$i]['class'] = 'planned';
                $data[$i]['status'] = '<i class="status">Запланировано</i>';
            }
            $data[$i]['visitDate'] = date("d.m.Y", strtotime($data[$i]['visitDateFrom'])).' - '.date("d.m.Y", strtotime($data[$i]['visitDateTo']));
        }
        return $data;
    }

    public function getSynod($params)
    {
        $query = "SELECT focus, visitDateFrom, visitDateTo, whoVisited
                  FROM isdb.teachers_attestation
                  WHERE iinWhoWasVisited = :iin
                    AND groupId = (SELECT DISTINCT MAX(groupId) FROM isdb.teachers_attestation);";
        $db = Db::getDb();
        $data = $db->selectQuery($query,['iin' => $this->getTeacherIin($params['person'])]);
        $str = '';
        foreach ($data as $key => $value) {
            $str .= implode('|', $value).'|';
        }
        echo $str;
    }

    public function saveSynod($post_params)
    {
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
        
        return $data;
    }

    public function getMaxGroupId()
    {
        $query = "SELECT DISTINCT MAX(groupId) AS gId FROM isdb.teachers_attestation;";
        $db = Db::getDb();
        $data = $db->selectQuery($query,[]);
        if ($data[0]['gId'] != '') { return strval($data[0]['gId']+1); } else { return '0'; }
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
        $spreadsheet->getProperties()->setCreator('Система оценивания уроков')
        ->setLastModifiedBy('Система оценивания уроков')
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
}