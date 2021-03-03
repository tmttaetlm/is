<?php

/*This file for syncronize data with 1C*/

//define('ROOT', '/home/developer/Code/PHP/is');
define('ROOT', '/var/www/is');

//Turns on the Composer Autoload
require ROOT.'/application/vendor/autoload.php';


require_once ROOT.'/application/Components/Config.php';
require_once ROOT.'/application/Components/Db.php';

class FasSync {
    private $db;

    public function sync()
    {
        $this->db = Components\Db::getDb();
        echo "PHP скрипт запущен, открываем файл выгрузки из 1С\n";
        $spreadsheet = $this->openFile(ROOT.'/application/mnt/KST_FM_1C_FA.xlsx');
        echo "Файл 1C выгрузки открыт для чтения. Начинаем загрузку информации в Базу данных...\n";
        $data = $this->loadData($spreadsheet);
        $libraryData = $this->loadLibraryData($spreadsheet);
        $this->clearDb();
        $this->multiInsert($data,'fixedAsset');
        $this->multiInsert($libraryData,'libraryAsset');
    }

    private function clearDb()
    {
        $query = "TRUNCATE TABLE `isdb`.`fixedAsset`;";
        $this->db->IUDquery($query);
        $query = "TRUNCATE TABLE `isdb`.`libraryAsset`;";
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
        } else {
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

    private function loadData($spreadsheet)
    {
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        //Reads the data from spreadsheet
        for ($row = 1; $row <= $highestRow; $row++)
        {
            $acc = $worksheet->getCellByColumnAndRow(14, $row)->getValue();
            if ($acc != '2419') {
                /*1*/ $data[] = $worksheet->getCellByColumnAndRow(1, $row)->getFormattedValue(); //invNumber
                /*2*/ $data[] = $worksheet->getCellByColumnAndRow(2, $row)->getFormattedValue(); //barcode
                /*3*/ $data[] = $worksheet->getCellByColumnAndRow(3, $row)->getValue(); //description
                /*4*/ $data[] = $worksheet->getCellByColumnAndRow(4, $row)->getValue(); //dateFixed
                /*5*/ $data[] = $worksheet->getCellByColumnAndRow(5, $row)->getFormattedValue(); //iin
                /*6*/ $data[] = $worksheet->getCellByColumnAndRow(6, $row)->getValue(); //person
                /*7*/ $data[] = $worksheet->getCellByColumnAndRow(7, $row)->getValue(); //location
                /*8*/ $data[] = $worksheet->getCellByColumnAndRow(8, $row)->getValue(); //sn
                /*9*/ $data[] = $worksheet->getCellByColumnAndRow(9, $row)->getValue(); //comment
                /*10*/ $data[] = $worksheet->getCellByColumnAndRow(10, $row)->getValue(); //registrationDate
                /*11*/ $data[] = $worksheet->getCellByColumnAndRow(12, $row)->getValue(); //accountablePersonIin
                /*12*/ $data[] = $worksheet->getCellByColumnAndRow(13, $row)->getFormattedValue(); //locationCode
                /*13*/ $data[] = $worksheet->getCellByColumnAndRow(14, $row)->getValue(); //accounts
                /*14*/ $data[] = $worksheet->getCellByColumnAndRow(15, $row)->getValue(); //properties
                /*15*/ $data[] = $worksheet->getCellByColumnAndRow(16, $row)->getValue(); //upgradeInfo
            }
        }
        //Deletes all '#NULL!' values
        for ($i=0; $i < count($data); $i++)
        {
            if ($data[$i] == '#NULL!') { $data[$i] = NULL; }
        }
        return $data;
    }

    private function loadLibraryData($spreadsheet)
    {
        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();
        //Reads the data from spreadsheet
        for ($row = 1; $row <= $highestRow; $row++)
        {
            $acc = $worksheet->getCellByColumnAndRow(14, $row)->getValue();
            if ($acc == '2419') {
                /*1*/ $data[] = $worksheet->getCellByColumnAndRow(1, $row)->getFormattedValue(); //invNumber
                /*2*/ $data[] = $worksheet->getCellByColumnAndRow(2, $row)->getFormattedValue(); //barcode
                /*3*/ $data[] = $worksheet->getCellByColumnAndRow(3, $row)->getValue(); //description
                /*4*/ $data[] = $worksheet->getCellByColumnAndRow(4, $row)->getValue(); //dateFixed
                /*5*/ $data[] = $worksheet->getCellByColumnAndRow(5, $row)->getFormattedValue(); //iin
                /*6*/ $data[] = $worksheet->getCellByColumnAndRow(6, $row)->getValue(); //person
                /*7*/ $data[] = $worksheet->getCellByColumnAndRow(7, $row)->getValue(); //location
                /*8*/ $data[] = $worksheet->getCellByColumnAndRow(8, $row)->getValue(); //sn
                /*9*/ $data[] = $worksheet->getCellByColumnAndRow(9, $row)->getValue(); //comment
                /*10*/ $data[] = $worksheet->getCellByColumnAndRow(10, $row)->getValue(); //registrationDate
                /*11*/ $data[] = $worksheet->getCellByColumnAndRow(12, $row)->getValue(); //accountablePersonIin
                /*12*/ $data[] = $worksheet->getCellByColumnAndRow(13, $row)->getFormattedValue(); //locationCode
                /*13*/ $data[] = $worksheet->getCellByColumnAndRow(14, $row)->getValue(); //accounts
                /*14*/ $data[] = $worksheet->getCellByColumnAndRow(15, $row)->getValue(); //properties
                /*15*/ $data[] = $worksheet->getCellByColumnAndRow(16, $row)->getValue(); //upgradeInfo
            }
        }
        //Deletes all '#NULL!' values
        for ($i=0; $i < count($data); $i++)
        {
            if ($data[$i] == '#NULL!') { $data[$i] = NULL; }
        }
        return $data;
    }

    private function multiInsert($data,$mode)
    {
        $numRows = count($data)/15;
        while ($numRows > 4000)
        {
            $d = array_splice($data,0,4000*15);
            $this->writeData(4000,$d,$mode);
            $numRows -= 4000;
        }
        if ($numRows > 0)
        {
            $this->writeData($numRows,$data,$mode);
        }
    }

    private function writeData($numRows,$data,$mode)
    {
    	$placeHolders='';
    	for ($i=1; $i <= $numRows; $i++)
	    {
            $placeHolders .= '(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?),';
    	}
    	$placeHolders = mb_substr($placeHolders, 0, -1);
        //make query
        $query = "INSERT INTO ".$mode." (invNumber,barcode,description,dateFix,iin,person,location,sn,comment,registrationDate,accountablePersonIin,locationCode,account,properties,upgradeInfo) VALUES ".$placeHolders;
        $this->db->InsertDataByQ($query, $data);
    }
}

$Fs = new FasSync();
$Fs->sync();

echo "Данные успешно загружены\n";
