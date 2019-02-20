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
        
//    //Функция создания таблицы, возвращает готовый HTML код со вставленными данными
//    public function createTable($headers,$fields,$data,$title,$class = null) 
//    {
//        if ($class) {
//                $result = "<table class='$class'>\n<caption>$title</caption>\n<tr>\n";
//        } else {
//                $result = "<table>\n<caption>$title</caption>\n<tr>\n";
//        }
//        
//        //Вставляем заголовки полей
//	for ($i=0;$i<count($headers);$i++) {
//            $result.= '<th>'.$headers[$i]."</th>\n";
//	}
//       
//        $result.="</tr>\n";
//
//	//Вставляем значения
//	foreach ($data as $row) {
//            $result.="<tr>\n";
//            for ($i=0;$i<count($fields);$i++) {
//                $result.= '<td>'.$row[$fields[$i]]."</td>\n";
//            }
//            $result.="</tr>\n";	
//	}
//	$result.="</table>\n";
//	
//	return $result;
//    }
    
    
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