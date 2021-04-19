<?php

require_once ROOT.'/application/Components/Config.php';
require_once ROOT.'/application/Components/Db.php';

class counter {
    public $db;

    public function count() {
        $this->db = Components\Db::getDb();

        // Получаем IP-адрес посетителя и сохраняем текущую дату
        $visitor_ip = $_SERVER['REMOTE_ADDR'];
        $date = getdate();

        if ($date['hours'] < 8) { $date = date("Y-m-d 08:00:00", strtotime("-1 day")); } else { $date = date("Y-m-d 08:00:00"); }

        // Узнаем, были ли посещения за сегодня
        $visits = $this->db->selectQuery("SELECT * FROM isdb.counter_visits WHERE date='$date'");
        // Узнаем, были ли посещения за сегодня
        $ips = $this->db->selectQuery("SELECT * FROM isdb.counter_ips WHERE address='$visitor_ip'");
        
        // Если сегодня еще не было посещений
        if (count($visits) == 0)
        {
            // Заносим в базу дату посещения и устанавливаем кол-во просмотров и уник. посещений в значение 1
            $this->db->IUDquery("INSERT INTO isdb.counter_visits (date, hosts, views) VALUES ('$date', 1, 1)");
        } else {
            // Если такой IP-адрес уже сегодня был (т.е. это не уникальный посетитель)
            if (count($ips) != 0)
            {
                // Добавляем для текущей даты +1 просмотр (хит)
                $this->db->IUDquery("UPDATE isdb.counter_visits SET views=views+1 WHERE date='$date'");
            }
            // Если сегодня такого IP-адреса еще не было (т.е. это уникальный посетитель)
            else
            {
                // Заносим в базу IP-адрес этого посетителя
                $this->db->IUDquery("INSERT INTO isdb.counter_ips (address) VALUES ('$visitor_ip')");
                // Добавляем в базу +1 уникального посетителя (хост) и +1 просмотр (хит)
                $this->db->IUDquery("UPDATE isdb.counter_visits SET hosts=hosts+1, views=views+1 WHERE date='$date'");
            }
        }
    }
}

$cnt = new counter();
$cnt->count();

?>