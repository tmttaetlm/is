<?php

namespace Models;
use Core\Model;
use Components\DbSkd;


use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SkdModel extends Model {
    
    public static function allLogs()
    {
        //Получаем сегодняшнюю дату и завтрашнюю для ограничения
        $param['dateStart'] = date("Ymd");
        $param['dateEnd'] = date("Ymd",strtotime("+1 day"));

        //Запрос "кто и во сколько вошел сегодня"
        $tsql = "
        SELECT L.UserId, P.Name AS Surname, P.Firstname AS Firstname, 
               D.Name AS Division, convert(varchar(20),MIN(DateTime),108) AS Time 
        FROM GateLog L 
        INNER JOIN dbo.pList P ON L.UserId = P.ID 
        INNER JOIN dbo.pDivision D ON P.Section = D.ID 
        WHERE Mode = 1 AND 
        DateTime >=:dateStart AND DateTime <:dateEnd 
        GROUP BY L.UserId, P.Name, P.Firstname, D.Name
        ORDER BY D.Name, P.Name";

        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$param);
        return $data;
    }
    
    public static function getUserLogs($params)
    {
        $params['date'] = date("Ymd",strtotime("-30 days"));
        $tsql = "
        SELECT DateTime AS OrderDate, convert(varchar(20),DateTime,113) AS DateTime, 
        CASE 
        WHEN dbo.GateLog.Mode=1 THEN 'Вход'
        WHEN dbo.GateLog.Mode=2 THEN 'Выход'
        END
        AS Mode  
        FROM dbo.GateLog 
        WHERE UserId = (SELECT id From dbo.pList WHERE MidName = :iin) 
        AND DateTime > :date 
        ORDER BY OrderDate";
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        $data = self::addRowNumbers($data);
        return $data;
    }
    
     public static function getDivisionList()
    {
        $tsql = "
        SELECT Id, Name 
        FROM dbo.pDivision 
        WHERE Name not LIKE '%[A-O]'
        ORDER BY Name";
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql);
        return $data;
    }

    
    public static function getUserEntranceExit($params)
    {
        $date = date("Ymd");
        $dateStart = date('Ymd', strtotime($date. ' - 30 days'));
        $dateEnd = date("Ymd",strtotime("+1 day"));
        $params['dateStart1'] = $dateStart;
        $params['dateStart2'] = $dateStart;
        $params['dateEnd1'] = $dateEnd;
        $params['dateEnd2'] = $dateEnd;
        
        //Запрос "кто и во сколько вошел и вышел сегодня"
        $tsql = "
        SET NOCOUNT ON
        DECLARE @@Entering TABLE (Id Int, UserId Int, TimeEvent DateTime, EventKey Varchar(20))
		INSERT INTO @@Entering 
		SELECT Temp1.Id, Temp1.UserId, Temp1.TimeEvent, CAST(Temp1.UserId AS varchar)+CONVERT(varchar, Temp1.TimeEvent, 10)+CAST(Temp1.DayEventQty AS varchar) AS EventId
		FROM
		(SELECT L.ID, L.UserID, L.Mode, L.DateTime AS TimeEvent, ROW_NUMBER()OVER(PARTITION BY (L.UserID), CONVERT(varchar(11), L.DateTime) ORDER BY (L.ID)) AS DayEventQty
		FROM dbo.GateLog L
		WHERE L.Mode = 1 and L.DateTime >= :dateStart1  AND L.DateTime <= :dateEnd1) AS Temp1
		WHERE Temp1.DayEventQty=1

        DECLARE @@Leaving  TABLE (Id Int, UserId Int, TimeEvent DateTime, EventKey Varchar(20))
		INSERT INTO @@Leaving
		SELECT Temp2.Id, Temp2.UserId, Temp2.TimeEvent, CAST(Temp2.UserId AS varchar)+CONVERT(varchar, Temp2.TimeEvent, 10)+CAST(Temp2.DayEventQty AS varchar) AS EventId
		FROM
		(SELECT L.ID, L.UserID, L.Mode, L.DateTime AS TimeEvent, ROW_NUMBER()OVER(PARTITION BY (L.UserID), CONVERT(varchar(11), L.DateTime) ORDER BY (L.ID) DESC) AS DayEventQty
		FROM dbo.GateLog L
		WHERE L.Mode = 2 and L.DateTime >= :dateStart2 AND L.DateTime <= :dateEnd2) AS Temp2
		WHERE Temp2.DayEventQty=1

        SELECT Et.UserId, P.Name AS Surname, P.Firstname AS Firstname,D.Name AS Division, CONVERT(varchar, Et.TimeEvent, 104) AS Date, CONVERT(varchar, Et.TimeEvent, 108) As EntranceTime, CONVERT(Varchar, Lv. TimeEvent, 108) As LeavingTime FROM @@Entering As Et
        FULL JOIN @@Leaving Lv ON  Et.EventKey=Lv.EventKey
        FULL JOIN dbo.pList P ON Et.UserId = P.ID 
        INNER JOIN dbo.pDivision D ON P.Section = D.ID 
        WHERE Et.UserId = (SELECT id From dbo.pList WHERE MidName = :iin)
        ORDER BY Et.TimeEvent
        ";
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        $data = self::addRowNumbers($data);
        return $data;
        
    }
    
    
    
    //
    public function getTabItems()
    {
        $tabs = [];
        $tabs['userControl']='Мои проходы';
        $userPriveleges = $this->user->getPriveleges();
        if (in_array("skdCanBrowseStudentsLogs", $userPriveleges)) {
            $tabs['studentControl']='Контроль учащихся';
        }
        if (in_array("skdCanBrowseStaffLogs", $userPriveleges)) {
            $tabs['staffControl']='Контроль сотрудников';
        }

        if (in_array("skdCanBrowseGeneralControl", $userPriveleges)) {
            $tabs['generalControl']='Общий контроль';
        }

        return $tabs;
    }
 
    public static function getDivisionStaffLogs($date,$divisionId)
    {
        $date = str_replace('-', '', $date); 
        $date.= ' 00:00:00';
        $params['dateStart1'] = $date;
        $params['dateStart2'] = $date;
        $date2 = date('Ymd', strtotime($date. ' + 1 days'));
        $params['dateEnd1'] = $date2 ;
        $params['dateEnd2'] = $date2;
        $params['divisionId'] = $divisionId;
        
         //Запрос "кто и во сколько вошел и вышел сегодня"
        $tsql = "
        SET NOCOUNT ON
        DECLARE @@Entering TABLE (Id Int, UserId Int, TimeEvent DateTime, EventKey Varchar(20))
		INSERT INTO @@Entering 
		SELECT Temp1.Id, Temp1.UserId, Temp1.TimeEvent, CAST(Temp1.UserId AS varchar)+CONVERT(varchar, Temp1.TimeEvent, 10)+CAST(Temp1.DayEventQty AS varchar) AS EventId
		FROM
		(SELECT L.ID, L.UserID, L.Mode, L.DateTime AS TimeEvent, ROW_NUMBER()OVER(PARTITION BY (L.UserID), CONVERT(varchar(11), L.DateTime) ORDER BY (L.ID)) AS DayEventQty
		FROM dbo.GateLog L
		WHERE L.Mode = 1 and L.DateTime >= :dateStart1  AND L.DateTime <= :dateEnd1) AS Temp1
		WHERE Temp1.DayEventQty=1

        DECLARE @@Leaving  TABLE (Id Int, UserId Int, TimeEvent DateTime, EventKey Varchar(20))
		INSERT INTO @@Leaving
		SELECT Temp2.Id, Temp2.UserId, Temp2.TimeEvent, CAST(Temp2.UserId AS varchar)+CONVERT(varchar, Temp2.TimeEvent, 10)+CAST(Temp2.DayEventQty AS varchar) AS EventId
		FROM
		(SELECT L.ID, L.UserID, L.Mode, L.DateTime AS TimeEvent, ROW_NUMBER()OVER(PARTITION BY (L.UserID), CONVERT(varchar(11), L.DateTime) ORDER BY (L.ID) DESC) AS DayEventQty
		FROM dbo.GateLog L
		WHERE L.Mode = 2 and L.DateTime >= :dateStart2 AND L.DateTime <= :dateEnd2) AS Temp2
		WHERE Temp2.DayEventQty=1

        SELECT Et.UserId, P.Name AS Surname, P.Firstname AS Firstname, P.Address AS Comment, 
               D.Name AS Division, CONVERT(Varchar, Et.TimeEvent, 108) As EntranceTime, CONVERT(Varchar, Lv. TimeEvent, 108) As LeavingTime FROM @@Entering As Et
        FULL JOIN @@Leaving Lv ON  Et.EventKey=Lv.EventKey
        FULL JOIN dbo.pList P ON Et.UserId = P.ID 
        INNER JOIN dbo.pDivision D ON P.Section = D.ID 
        WHERE D.Id = :divisionId
        ORDER BY D.Name, P.Name
        ";
        
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        $data = self::addRowNumbers($data);
        return $data;
        
    }
    
    
    public static function getStaffLogs($date)
    {
        $date = str_replace('-', '', $date); 
        $date.= ' 00:00:00';
        $params['dateStart1'] = $date;
        $params['dateStart2'] = $date;
        $date2 = date('Ymd', strtotime($date. ' + 1 days'));
        $params['dateEnd1'] = $date2 ;
        $params['dateEnd2'] = $date2;
        
         //Запрос "кто и во сколько вошел и вышел сегодня"
        $tsql = "
        SET NOCOUNT ON
        DECLARE @@Entering TABLE (Id Int, UserId Int, TimeEvent DateTime, EventKey Varchar(20))
		INSERT INTO @@Entering 
		SELECT Temp1.Id, Temp1.UserId, Temp1.TimeEvent, CAST(Temp1.UserId AS varchar)+CONVERT(varchar, Temp1.TimeEvent, 10)+CAST(Temp1.DayEventQty AS varchar) AS EventId
		FROM
		(SELECT L.ID, L.UserID, L.Mode, L.DateTime AS TimeEvent, ROW_NUMBER()OVER(PARTITION BY (L.UserID), CONVERT(varchar(11), L.DateTime) ORDER BY (L.ID)) AS DayEventQty
		FROM dbo.GateLog L
		WHERE L.Mode = 1 and L.DateTime >= :dateStart1  AND L.DateTime <= :dateEnd1) AS Temp1
		WHERE Temp1.DayEventQty=1

        DECLARE @@Leaving  TABLE (Id Int, UserId Int, TimeEvent DateTime, EventKey Varchar(20))
		INSERT INTO @@Leaving
		SELECT Temp2.Id, Temp2.UserId, Temp2.TimeEvent, CAST(Temp2.UserId AS varchar)+CONVERT(varchar, Temp2.TimeEvent, 10)+CAST(Temp2.DayEventQty AS varchar) AS EventId
		FROM
		(SELECT L.ID, L.UserID, L.Mode, L.DateTime AS TimeEvent, ROW_NUMBER()OVER(PARTITION BY (L.UserID), CONVERT(varchar(11), L.DateTime) ORDER BY (L.ID) DESC) AS DayEventQty
		FROM dbo.GateLog L
		WHERE L.Mode = 2 and L.DateTime >= :dateStart2 AND L.DateTime <= :dateEnd2) AS Temp2
		WHERE Temp2.DayEventQty=1

        SELECT Et.UserId, P.Name AS Surname, P.Firstname AS Firstname, P.Address AS Comment, 
               D.Name AS Division, CONVERT(Varchar, Et.TimeEvent, 108) As EntranceTime, CONVERT(Varchar, Lv. TimeEvent, 108) As LeavingTime FROM @@Entering As Et
        FULL JOIN @@Leaving Lv ON  Et.EventKey=Lv.EventKey
        FULL JOIN dbo.pList P ON Et.UserId = P.ID 
        INNER JOIN dbo.pDivision D ON P.Section = D.ID 
        WHERE D.Name not LIKE '%[A-O]'
        ORDER BY D.Name, P.Name
        ";
        
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        $data = self::addRowNumbers($data);
        return $data;
        
    }
    
    
    
    public static function getStudentsLogs($grade,$date)
    {
        //Получаем сегодняшнюю дату и завтрашнюю для ограничения
        $date = str_replace('-', '', $date); 
        $date.= ' 00:00:00';
        $params['dateStart1'] = $date;
        $params['dateStart2'] = $date;
        $date2 = date('Ymd', strtotime($date. ' + 1 days'));
        $params['dateEnd1'] = $date2 ;
        $params['dateEnd2'] = $date2;
        $params['grade'] = $grade;
        
        //Запрос "кто и во сколько вошел и вышел сегодня"
        $tsql = "
        SET NOCOUNT ON
        DECLARE @@Entering TABLE (Id Int, UserId Int, TimeEvent DateTime, EventKey Varchar(20))
		INSERT INTO @@Entering 
		SELECT Temp1.Id, Temp1.UserId, Temp1.TimeEvent, CAST(Temp1.UserId AS varchar)+CONVERT(varchar, Temp1.TimeEvent, 10)+CAST(Temp1.DayEventQty AS varchar) AS EventId
		FROM
		(SELECT L.ID, L.UserID, L.Mode, L.DateTime AS TimeEvent, ROW_NUMBER()OVER(PARTITION BY (L.UserID), CONVERT(varchar(11), L.DateTime) ORDER BY (L.ID)) AS DayEventQty
		FROM dbo.GateLog L
		WHERE L.Mode = 1 and L.DateTime >= :dateStart1  AND L.DateTime <= :dateEnd1) AS Temp1
		WHERE Temp1.DayEventQty=1

        DECLARE @@Leaving  TABLE (Id Int, UserId Int, TimeEvent DateTime, EventKey Varchar(20))
		INSERT INTO @@Leaving
		SELECT Temp2.Id, Temp2.UserId, Temp2.TimeEvent, CAST(Temp2.UserId AS varchar)+CONVERT(varchar, Temp2.TimeEvent, 10)+CAST(Temp2.DayEventQty AS varchar) AS EventId
		FROM
		(SELECT L.ID, L.UserID, L.Mode, L.DateTime AS TimeEvent, ROW_NUMBER()OVER(PARTITION BY (L.UserID), CONVERT(varchar(11), L.DateTime) ORDER BY (L.ID) DESC) AS DayEventQty
		FROM dbo.GateLog L
		WHERE L.Mode = 2 and L.DateTime >= :dateStart2 AND L.DateTime <= :dateEnd2) AS Temp2
		WHERE Temp2.DayEventQty=1

        SELECT Et.UserId, P.Name AS Surname, P.Firstname AS Firstname, 
               D.Name AS Division, CONVERT(Varchar, Et.TimeEvent, 108) As EntranceTime, CONVERT(Varchar, Lv. TimeEvent, 108) As LeavingTime FROM @@Entering As Et
        FULL JOIN @@Leaving Lv ON  Et.EventKey=Lv.EventKey
        FULL JOIN dbo.pList P ON Et.UserId = P.ID 
        INNER JOIN dbo.pDivision D ON P.Section = D.ID 
        WHERE D.Name = :grade
        ORDER BY D.Name, P.Name
        ";
        
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        $data = self::addRowNumbers($data);
        return $data;
 
    }
    
    public static function getStudentLogsByPeriod ($userId, $startDate,$endDate){
        $startDate = str_replace('-', '', $startDate);
        $endDate = str_replace('-', '', $endDate);
        $startDate.= ' 00:00:00';
        $endDate.=' 23:59:59';
        $params['userId'] = $userId;
        $params['dateStart1'] = $startDate;
        $params['dateStart2'] =$startDate;
        $params['dateEnd1'] =$endDate;
        $params['dateEnd2'] =$endDate;
        
        $tsql = "
        SET NOCOUNT ON
        DECLARE @@Entering TABLE (Id Int, UserId Int, TimeEvent DateTime, EventKey Varchar(20))
		INSERT INTO @@Entering 
		SELECT Temp1.Id, Temp1.UserId, Temp1.TimeEvent, CAST(Temp1.UserId AS varchar)+CONVERT(varchar, Temp1.TimeEvent, 10)+CAST(Temp1.DayEventQty AS varchar) AS EventId
		FROM
		(SELECT L.ID, L.UserID, L.Mode, L.DateTime AS TimeEvent, ROW_NUMBER()OVER(PARTITION BY (L.UserID), CONVERT(varchar(11), L.DateTime) ORDER BY (L.ID)) AS DayEventQty
		FROM dbo.GateLog L
		WHERE L.Mode = 1 and L.DateTime >= :dateStart1  AND L.DateTime <= :dateEnd1) AS Temp1
		WHERE Temp1.DayEventQty=1

        DECLARE @@Leaving  TABLE (Id Int, UserId Int, TimeEvent DateTime, EventKey Varchar(20))
		INSERT INTO @@Leaving
		SELECT Temp2.Id, Temp2.UserId, Temp2.TimeEvent, CAST(Temp2.UserId AS varchar)+CONVERT(varchar, Temp2.TimeEvent, 10)+CAST(Temp2.DayEventQty AS varchar) AS EventId
		FROM
		(SELECT L.ID, L.UserID, L.Mode, L.DateTime AS TimeEvent, ROW_NUMBER()OVER(PARTITION BY (L.UserID), CONVERT(varchar(11), L.DateTime) ORDER BY (L.ID) DESC) AS DayEventQty
		FROM dbo.GateLog L
		WHERE L.Mode = 2 and L.DateTime >= :dateStart2 AND L.DateTime <= :dateEnd2) AS Temp2
		WHERE Temp2.DayEventQty=1

        SELECT Et.UserId, P.Name AS Surname, P.Firstname AS Firstname,D.Name AS Division, CONVERT(varchar, Et.TimeEvent, 104) AS Date, CONVERT(varchar, Et.TimeEvent, 108) As EntranceTime, CONVERT(Varchar, Lv. TimeEvent, 108) As LeavingTime FROM @@Entering As Et
        FULL JOIN @@Leaving Lv ON  Et.EventKey=Lv.EventKey
        FULL JOIN dbo.pList P ON Et.UserId = P.ID 
        INNER JOIN dbo.pDivision D ON P.Section = D.ID 
        WHERE Et.UserId = :userId
        ORDER BY Et.TimeEvent
        ";
        
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        $data = self::addRowNumbers($data);
        return $data;
    }
    
    public static function getStudentsAtSchool($grade)
    {
        
        
        $params['date'] = date("Ymd",strtotime("-3 days"));
        //КТО В ШКОЛЕ
        $tsql = "
        SELECT F.MaxID, F.UserID, F.Surname, F.Firstname, F.Division, (F.Event+' '+F.EventTime) As Status
        FROM
        (SELECT Result.MaxID, Result.UserId, (convert(varchar, DateTime, 108) +'  ' +convert(varchar, DateTime, 106)) AS EventTime,
        P.Name AS Surname, P.Firstname AS Firstname, D.Name AS Division, 
        CASE
            WHEN GateLog.Mode=1 THEN 'В школе с'
            WHEN GateLog.Mode=2 THEN 'Отсутствует с'
        END
        AS Event
        FROM 
        (SELECT MAX(id) as MaxID, UserID
        FROM GateLog L 
        WHERE DateTime >=:date 
        GROUP BY UserId) 
        AS Result
        INNER JOIN dbo.GateLog ON  dbo.GateLog.Id=Result.MaxID
        FULL JOIN dbo.pList P ON Result.UserId = P.ID 
        INNER JOIN dbo.pDivision D ON P.Section = D.ID 
        ) AS F
        WHERE F.Division = :grade 
        ORDER BY F.Surname";
        $params['grade'] = $grade;
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        $data = self::addRowNumbers($data);
        return $data;
    }
    
    public static function getStaffAtSchool()
    {
        $params['date'] = date("Ymd",strtotime("-3 days"));
        //КТО В ШКОЛЕ
        $tsql = "
        SELECT F.MaxID, F.UserID, F.Surname, F.Firstname, F.Division, F.Comment, (F.Event+' '+F.EventTime) As Status
        FROM
        (SELECT Result.MaxID, Result.UserId, (convert(varchar, DateTime, 108) +'  ' +convert(varchar, DateTime, 106)) AS EventTime,
        P.Name AS Surname, P.Firstname AS Firstname, D.Name AS Division, P.Address AS Comment,  
        CASE
            WHEN GateLog.Mode=1 THEN 'В школе с'
            WHEN GateLog.Mode=2 THEN 'Отсутствует с'
        END
        AS Event
        FROM 
        (SELECT MAX(id) as MaxID, UserID
        FROM GateLog L 
        WHERE DateTime >=:date 
        GROUP BY UserId) 
        AS Result
        INNER JOIN dbo.GateLog ON  dbo.GateLog.Id=Result.MaxID
        FULL JOIN dbo.pList P ON Result.UserId = P.ID 
        INNER JOIN dbo.pDivision D ON P.Section = D.ID 
        ) AS F 
        WHERE F.Division not LIKE '%[A-O]'
        ORDER BY F.Division, F.Surname";
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        $data = self::addRowNumbers($data);
        return $data;
    }
    
    public static function getDivisionStaffAtSchool($divisionId)
    {
        $params['date'] = date("Ymd",strtotime("-3 days"));
        $params['divisionId'] = $divisionId;
        //КТО В ШКОЛЕ
        $tsql = "
        SELECT F.DivisionId, F.MaxID, F.UserID, F.Surname, F.Firstname, F.Division, F.Comment, (F.Event+' '+F.EventTime) As Status
        FROM
        (SELECT Result.MaxID, Result.UserId, (convert(varchar, DateTime, 108) +'  ' +convert(varchar, DateTime, 106)) AS EventTime,
        P.Name AS Surname, P.Firstname AS Firstname, D.Name AS Division, D.Id As DivisionId, P.Address AS Comment,
        CASE
            WHEN GateLog.Mode=1 THEN 'В школе с'
            WHEN GateLog.Mode=2 THEN 'Отсутствует с'
        END
        AS Event
        FROM 
        (SELECT MAX(id) as MaxID, UserID
        FROM GateLog L 
        WHERE DateTime >=:date 
        GROUP BY UserId) 
        AS Result
        INNER JOIN dbo.GateLog ON  dbo.GateLog.Id=Result.MaxID
        FULL JOIN dbo.pList P ON Result.UserId = P.ID 
        INNER JOIN dbo.pDivision D ON P.Section = D.ID 
        ) AS F 
        WHERE F.DivisionId = :divisionId
        ORDER BY F.Division, F.Surname";
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        $data = self::addRowNumbers($data);
        return $data;
    }
    
    
    public static function getStudentsList($grade)
    {
        $params['grade'] = $grade;
        $tsql = "
        SELECT P.Id AS UserID,  (P.Name +' '+ P.FirstName) AS FIO, D.Name AS Division  FROM dbo.pList P
        FULL JOIN  dbo.pDivision D ON P.Section = D.ID 
        WHERE D.Name=:grade
        ORDER BY FIO";
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        return $data;
    }
    
    
    public static function getStaffList($divisionId)
    {
        $params['divisionId'] = $divisionId;
        $tsql = "
        SELECT P.Id AS UserID,  (P.Name +' '+ P.FirstName) AS FIO, D.Name AS Division  FROM dbo.pList P
        FULL JOIN  dbo.pDivision D ON P.Section = D.ID 
        WHERE D.Id=:divisionId
        ORDER BY FIO";
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        return $data;
    }
    
    
    public static function getPersonLogs($personId,$startDate,$endDate)
    {
        $startDate = str_replace('-', '', $startDate);
        $endDate = str_replace('-', '', $endDate);
        $startDate.= ' 00:00:00';
        $endDate.=' 23:59:59';
        $params['dateStart'] = $startDate;
        $params['dateEnd'] =$endDate;
        $params['personId'] = $personId;
        $tsql = "
        SELECT DateTime AS OrderDate, convert(varchar(20),DateTime,113) AS DateTime, 
        CASE 
        WHEN dbo.GateLog.Mode=1 THEN 'Вход'
        WHEN dbo.GateLog.Mode=2 THEN 'Выход'
        END
        AS Mode  
        FROM dbo.GateLog 
        WHERE UserId = :personId 
        AND DateTime >= :dateStart AND DateTime <= :dateEnd
        ORDER BY OrderDate";
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql,$params);
        $data = self::addRowNumbers($data);
        return $data;
    }
    
    public static function getGeneralData(){
        $tsql = "
        --Возвращает количество работников
        SELECT COUNT(*) AS number FROM dbo.pList P
        INNER JOIN dbo.pDivision D
        ON P.Section = D.ID
        WHERE P.Company = 1
        AND D.Name NOT LIKE '%[A-O]'

        UNION ALL

        --Возвращает количество работников, кто находится в школе
        SELECT COUNT(*) FROM dbo.pList P
        INNER JOIN dbo.pDivision D
        ON P.Section = D.ID
        WHERE P.Company = 1
        AND D.Name NOT LIKE '%[A-O]'
        AND P.IsInside = 1

        UNION ALL

        --Возвращает количество учащихся
        SELECT COUNT(*) FROM dbo.pList P
        INNER JOIN dbo.pDivision D
        ON P.Section = D.ID
        WHERE P.Company = 1 
        AND D.Name LIKE '%[A-O]'

        UNION ALL

        --Возвращает количество учащихся, кто находится в школе
        SELECT COUNT(*) FROM dbo.pList P
        INNER JOIN dbo.pDivision D
        ON P.Section = D.ID
        WHERE P.Company = 1
        AND D.Name LIKE '%[A-O]'
        AND P.IsInside = 1
        
        UNION ALL

        --Возвращает количество учащихся из интерната
        SELECT COUNT(*) FROM dbo.pList P
        INNER JOIN dbo.pDivision D
        ON P.Section = D.ID
        WHERE P.Company = 1 
        AND D.Name LIKE '%[A-O]'
        AND P.Post = 7 

        UNION ALL

        --Возвращает количество учащихся из интерната, кто находится в школе
        SELECT COUNT(*) FROM dbo.pList P
        INNER JOIN dbo.pDivision D
        ON P.Section = D.ID
        WHERE P.Company = 1
        AND D.Name LIKE '%[A-O]'
        AND P.IsInside = 1
        AND P.Post = 7;";

        $db = DbSkd::getInstance();
        $result = $db->execQuery($tsql);

        $staff = $result[0]['number'];
        $staffInside = $result[1]['number'];
       
        $students = $result[2]['number'];
        $studentsInside = $result[3]['number'];

        $studentsDormitory = $result[4]['number'];
        $studentsDormitoryInside = $result[5]['number'];

        $total = $result[0]['number' ] + $result[2]['number'];
        $totalInside = $result[3]['number'] + $result[1]['number'];

        $data = [
                    [ 'who' => 'Сотрудники',
                      'amount' => $staff,
                      'inside' => $staffInside,
                    ],   
                    [ 'who' => 'Учащиеся(город)',
                      'amount' => $students - $studentsDormitory,
                      'inside' => $studentsInside - $studentsDormitoryInside,
                    ],
                    [ 'who' => "Учащиеся(интернат)",
                      'amount' => $studentsDormitory,
                      'inside' => $studentsDormitoryInside,
                    ],
                    [ 'who' => 'Всего',
                      'amount' => $total,
                      'inside' => $totalInside,
                    ],
                ];   
        return $data;
    }

    public static function getGCReport($option1,$option2){
        //$params['date'] = date("Ymd",strtotime("-14 days"));
        
        $who="";
        switch ($option1) {
            case "gcStaff":
                $who= " AND D.Name NOT LIKE '%[A-O]'";
                break;
            case "gcStudents":
                $who= " AND D.Name LIKE '%[A-O]'";
                break;
        }
        
        $where="";
        switch ($option2) {
            case "inside":
                $where= " AND P.IsInside=1";
                break;
            case "outside":
                $where= " AND P.IsInside=2";
                break;
        }

        $tsql = "
        --
        SELECT P.ID,P.Name,P.Firstname,D.Name AS Division, P.Address, convert(varchar(20),P.LogTime,113) As LogTime,
        CASE 
        WHEN P.IsInside=1 THEN 'В школе'
        WHEN P.IsInside=2 THEN 'Отсутствует'
        END
        AS IsInside
        FROM dbo.pList P
        INNER JOIN dbo.pDivision D
        ON P.Section = D.ID
        WHERE Company = 1" . $who . $where . " ORDER BY D.Name,P.Name";

        //var_dump($tsql); die();
        $db = DbSkd::getInstance();
        $data = $db->execQuery($tsql);
        $data = self::addRowNumbers($data);
        return $data;
        

    }

    public static function getDump($who,$where){
        require_once ROOT.'/application/vendor/phpoffice/phpspreadsheet/src/Bootstrap.php';
        $helper = new Sample();
        $spreadsheet = new Spreadsheet();

        // Set document properties
        $spreadsheet->getProperties()->setCreator('Система СКД')
        ->setLastModifiedBy('Система СКД')
        ->setTitle('Выгрузка по сотрудникам и учащимся')
        ->setSubject('Выгрузка по сотрудникам и учащимся')
        ->setDescription('Выгрузка по сотрудникам и учащимся')
        ->setKeywords('office 2007 openxml php')
        ->setCategory('Отчет');

        // Add data from model
        $arrayData = self::getGCReport($who,$where);
        
        // Width for cells
        $spreadsheet->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('D')->setWidth(40);
        $spreadsheet->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('F')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
        $spreadsheet->getActiveSheet()->getRowDimension('1')->setRowHeight(28);

        $spreadsheet->getActiveSheet()->mergeCells('A1:F1');
        $spreadsheet->getActiveSheet()->setCellValue('A1', 'Выгрузка из системы СКД по состоянию на: '. date("d.m.Y H:i:s"));
        //Put headers
        $spreadsheet->getActiveSheet()->setCellValue('A2', '№');
        $spreadsheet->getActiveSheet()->setCellValue('B2', 'Фамилия');
        $spreadsheet->getActiveSheet()->setCellValue('C2', 'Имя');
        $spreadsheet->getActiveSheet()->setCellValue('D2', 'Подразделение');
        $spreadsheet->getActiveSheet()->setCellValue('E2', 'Статус');
        $spreadsheet->getActiveSheet()->setCellValue('F2', 'Последний проход');
        $spreadsheet->getActiveSheet()->setCellValue('G2', 'Комментарий');

        //Put data into cells
        foreach ($arrayData as $elem) {
            $i = $elem['num'] + 2;
            $spreadsheet->getActiveSheet()->setCellValue('A' . $i, $elem['num']);
            $spreadsheet->getActiveSheet()->setCellValue('B' . $i, $elem['Name']);
            $spreadsheet->getActiveSheet()->setCellValue('C' . $i, $elem['Firstname']);
            $spreadsheet->getActiveSheet()->setCellValue('D' . $i, $elem['Division']);
            $spreadsheet->getActiveSheet()->setCellValue('E' . $i, $elem['IsInside']);
            $spreadsheet->getActiveSheet()->setCellValue('F' . $i, $elem['LogTime']);
            $spreadsheet->getActiveSheet()->setCellValue('G' . $i, $elem['Address']);
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

        //styling
        /* 
        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_GRADIENT_LINEAR,
                'rotation' => 90,
                'startColor' => [
                    'argb' => 'FFA0A0A0',
                ],
                'endColor' => [
                    'argb' => 'FFFFFFFF',
                ],
            ],
        ];

        */
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
        $spreadsheet->getActiveSheet()->getStyle('A2:G2')->applyFromArray($styleHeaders);
        $spreadsheet->getActiveSheet()->getStyle('A2:G'.$i)->applyFromArray($styleData);
        $spreadsheet->getActiveSheet()->setAutoFilter('B2:G'.$i);



        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    public static function writeComment($id,$comment){
        $params['id'] = $id;
        $params['comment'] = $comment;
        $tsql = "
        --Обновляет поле Address (записывает туда комментарий) 
        UPDATE dbo.pList
        SET Address = :comment
        WHERE ID = :id";
        $db = DbSkd::getInstance();
        $data = $db->updateQuery($tsql,$params);
    }
}