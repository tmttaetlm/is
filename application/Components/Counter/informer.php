<?php

require_once ROOT.'/application/Components/Config.php';
require_once ROOT.'/application/Components/Db.php';

class informer {
    public $db;
    public function count() {
        $this->db = Components\Db::getDb();
        $date = getdate();
        if ($date['hours'] < 8) { $date = date("Y-m-d 08:00:00", strtotime("-1 day")); } else { $date = date("Y-m-d 08:00:00"); }
        return $this->db->selectQuery("SELECT views, hosts FROM isdb.counter_visits WHERE date='$date'");
    }
}

$inf = new informer();
$visits = $inf->count();

//echo '<p>Уникальных посетителей: ' . $visits[0]['hosts'] . '<br />';
//echo 'Просмотров: ' . $visits[0]['views'] . '</p>';
?>

<div class="informer">
    <i class="far fa-eye">
        <a style="font: 17px Roboto-Regular;"><?php echo $visits[0]['views']?></a>
    </i>
    <i class="far fa-user" style="margin-left: 5px;">
        <a style="font: 17px Roboto-Regular;"><?php echo $visits[0]['hosts']?></a>
    </i>
</div>