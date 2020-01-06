<?php
namespace Core;

/*
Base View
*/


class View
{
    //generates HTML code from data and template file
    public function generate($templateView, $data = []) 
    {
        ob_start();
        require ROOT.'/application/Views/'.$templateView.'.php';
        return ob_get_clean();
    }
       
    public function cTable($caption,$columns,$tableData,$class = null)
    {
       $data['caption'] = $caption;
       $data['columns'] = $columns;
       $data['tableData'] = $tableData;
       $data['class'] = $class;
       return $this->generate('framework/table', $data);
    }
    
    public function getDataByTabItems($tabItems,$system)
    {
        foreach ($tabItems as $key=>$value) {
            $data[$key] = $this->generate($system.'/'.$key);
        }
        return $data;
    }
    
    public function createSelectOptions($data,$field,$id)
    {
        $result = '';
        foreach ($data as $row) {
            $result .= '<option data-id ="'.$row[$id].'">'.$row[$field]."</option>\n";
        }
        return $result;
    }
    
    
}