<input type = "text" id="newRole" placeholder="Введите название новой роли..." />
<button name="adminAddRole">Добавить</button> <br/>
<select multiple id="roleList" size=9 style="height: auto;">
</select>
<div class="permSettings">
    <?php foreach($data['permissions'] as $permission):?>
        <input type="checkbox" class="perms" id="<?php echo $permission['access'];?>" data-id="<?php echo $permission['id'];?>"/>
        <label for="<?php echo $permission['access'];?>"><?php echo $permission['description'];?></label><br/>
    <?php endforeach;?>
</div>

<br/>
<button name="adminDeleteRole">Удалить выбранную роль</button> <br/>
<hr/>
<label for="deleteOldEntriesDate">Удалить старые записи о проходах до </label>
<input type="date" id="deleteOldEntriesDate" value='2018-01-01'>
<button name="deleteOldEntries">Удалить</button>