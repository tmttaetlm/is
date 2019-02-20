<ul>
    <?php foreach ($data as $menuName=>$menuItem):?>
    <li><button name="<?php echo $menuName?>"><?php echo $menuItem?></button></li>
    <?php endforeach;?>
</ul>
