<div id = "results">
<table class="users">
    <tr>
        <th>Фамилия</th>
        <th>Имя</th>
        <th>Логин</th>
        <th>Роль</th>
        <th></th>
    </tr>
        <?php foreach ($data['users'] as $user): ?>
            <tr>
                <td><?php echo $user['lastName'];?></td>
                <td><?php echo $user['firstName'];?></td>
                <td><?php echo $user['login'];?></td>
                <td> 
                    <select name="role" data-iin="<?php echo $user['iin'];?>">
                    <option value="0">Без роли</option>
                        <?php foreach  ($data['roles'] as $role):?>
                            <option value="<?php echo $role['id'];?>" <?php if ($role['id']==$user['roleId']) echo "selected";?>><?php echo $role['name'];?></option>
                        <?php endforeach; ?>
                    </select> 
                </td>
                <td><input type = "button" value="Удалить" name="deleteUser" data-iin="<?php echo $user['iin'];?>"></td>
            </tr>
        <?php endforeach; ?>
</table>
</div>
