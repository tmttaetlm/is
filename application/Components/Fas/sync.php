<?php

/* 
 * This file for syncronize data with 1C
 */
define('ROOT', '/var/www/is');
//Turns on the Composer Autoload
require ROOT.'/application/vendor/autoload.php';


require_once ROOT.'/application/Components/Config.php';
require_once ROOT.'/application/Components/Db.php';

class FasSync {
    private $db;

    public function sync() {
        $this->db = Components\Db::getDb();
        echo "PHP скрипт запущен, открываем файл выгрузки из 1С\n";
        $spreadsheet = $this->openFile(ROOT.'/application/mnt/KST_FM_1C_FA.xlsx');
        echo "Файл 1C выгрузки открыт для чтения. Начинаем загрузку информации в Базу данных...\n";
        $data = $this->loadData($spreadsheet);
        $this->clearDb();
        $this->multiInsert($data);
        
    }
    
    private function clearDb() {
        $query = "
        DELETE FROM `isdb`.`fixedAsset`;";
        $this->db->IUDquery($query);
    }


    private function openFile($file) 
    { 
        //Cheks if the 1C file exists and returns spreadsheet
        if (file_exists($file)) {
            $this->saveFileModificationTime($file);
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(FALSE);
            $spreadsheet = $reader->load($file);
            return $spreadsheet;
        }
        
        else {
            die('Can not open the 1C file');
        }
    }

    private function saveFileModificationTime($file)
    {
        date_default_timezone_set("Asia/Almaty"); 
        $modTime = date ("Y-m-d H:i:s", filemtime($file));
        $query = "UPDATE info
        SET `dateTimeValue` = :modTime
        WHERE `key` = '1cFileLastUpdate';";
        $this->db->IUDquery($query,['modTime'=>$modTime]);
    }

    
    private function loadData($spreadsheet) {
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        
        //Reads the data from spreadsheet
        for ($row = 1; $row <= $highestRow; $row++) 
        {
            //invNumber
            $data[] = $worksheet->getCellByColumnAndRow(1, $row)->getFormattedValue();
            //barcode
            $data[] = $worksheet->getCellByColumnAndRow(2, $row)->getFormattedValue();
            //description
            $data[] = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
            //dateFixed
            $data[] = $worksheet->getCellByColumnAndRow(4, $row)->getValue();
            //iin
            $data[] = $worksheet->getCellByColumnAndRow(5, $row)->getFormattedValue();
            //person
            $data[] = $worksheet->getCellByColumnAndRow(6, $row)->getValue();
            //location
            $data[] = $worksheet->getCellByColumnAndRow(7, $row)->getValue();
            //sn
            $data[] = $worksheet->getCellByColumnAndRow(8, $row)->getValue();
        }
        
        //Deletes all '#NULL!' values
        for ($i=0; $i < count($data); $i++)
        {
            if ($data[$i] == '#NULL!') 
            {
                $data[$i] = NULL;
            }
        }
        
        return $data;
    }
    
    private function multiInsert($data)
    {
        $numRows = count($data)/8; 

        while ($numRows > 5000)
        {
            $d = array_splice($data,0,5000*8);
            $this->writeData(5000,$d);
            $numRows -=5000;
        }  

        if ($numRows > 0)
        {
            $this->writeData($numRows,$data);
        }

    }
    
    private function writeData($numRows,$data)
    {
    	$placeHolders='';
    	for ($i=1; $i <= $numRows; $i++)
	{
            $placeHolders.= '(?,?,?,?,?,?,?,?),';
    	}

    	$placeHolders = mb_substr($placeHolders, 0, -1);
        //make query
        $query = "INSERT INTO fixedAsset(invNumber,barcode,description,dateFix,iin,person,location,sn) VALUES ".$placeHolders;
        $this->db->InsertDataByQ($query, $data);
    }
    

}

$Fs = new FasSync();
$Fs->sync();

echo "Данные успешно загружены\n";