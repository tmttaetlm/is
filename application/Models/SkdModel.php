<?php

namespace Models;
use Core\Model;
use Components\DbSkd;

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

        SELECT Et.UserId, P.Name AS Surname, P.Firstname AS Firstname, 
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

        SELECT Et.UserId, P.Name AS Surname, P.Firstname AS Firstname, 
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
        SELECT F.DivisionId, F.MaxID, F.UserID, F.Surname, F.Firstname, F.Division, (F.Event+' '+F.EventTime) As Status
        FROM
        (SELECT Result.MaxID, Result.UserId, (convert(varchar, DateTime, 108) +'  ' +convert(varchar, DateTime, 106)) AS EventTime,
        P.Name AS Surname, P.Firstname AS Firstname, D.Name AS Division, D.Id As DivisionId,
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
    
}