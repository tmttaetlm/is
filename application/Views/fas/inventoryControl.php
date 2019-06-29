<?php 
    if ($data['canInvStart']==true){ 
        echo '<span>Управление процессом инвентаризации:</span>';
        echo '<button id="startInventory">Начать</button>';
        echo '<button id="stopInventory">Завершить</button><br><br>';
    }
?>

<form id="invExportForm" method="post"  action="/fas/getInvExport" class="but">
    <label for="invReportType">Выберите тип отчета:</label>
    <select name="invReportType">
        <option value="allAssets">Все ОС</option>
        <option value="unscannedAssets">Неотсканированнные ОС</option>
        <option value="movement">Движение ОС</option>
        <option value="people">Сотрудники</option>
    </select> 
</form>
<button id="getInvExport">Сформировать</button>
<br/>
<form id="invTransferAssets" method="post"  action="/fas/invTransferAssets" class="but">
    <label for="invTransmittingPerson">Передать все ОС сотрудника:</label>
    <select name="invTransmittingPerson" id="invTransmittingPerson">
    </select>
    <label for="invReceivingPerson">сотруднику:</label>
    <select name="invReceivingPerson" id="invReceivingPerson">
    </select> 
</form>
<button id="transferAssets">Передать</button>


<div class="autocomplete">
    <label for="invSeachType">Поиск по </label>
    <select id="invSeachType" name="invSeachType">
        <option data-type="invNumber">инвентарному номеру</option>
        <option data-type="person">ФИО</option>
        <option data-type="location">местонахождению</option>
        <option data-type="fixedAsset">основному средству</option>
    </select>
    <input type="text" id="invSeachField" name="invSeachField" placeholder="Поиск...">
    <button name="invSeach">Найти</button>
</div>
<div class="results" id="invResults">
</div>
<div id="dialogWindowBackground">
    <div class="dialogWindow">
        <div class="autocomplete">
                <span id="closeDialogWindow" class="close">X</span>
                <h3>Редактирование ОС</h3>
                <input type="text" id="invChangeOwner" name="invChangeOwner" placeholder="Новый ответственный...">
                <button name="invSaveChanges">Сохранить</button>  
        </div>
    </div>
</div>