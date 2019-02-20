<?php

namespace Models;
use Core\Model;

class MainModel extends Model {
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function getData()
    {
        $data['admin'] = $this->user->checkAdmin();
        $data['user'] = $this->user->getFullName();
        return $data;
    }
}
