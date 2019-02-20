<?php
namespace Core;
/*
Base Model
*/

class Model
{
    public $user;
    protected $view;
    
    public function __construct() {
        $this->user = new User;
    }
    
    //Adds numbering to an array
    public static function addRowNumbers($data) {
        $c=count($data);
        for($i=0;$i<$c;$i++) {
            $data[$i]['num'] = $i+1;
        }
        return $data;
    }
    
}
