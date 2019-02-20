<p class="fasUpdateInfo">Согласно сведениям из ИС 1С:Бухгалтерия. Последняя синхронизация: <?php echo $data['lastUpdate'];?></p>
<div class="autocomplete">
    <label for="fasSeachType">Поиск по </label>
    <select id="fasSeachType" name="fasSeachType">
        <option data-type="invNumber">инвентарному номеру</option>
        <option data-type="person">ФИО</option>
        <option data-type="location">местонахождению</option>
        <option data-type="fixedAsset">основному средству</option>
        <option data-type="barcode">штрих-коду</option>
    </select>
    <input type="text" id="seachField" name="seachField" placeholder="Поиск...">
    <button name="fasSeach">Найти</button>
</div>
<div class="results" id="results">
</div>