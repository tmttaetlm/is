<?php
/* 
* System loader 
*/
use Core\Router;
use Components\Config;

//Turn on to show all errors!!!
ini_set('display_errors',1);
error_reporting(E_ALL);

//Set default time zone
date_default_timezone_set('Asia/Almaty');

//Autoload function
function systemAutoload($className)
{
    //Split class name (for using Namespace)
    $class_pieces = explode('\\', $className);

    //Creates the path to the file and require the file
    require_once __DIR__.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $class_pieces).'.php';
}
spl_autoload_register('systemAutoload');

//Constant ROOT stores the system root directory
define('ROOT', $_SERVER['DOCUMENT_ROOT']);

//Start session
session_start();

//Calls the Router
$router = new Router();
$router->start();
