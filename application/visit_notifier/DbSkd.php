<?php

use PDO;

/**
* Класс для работы с БД MSSQL сервера СКД
*/
class DbSkd {

    public static $instance; // объект текущего класса
    private $db; // объект класса PDO

    function __construct()
    {
        $serverName = '10.14.24.105,1443';
        $dataBase = 'Orion';
        $uid = 'isWeb';
        $pwd = 'ManGust2112';

        try
        {
            $this->db = new PDO( "sqlsrv:server=$serverName;Database = $dataBase", $uid, $pwd);
            $this->db->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
            $this->db->setAttribute( PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC );
        }
            catch( PDOException $e )
        {
            die("Error connecting to SQL Server ".$e);
        }
        
    }

    //Реализация Singltone для получения одного соединения 
    public static function getInstance()
    {
        if (!isset(self::$instance))
                {
                    self::$instance = new self();
                }
        //print_r('getInstance<br>');
        return self::$instance;
    }

    /*Выполнениет запрос к базе данных 
    * @param строка $tsql - SQL запрос
    * @param массив $params - данные для плейсхолдеров
    * return массив - данные полученные из БД в результате выполнения запроса
    */
    public function execQuery($tsql,$params = null)
    {
        try
        {
            $stmt = $this->db->prepare($tsql);
            if ($params)
            {
                foreach ($params as $key => $value)
                {
                    $bindKey = ':' . $key;
                    $stmt->bindValue($bindKey, $params[$key]);
                }
            }
            $stmt->execute();
            return $stmt->fetchAll( PDO::FETCH_ASSOC );
        }

        catch(Exception $e)
        {
            die( print_r( $e->getMessage() ) );
        }
        //print_r('execQuery<br>');
    }

    public function updateQuery($tsql,$params){
        try{
            $stmt = $this->db->prepare($tsql);
            foreach ($params as $key => $value)
            {
                $bindKey = ':' . $key;
                $stmt->bindValue($bindKey, $params[$key]);
            }
            $stmt->execute();
        }
        catch(Exception $e)
        {
            die( print_r( $e->getMessage() ) );
        }
        //print_r('updateQuery<br>');
    }
}
