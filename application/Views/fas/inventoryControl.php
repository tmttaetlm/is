<form id="invExportForm" method="post"  action="/fas/getInvExport" class="but">
</form>
<button id="getInvExport">Выгрузить в Exel</button>

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