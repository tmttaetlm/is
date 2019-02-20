<?php
namespace Components;

/**
* Класс для работы с Active Directory (авторизация через LDAP)
*
*
*/
class Ad
{
    public static $instance;

    private $ldap_host;
    private $ldap_base;
    private $ldap_filter;
    private $ldap_con;

    function __construct()
    {
	//Настройки для подключения к серверу Active Directory
	$this->ldap_host = Config::getParams('AD','host');
	$this->ldap_base = Config::getParams('AD','base');
	$this->ldap_filter = Config::getParams('AD','filter');
        
	//Подключаемся к серверу Active Directory
	$this->ldap_con = ldap_connect($this->ldap_host) or die("Нет соединения с сервером Active Directory");
	ldap_set_option($this->ldap_con, LDAP_OPT_REFERRALS, 0);
	ldap_set_option($this->ldap_con, LDAP_OPT_PROTOCOL_VERSION, 3);
    }

    //Реализация Синглтона с помощью статической функции (не требует создания класса)
    public static function getInstance()
    {
	if (!isset(Ad::$instance))
        {
            Ad::$instance = new Ad();
	}
	return Ad::$instance;
    }
    
    //Функция возвращает массив данных пользователя из AD либо false
    public static function getDataFromAD($login,$password)
    {
        $con = Ad::getInstance();	
	$bind = @ldap_bind($con->ldap_con,$login,$password);
	if ($bind) // Если удалось подключиться - выполняем поиск пользователя с данным логином и паролем
	{
	    $result = ldap_search($con->ldap_con,$con->ldap_base,$con->ldap_filter."=".$login);
	    // Получение данных пользователя из АД
	    $result_ent = ldap_get_entries($con->ldap_con,$result) or die ("Error in search in Active Directory");
            
            // Проверяем наличие и заполненность необходимых полей и заполняем массив
            if (isset($result_ent[0]['nisedukziin'][0],$result_ent[0]['userprincipalname'][0],$result_ent[0]['givenname'][0],$result_ent[0]['sn'][0]))
            {
                $data['iin']=$result_ent[0]['nisedukziin'][0];
                $data['login']=$result_ent[0]['userprincipalname'][0];
                $data['firstName']=$result_ent[0]['givenname'][0];
                $data['lastName']=$result_ent[0]['sn'][0];
                return $data;
            }
            else 
            {
                return false;
            }
	}

        else
        {
            return false;
        } 
    }
}
