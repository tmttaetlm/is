<?php
namespace Core;

use Controllers\Main;

/*
    Class Router - analizes the query, determines the controller and action
*/

class Router
{
    public function start()
    {
    //Controller and action by default
    $controllerName = 'Main';
    $actionName = 'Index';

    //Gets the controller and action from query string
    $routes = explode ('/', $_SERVER['REQUEST_URI']);

    if (!empty($routes[1]))
    {
        $controllerName = strtolower($routes[1]);
    }

    if (!empty($routes[2]))
    {
        $actionName = strtolower($routes[2]);
    }
    
    //Format controller and action name
    $controllerName = ucfirst($controllerName).'Controller';
    $actionName = 'action'.ucfirst($actionName);

    //Connects the controller file
    $controllerFile = ROOT.'/application/Controllers/'.$controllerName.'.php';

    if (file_exists($controllerFile))
    {
        require_once $controllerFile;
    }
    else
    {
        echo 'NO CONTROLLER: '.$controllerFile; exit;//!!!!!!!!Here need to add error control!!!
    }

    //Adds the Namespace
    $controllerName = 'Controllers\\'.$controllerName;

    //Creates Controller object
    $controller = new $controllerName;
    
    //Call the action
    if (method_exists($controller,$actionName))
    {
        $controller->$actionName(); 
    }
    else
    {
        echo 'NO METHOD:  '.$actionName; exit;//Here need to add error control!!!
    }


    }
}