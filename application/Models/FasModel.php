<?php
namespace Models;

use Core\Model;
use Components\Db;

use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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

    private function getFixedAssetsInventory($iin) {
        $db = Db::getDb();
        $query = "SELECT A.id, A.invNumber, A.barcode, A.description, A.iin, A.location,A.locationCode, A.newLocation, A.newLocationCode, A.sn, A.registrationDate, A.comment,  
                  CONCAT(SUBSTRING(U.person, 1, POSITION(' ' IN U.person)+1),'.') as whoScanned,
                  DATE_FORMAT(scannedTime, '%d.%m.%Y %k:%i') AS scannedTime,
                  CASE barcodeScanned 
                    WHEN 'Yes' 
                      THEN 'Да' 
                        ELSE 'Нет' 
                      END 
                  AS barcodeScanned
                  FROM fixedAssetInventory A
                  LEFT JOIN finishedInventory U
                  ON A.iinWhoScanned = U.iin
                  WHERE A.iin = :iin
                  ORDER BY description;";
        
        return $db->selectQuery($query,['iin'=>$iin]);
    }
    
    private function getModificationDate() 
    {
        $db = Db::getDb();
        $result = $db->selectQuery("SELECT dateTimeValue FROM info WHERE `key` = '1cFileLastUpdate';");
        return strtotime($result[0]['dateTimeValue']);
    }

    private function getStartedDate() 
    {
        $db = Db::getDb();
        $result = $db->selectQuery("SELECT dateTimeValue FROM info WHERE `key` = 'fasInventoryStarted';");
        return strtotime($result[0]['dateTimeValue']);
    }


    public function index() {
        $updateInfo = '<p class="fasUpdateInfo">Согласно сведениям из ИС 1С:Бухгалтерия. Последняя синхронизация: '.date('d.m.Y H:i',$this->getModificationDate())."</p>\n<br>";
        $data['lastUpdate'] = date('d.m.Y H:i',$this->getModificationDate());
        $data['inventoryStartedAt'] = date('d.m.Y H:i',$this->getStartedDate());
        $data['tabItems']['monitoring']='Мои ОС';
        $userPriveleges = $this->user->getPriveleges();
        if (in_array("fasCanSeach", $userPriveleges)) {
            $data['tabItems']['seach'] = 'Поиск в БД';
        }
        //Inventory
        if ($this->getInventoryStatus()) {
            $data['tabItems']['inventory'] = 'Инвентаризация';
            $data['inventoryData'] = $this->getInventoryData();
            $data['inventoryFinished'] = $this->checkInventoryFinished();
            $data['tabData']['inventory'] =  $this->view->generate('fas/inventory',$data);
        }

        //InventoryControl
        if (in_array("fasInvControl", $userPriveleges)) {
            $data['tabItems']['inventoryControl'] = 'Инв. контроль';
            $data['canInvStart'] = $this->user->hasPrivilege('fasInvStart');
            $data['tabData']['inventoryControl'] =  $this->view->generate('fas/inventoryControl',$data);
        }


        //set the title of the table
        $title = 'ОC закрепленные за сотрудником: <u>'.$this->user->getFullName().'</u>'; 
        //get user fixed assets from the Data Base 
        $result = $this->getFixedAssets($this->user->getIin());
        $result = $this->addRowNumbers($result);
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

    public function getInventoryStatus(){
        $db = Db::getDb();
        $data = $db->selectQuery("SELECT booleanValue FROM info WHERE `key` = 'fasInventoryStarted'");
        return $data[0]['booleanValue'];
    }

    
    
    //Add select tags
    private function addSelectTags($data){
         $c=count($data);
         
         for($i=0;$i<$c;$i++){
            if ($data[$i]['barcodeScanned']=='Да'){
                if(!is_null($data[$i]['newLocation'])){
                    $data[$i]['newLocation'] = "<select data-id='".$data[$i]['id']."' class='inv-select-location'>\n<option value='".$data[$i]['newLocationCode']."'>".$data[$i]['newLocation']."</option>\n </select>\n";
                    $data[$i]['comment'] = "<select data-id='".$data[$i]['id']."' class='inv-select-comment'>\n<option value='".$data[$i]['comment'] ."'>".$data[$i]['comment']."</option>\n </select>\n";
                }
                else{
                    $data[$i]['newLocation'] = "<select data-id='".$data[$i]['id']."' class='inv-select-location'>\n<option value='".$data[$i]['locationCode'] ."'>".$data[$i]['location']."</option>\n </select>\n";
                    $data[$i]['comment'] = "<select data-id='".$data[$i]['id']."' class='inv-select-comment'>\n<option value='".$data[$i]['comment'] ."'>".$data[$i]['comment']."</option>\n </select>\n";
                }
            }
            else{
                $data[$i]['newLocation'] = $data[$i]['location'];
            }
         }
        return $data;
        
    }


    //Inventory data
    public function getInventoryData(){
        $result = $this->getFixedAssetsInventory($this->user->getIin());
        $rowNumber = 0;
        foreach($result as $row){
            if ($row['newLocation']==''){
                $result[$rowNumber]['newLocation'] = $row['location'];
            };
            $rowNumber += 1;
        };
        $result = $this->addRowNumbers($result);
        if (!$this->checkInventoryFinished()){
            $result = $this->addSelectTags($result);
        };
        $columns = [
            'num'=>'№',
            'invNumber'=>'Инвентарный номер',
            'description'=>'Описание',
            'newLocation'=>'Фактическое местонахождение',
            'registrationDate'=>'Балансовая дата',
            'barcodeScanned'=>'ОС отскан.',
            'scannedTime'=>'Время скан.',
            'whoScanned'=>'Инвентаризатор',
            'comment'=>'Комментарий',
            ];
        $title = 'Инвентаризация ОC закрепленных за сотрудником: <u>'.$this->user->getFullName().'</u>';
        return $this->view->cTable($title,$columns, $result,'fasResultTable');
    }

    public function InventoryFinish(){
        $db = Db::getDb();
        $query = 'SELECT COUNT(*) AS count FROM fixedAssetInventory WHERE iin = :iin AND barcodeScanned IS NULL';
        $result = $db->selectQuery($query,['iin'=>$this->user->getIin()]);
        
        if ($result[0]['count'] == 0){
            $query = "UPDATE finishedInventory SET finishedValue = 'YES', finishedAt = NOW() WHERE iin = :iin";
            $db->IUDQuery($query,['iin'=>$this->user->getIin()]);
            return true;
        }
        else {
            return false;
        }
        
        
    }

    public function checkInventoryFinished(){
        $db = Db::getDb();
        $query = "SELECT finishedValue FROM finishedInventory WHERE iin = :iin";
        $result = $db->selectQuery($query,['iin'=>$this->user->getIin()]);
        if (isset($result[0]['finishedValue']) and  ($result[0]['finishedValue']=='YES')){
            return true;
        }
        else{
            return false;
        }
    }

    public function getFasRooms(){
        $db = Db::getDb();
        $query = "SELECT DISTINCT location, locationCode FROM fixedAssetInventory WHERE location IS NOT NULL ORDER BY location";
        $result = $db->selectQuery($query);
        $resultJson = json_encode($result);
        return $resultJson;
    }

    public function getFasComments(){
        $db = Db::getDb();
        $query = "SELECT * FROM inventoryComment";
        $result = $db->selectQuery($query);
        $resultJson = json_encode($result);
        return $resultJson;
    }

    public function inventoryChangeLocation($inventoryId,$locationCode){
        $db = Db::getDb();
        $query = "SELECT locationCode FROM isdb.fixedAssetInventory WHERE id =:inventoryId";
        
        $result = $db->selectQuery($query,['inventoryId'=>$inventoryId]);

        if ($result[0]['locationCode']!=$locationCode){
            $query = "UPDATE isdb.fixedAssetInventory 
            SET newLocationCode = :locationCode, 
            newLocation = (SELECT * FROM (SELECT location FROM isdb.fixedAssetInventory WHERE locationCode = :locationCode2 LIMIT 1) AS X)
            WHERE id=:inventoryId;";
            $result = $db->IUDQuery($query,['locationCode'=>$locationCode,'locationCode2'=>$locationCode,'inventoryId'=>$inventoryId]);
            $resultJson = json_encode($result);
            echo $resultJson;
        }
        else{
            $query = "UPDATE isdb.fixedAssetInventory 
            SET newLocationCode = NULL, 
            newLocation = NULL
            WHERE id=:inventoryId;";
            $result = $db->IUDQuery($query,['inventoryId'=>$inventoryId]);
            $resultJson = json_encode($result);
            echo $resultJson;
        }
        
    }
    public function inventoryChangeComment($inventoryId,$commentId){
        $db = Db::getDb();
        if ($commentId != 0){
            $query = "UPDATE isdb.fixedAssetInventory 
            SET comment = (SELECT comment FROM isdb.inventoryComment WHERE id = :commentId)
            WHERE id = :inventoryId;";
            $result = $db->IUDQuery($query,['inventoryId'=>$inventoryId,'commentId'=>$commentId]);
            $resultJson = json_encode($result);
            echo $resultJson;
        } else {
            $query = "UPDATE isdb.fixedAssetInventory 
            SET comment = NULL
            WHERE id = :inventoryId;";
            $result = $db->IUDQuery($query,['inventoryId'=>$inventoryId]);
            $resultJson = json_encode($result);
            echo $resultJson;  
        }
              
    }

    public static function getInvExport($reportType){
        require_once ROOT.'/application/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $helper = new Sample();
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('Система УОС')
        ->setLastModifiedBy('Система УОС')
        ->setTitle('Выгрузка таблицы инвентаризации')
        ->setSubject('Выгрузка таблицы инвентаризации')
        ->setDescription('Выгрузка таблицы инвентаризации')
        ->setKeywords('office 2007 openxml php')
        ->setCategory('Отчет');

        // Add data from model
        if ($reportType=='allAssets'){
            $arrayData = self::getInvExportData();
        }
        elseif($reportType=='unscannedAssets'){
            $arrayData = self::getInvUnscannedData();
        }
            
        
        // Width for cells
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('L')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('M')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('N')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('O')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('P')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('Q')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('R')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(28);

        $spreadsheet->getActiveSheet()->mergeCells('A1:F1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Отчет по инвентаризации ОС школы на: '. date("d.m.Y H:i:s"));
        //Put headers
        $spreadsheet->getActiveSheet()->setCellValue('A2', '№');
        $spreadsheet->getActiveSheet()->setCellValue('B2', 'Инвентарный номер');
        $spreadsheet->getActiveSheet()->setCellValue('C2', 'Штрих-код');
        $spreadsheet->getActiveSheet()->setCellValue('D2', 'Описание');
        $spreadsheet->getActiveSheet()->setCellValue('E2', 'Закреплен за');
        $spreadsheet->getActiveSheet()->setCellValue('F2', 'Местонахождение');
        $spreadsheet->getActiveSheet()->setCellValue('G2', 'Балансовая дата');

        //aditional colomns
        $spreadsheet->getActiveSheet()->setCellValue('H2', 'Дата закрепления');
        $spreadsheet->getActiveSheet()->setCellValue('I2', 'ИИН ответственного');
        $spreadsheet->getActiveSheet()->setCellValue('J2', 'ИИН экс-ответсвенного');
        $spreadsheet->getActiveSheet()->setCellValue('K2', 'Код местонахождения');
        $spreadsheet->getActiveSheet()->setCellValue('L2', 'МОЛ ИИН');
        $spreadsheet->getActiveSheet()->setCellValue('M2', 'Серийный №');
        $spreadsheet->getActiveSheet()->setCellValue('N2', 'Время сканирования');
        $spreadsheet->getActiveSheet()->setCellValue('O2', 'ИИН кто сканировал');
        $spreadsheet->getActiveSheet()->setCellValue('P2', 'Новое местонахождение');
        $spreadsheet->getActiveSheet()->setCellValue('Q2', 'Код нового местонахождения');
        $spreadsheet->getActiveSheet()->setCellValue('R2', 'Комментарий');

        //Put data into cells
        $i=2;
        foreach ($arrayData as $elem) {
            $i++;
            $spreadsheet->getActiveSheet()->setCellValue('A' . $i, $i-2);
            $spreadsheet->getActiveSheet()->setCellValue('B' . $i, $elem['invNumber']);
            $spreadsheet->getActiveSheet()->setCellValue('C' . $i, $elem['barcode']);
            $spreadsheet->getActiveSheet()->setCellValue('D' . $i, $elem['description']);
            $spreadsheet->getActiveSheet()->setCellValue('E' . $i, $elem['person']);
            $spreadsheet->getActiveSheet()->setCellValue('F' . $i, $elem['location']);
            $spreadsheet->getActiveSheet()->setCellValue('G' . $i, $elem['registrationDate']);

            $spreadsheet->getActiveSheet()->setCellValue('H' . $i, $elem['dateFix']);
            $spreadsheet->getActiveSheet()->setCellValue('I' . $i, $elem['iin']);
            $spreadsheet->getActiveSheet()->setCellValue('J' . $i, $elem['exIin']);
            $spreadsheet->getActiveSheet()->setCellValue('K' . $i, $elem['locationCode']);
            $spreadsheet->getActiveSheet()->setCellValue('L' . $i, $elem['accountablePersonIin']);
            $spreadsheet->getActiveSheet()->setCellValue('M' . $i, $elem['sn']);
            $spreadsheet->getActiveSheet()->setCellValue('N' . $i, $elem['scannedTime']);
            $spreadsheet->getActiveSheet()->setCellValue('O' . $i, $elem['iinWhoScanned']);
            $spreadsheet->getActiveSheet()->setCellValue('P' . $i, $elem['newLocation']);
            $spreadsheet->getActiveSheet()->setCellValue('Q' . $i, $elem['newLocationCode']);
            $spreadsheet->getActiveSheet()->setCellValue('R' . $i, $elem['comment']);
        }

        // Rename worksheet
        $spreadsheet->getActiveSheet()->setTitle('Выгрузка');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Выгрузка.xlsx"');
        header('Cache-Control: max-age=0');
        
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        $styleData = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $styleHeaders = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'bold' => true,
            ],
        ];
        
        $spreadsheet->getActiveSheet()->getStyle('A1')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A2:R2')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A2:R'.$i)->applyFromArray($styleData);
        $spreadsheet->getActiveSheet()->setAutoFilter('B2:R'.$i);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public static function getInvMovementData(){
        $db = Db::getDb();
        $query = "SELECT invNumber, description,currentResponsible, priorOwner,barcode,priorLocation, currentLocation, accountablePerson, whoScanned, concat_ws(';',iin,currentLocationCode,barcode) AS txtFor1C
        FROM
        (SELECT t1.invNumber, t1.description, t1.person AS currentResponsible, t1.iin,  t3.person AS priorOwner, t1.barcode, 
        CASE
        WHEN newLocation IS NOT NULL THEN Location
        WHEN newLocation IS NULL THEN newlocation
        END
        AS priorLocation,
        CASE
        WHEN newLocation IS NOT NULL THEN newLocation
        WHEN newLocation IS NULL THEN location
        END
        AS currentLocation,
        CASE
        WHEN newLocationCode IS NOT NULL THEN newLocationCode
        WHEN newLocationCode IS NULL THEN locationCode
        END
        AS currentLocationCode,
        t4.person AS accountablePerson, t2.person AS whoScanned
        FROM isdb.fixedAssetInventory t1
        LEFT JOIN isdb.finishedInventory t2 ON t1.iinWhoScanned=t2.iin
        LEFT JOIN isdb.finishedInventory t3 ON t1.exIin=t3.iin
        LEFT JOIN isdb.finishedInventory t4 ON t1.accountablePersonIin=t4.iin
        WHERE (exIin IS NOT NULL OR newLocationCode IS NOT NULL)
        ORDER BY accountablePersonIin, t1.iin, currentLocation) AS tempQuery";
        $result = $db->selectQuery($query);
        return $result;
    }

    public static function getInvMovement(){
        require_once ROOT.'/application/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $helper = new Sample();
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('Система УОС')
        ->setLastModifiedBy('Система УОС')
        ->setTitle('Выгрузка таблицы инвентаризации')
        ->setSubject('Выгрузка таблицы инвентаризации')
        ->setDescription('Выгрузка таблицы инвентаризации')
        ->setKeywords('office 2007 openxml php')
        ->setCategory('Отчет');

        // Add data from model
        $arrayData = self::getInvMovementData();
        
        // Width for cells
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('I')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('J')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('K')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(28);

        $spreadsheet->getActiveSheet()->mergeCells('A1:F1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Отчет по движению ОС школы на: '. date("d.m.Y H:i:s"));
        //Put headers
        $spreadsheet->getActiveSheet()->setCellValue('A2', '№');
        $spreadsheet->getActiveSheet()->setCellValue('B2', 'Инвентарный номер');
        $spreadsheet->getActiveSheet()->setCellValue('C2', 'Наименование');
        $spreadsheet->getActiveSheet()->setCellValue('D2', 'Текущий владелец');
        $spreadsheet->getActiveSheet()->setCellValue('E2', 'Прежний владелец');
        $spreadsheet->getActiveSheet()->setCellValue('F2', 'Штрих-код');
        $spreadsheet->getActiveSheet()->setCellValue('G2', 'Текущее местонахождение');

        //aditional colomns
        $spreadsheet->getActiveSheet()->setCellValue('H2', 'Прежнее местонахождение');
        $spreadsheet->getActiveSheet()->setCellValue('I2', 'МОЛ');
        $spreadsheet->getActiveSheet()->setCellValue('J2', 'Инвентаризатор');
        $spreadsheet->getActiveSheet()->setCellValue('K2', 'Код 1C');

        //Put data into cells
        $i=2;
        foreach ($arrayData as $elem) {
            $i++;
            $spreadsheet->getActiveSheet()->setCellValue('A' . $i, $i-2);
            $spreadsheet->getActiveSheet()->setCellValue('B' . $i, $elem['invNumber']);
            $spreadsheet->getActiveSheet()->setCellValue('C' . $i, $elem['description']);
            $spreadsheet->getActiveSheet()->setCellValue('D' . $i, $elem['currentResponsible']);
            $spreadsheet->getActiveSheet()->setCellValue('E' . $i, $elem['priorOwner']);
            $spreadsheet->getActiveSheet()->setCellValue('F' . $i, $elem['barcode']);
            $spreadsheet->getActiveSheet()->setCellValue('G' . $i, $elem['currentLocation']);

            $spreadsheet->getActiveSheet()->setCellValue('H' . $i, $elem['priorLocation']);
            $spreadsheet->getActiveSheet()->setCellValue('I' . $i, $elem['accountablePerson']);
            $spreadsheet->getActiveSheet()->setCellValue('J' . $i, $elem['whoScanned']);
            $spreadsheet->getActiveSheet()->setCellValue('K' . $i, $elem['txtFor1C']);
        }

        // Rename worksheet
        $spreadsheet->getActiveSheet()->setTitle('Выгрузка');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Выгрузка.xlsx"');
        header('Cache-Control: max-age=0');
        
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        $styleData = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $styleHeaders = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'bold' => true,
            ],
        ];
        
        $spreadsheet->getActiveSheet()->getStyle('A1')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A2:K2')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A2:K'.$i)->applyFromArray($styleData);
        $spreadsheet->getActiveSheet()->setAutoFilter('B2:K'.$i);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public static function getInvExportData(){
        $db = Db::getDb();
        $query = "SELECT * FROM isdb.fixedAssetInventory";
        $result = $db->selectQuery($query);
        return $result;
    }

    public static function getInvUnscannedData(){
        $db = Db::getDb();
        $query = "SELECT * FROM isdb.fixedAssetInventory WHERE barcodeScanned IS NULL";
        $result = $db->selectQuery($query);
        return $result;
    }
    

    public function getInvPeople() {
        $db = Db::getDb();
        $data = $db->selectQuery("SELECT person FROM finishedInventory ORDER BY person");
        foreach($data as $row) {
            $data2[] = $row['person']; 
        }
        $data2 = json_encode($data2, JSON_UNESCAPED_UNICODE);
        echo $data2;
    }

    public function invSeachByPerson($person) {
        $db = Db::getDb();
	    $data = $db->selectQuery("SELECT * FROM fixedAssetInventory WHERE person = :person ORDER BY description",['person'=>$person]);  
        $dataCount = count($data);
        for ($i = 0; $i < $dataCount; $i++){
            $data[$i]['invNumber'] = '<span class="invLink">'.$data[$i]['invNumber'].'</span>';
            if ($data[$i]['barcodeScanned']=='Yes'){
                $endStr = ' checked>';
            }
            else{
                $endStr = '>';
            }
            $data[$i]['barcodeScanned'] ='<input type="checkbox" class="invCheckbox" value='.$data[$i]['id'].$endStr;
        }
        return $data;
    }

    public function invSeachByInvNumber($invNumber) {
        $db = Db::getDb();
	    $data = $db->selectQuery("SELECT * FROM fixedAssetInventory WHERE invNumber = :invNumber",['invNumber'=>$invNumber]);  
        $dataCount = count($data);
        for ($i = 0; $i < $dataCount; $i++){
            $data[$i]['invNumber'] = '<span class="invLink">'.$data[$i]['invNumber'].'</span>';
            if ($data[$i]['barcodeScanned']=='Yes'){
                $endStr = ' checked>';
            }
            else{
                $endStr = '>';
            }
            $data[$i]['barcodeScanned'] ='<input type="checkbox" class="invCheckbox" value='.$data[$i]['id'].$endStr;
            
        }
        return $data;
    }

    public function invChangeOwner($invNumber, $newOwner){
        $db = Db::getDb();

        $query = "UPDATE fixedAssetInventory SET 
                  person = :newOwner,
                  exIin = iin,
                  iin = (SELECT iin from finishedInventory WHERE person = :newOwner2)
                  WHERE invNumber = :invNumber";
        $db->IUDQuery($query,['invNumber'=>$invNumber, 'newOwner'=>$newOwner, 'newOwner2'=>$newOwner]);
        
        //Set for receiving person status FINISHED NO
        $query = "UPDATE finishedInventory SET 
        finishedValue = 'NO',
        finishedAt = NULL
        WHERE person = :newOwner";
        $db->IUDQuery($query,['newOwner'=>$newOwner]);

        return true;
    }


    public function getInvLocationList()
    {
        $db = Db::getDb();
        $data = $db->selectQuery("SELECT DISTINCT location FROM fixedAssetInventory");
        foreach($data as $row) {
            $data2[] = $row['location']; 
        }
        $data2 = json_encode($data2, JSON_UNESCAPED_UNICODE);
        echo $data2;
    }
    
    public function getInvFixedAssetList()
    {
        $db = Db::getDb();
        $data = $db->selectQuery("SELECT DISTINCT description FROM fixedAssetInventory");
        foreach($data as $row) {
            $data2[] = $row['description']; 
        }
        $data2 = json_encode($data2, JSON_UNESCAPED_UNICODE);
        echo $data2;
    }
    public function invSeachByLocation($location) {
        $db = Db::getDb();
	    $data = $db->selectQuery("SELECT * FROM fixedAssetInventory WHERE location = :location ORDER BY person,description",['location'=>$location]);
        $dataCount = count($data);
        for ($i = 0; $i < $dataCount; $i++){
            $data[$i]['invNumber'] = '<span class="invLink">'.$data[$i]['invNumber'].'</span>';
            if ($data[$i]['barcodeScanned']=='Yes'){
                $endStr = ' checked>';
            }
            else{
                $endStr = '>';
            }
            $data[$i]['barcodeScanned'] ='<input type="checkbox" class="invCheckbox" value='.$data[$i]['id'].$endStr;
        }
        return $data;
    }

    public function invSeachByFixedAsset($fixedAsset) {
        $db = Db::getDb();
	    $data = $db->selectQuery("SELECT * FROM fixedAssetInventory WHERE description = :fixedAsset",['fixedAsset'=>$fixedAsset]);
        $dataCount = count($data);
        for ($i = 0; $i < $dataCount; $i++){
            $data[$i]['invNumber'] = '<span class="invLink">'.$data[$i]['invNumber'].'</span>';
            if ($data[$i]['barcodeScanned']=='Yes'){
                $endStr = ' checked>';
            }
            else{
                $endStr = '>';
            }
            $data[$i]['barcodeScanned'] ='<input type="checkbox" class="invCheckbox" value='.$data[$i]['id'].$endStr;
        }
        return $data;
    }
    
    public static function getInvPeopleExport($reportType){
        require_once ROOT.'/application/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $helper = new Sample();
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('Система УОС')
        ->setLastModifiedBy('Система УОС')
        ->setTitle('Выгрузка таблицы инвентаризации')
        ->setSubject('Выгрузка таблицы инвентаризации')
        ->setDescription('Выгрузка таблицы инвентаризации')
        ->setKeywords('office 2007 openxml php')
        ->setCategory('Отчет');

        // Add data from model
        $arrayData = self::getInvPeopleData();
 
            
        
        // Width for cells
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(28);

        $spreadsheet->getActiveSheet()->mergeCells('A1:E1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Выгрузка сотрудников по состоянию на: '. date("d.m.Y H:i:s"));
        //Put headers
        $spreadsheet->getActiveSheet()->setCellValue('A2', '№');
        $spreadsheet->getActiveSheet()->setCellValue('B2', 'ИИН');
        $spreadsheet->getActiveSheet()->setCellValue('C2', 'ФИО');
        $spreadsheet->getActiveSheet()->setCellValue('D2', 'Статус');
        $spreadsheet->getActiveSheet()->setCellValue('E2', 'Время завершения');

        //Put data into cells
        $i=2;
        foreach ($arrayData as $elem) {
            $i++;
            $spreadsheet->getActiveSheet()->setCellValue('A' . $i, $i-2);
            $spreadsheet->getActiveSheet()->setCellValue('B' . $i, $elem['iin']);
            $spreadsheet->getActiveSheet()->setCellValue('C' . $i, $elem['person']);
            $spreadsheet->getActiveSheet()->setCellValue('D' . $i, $elem['finishedValue']);
            $spreadsheet->getActiveSheet()->setCellValue('E' . $i, $elem['finishedAt']);
        }

        // Rename worksheet
        $spreadsheet->getActiveSheet()->setTitle('Выгрузка');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Redirect output to a client’s web browser (Xlsx)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Выгрузка.xlsx"');
        header('Cache-Control: max-age=0');
        
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        $styleData = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ];
        $styleHeaders = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
            'font' => [
                'bold' => true,
            ],
        ];
        
        $spreadsheet->getActiveSheet()->getStyle('A1')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A2:E2')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A2:E'.$i)->applyFromArray($styleData);
        $spreadsheet->getActiveSheet()->setAutoFilter('B2:D'.$i);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public static function getInvPeopleData(){
        $db = Db::getDb();
        $query = "SELECT * FROM isdb.finishedInventory";
        $result = $db->selectQuery($query);
        return $result;
    }

    public static function InvChangeScannedStatus($id,$status,$iin){
        $db = Db::getDb();
        if ($status=='false'){
            $query = "UPDATE fixedAssetInventory SET 
                barcodeScanned = NULL,
                scannedTime = NULL,
                iinWhoScanned = NULL,
                newLocation = NULL,
                newLocationCode = NULL,
                comment = NULL
                WHERE id = :id";
            $result = $db->IUDQuery($query,['id'=>$id]);
            echo $result;
        }
        if ($status=='true'){
            $query = "UPDATE fixedAssetInventory SET 
                barcodeScanned = 'Yes',
                scannedTime = NOW(),
                iinWhoScanned = :iin
                WHERE id = :id";
            $result = $db->IUDQuery($query,['id'=>$id, 'iin' => $iin]);
            echo $result;
        }
    }

    public function transmitAssets($transmittingPerson, $invReceivingPerson){
        $db = Db::getDb();
        $query = "UPDATE fixedAssetInventory SET 
                  person = :invReceivingPerson,
                  exIin = iin,
                  iin = (SELECT iin from finishedInventory WHERE person = :invReceivingPerson2)
                  WHERE person = :transmittingPerson";
        $db->IUDQuery($query,['transmittingPerson'=>$transmittingPerson, 'invReceivingPerson'=>$invReceivingPerson, 'invReceivingPerson2'=>$invReceivingPerson]);
        
        //Set for transmitting person status FINISHED YES
        $query = "UPDATE finishedInventory SET 
        finishedValue = 'YES',
        finishedAt = NOW()
        WHERE person = :transmittingPerson";
        $db->IUDQuery($query,['transmittingPerson'=>$transmittingPerson]);

        //Set for receiving person status FINISHED NO
        $query = "UPDATE finishedInventory SET 
        finishedValue = 'NO',
        finishedAt = NULL
        WHERE person = :invReceivingPerson";
        $db->IUDQuery($query,['invReceivingPerson'=>$invReceivingPerson]);
        
        return true;
    }

    public function startInventory(){
        $db = Db::getDb();
        $query = "UPDATE info SET 
                  dateTimeValue = NOW(),
                  booleanValue = '1'
                  WHERE `key` = 'fasInventoryStarted';
                  ";
        $db->IUDQuery($query);
        
        $query = "TRUNCATE TABLE fixedAssetInventory";
        $db->IUDQuery($query);
        
        $query = "TRUNCATE TABLE finishedInventory";
        $db->IUDQuery($query);
        
        $query = "INSERT INTO `isdb`.`fixedAssetInventory`
        (`invNumber`,
        `barcode`,
        `description`,
        `dateFix`,
        `iin`,
        `person`,
        `location`,
        `locationCode`,
        `accountablePersonIin`,
        `sn`,
        `registrationDate`)
        SELECT
            `fixedAsset`.`invNumber`,
            `fixedAsset`.`barcode`,
            `fixedAsset`.`description`,
            `fixedAsset`.`dateFix`,
            `fixedAsset`.`iin`,
            `fixedAsset`.`person`,
            `fixedAsset`.`location`,
            `fixedAsset`.`locationCode`,
            `fixedAsset`.`accountablePersonIin`,
            `fixedAsset`.`sn`,
            `fixedAsset`.`registrationDate`
        FROM `isdb`.`fixedAsset`;";
        $db->IUDQuery($query);
        
        $query = "INSERT INTO `isdb`.`finishedInventory`
        (`iin`,
        `person`)
        SELECT distinct iin, person FROM fixedAssetInventory where person IS NOT NULL;";
        $db->IUDQuery($query);        
        
        return true;

    }
    public function stopInventory(){
        $db = Db::getDb();
        $query = "UPDATE info SET 
                  booleanValue = '0'
                  WHERE `key` = 'fasInventoryStarted'";
        $db->IUDQuery($query);
        return true;
    }
}
