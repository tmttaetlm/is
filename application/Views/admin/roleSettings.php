<input type = "text" id="newRole" placeholder="Введите название новой роли..." />
<button name="adminAddRole">Добавить</button> <br/>
<select multiple id="roleList" size=5>
</select>
<div>
    <?php foreach($data['permissions'] as $permission):?>
        <input type="checkbox" class="perms" id="<?php echo $permission['access'];?>" data-id="<?php echo $permission['id'];?>"/>
        <label for="<?php echo $permission['access'];?>"><?php echo $permission['description'];?></label><br/>
    <?php endforeach;?>
</div>

<br/>
<button name="adminDeleteRole">Удалить выбранную роль</button> <br/>