<?php
namespace Components;

/* Класс Config - загружает настройки системы из файла config.ini
 * затем к ним можно обратиться через функцию getParams
*/

class Config
{
    public static $instance;
    private $params;
	
    //Получаем настройки из файла и загружаем в массив
    private function __construct()
    {
	$this->params = parse_ini_file(ROOT.'/application/config.ini',true);
    }

    //Реализация Синглтона с помощью статической функции (не требует создания класса)
    public static function getInstance()
    {
	if (!isset(Config::$instance))
            {
		Config::$instance = new Config();
            }
        return Config::$instance;
	}

    //Получение параметров
    public static function getParams($section,$property)
    {
	$cfg = Config::getInstance();
	return $cfg->params[$section][$property]; 
    }

}