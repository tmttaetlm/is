<div class="fasUpdateInfo">
<a target="_blank" href="/public/androidApp/NISBarcodeScanner_Instruction.pdf">Инструкция по работе с системой</a>
<p>Скачать мобильное приложение <a href="/public/androidApp/NISBarcodeScanner.apk">NIS Barcode Scanner</a> (для Android) </p>
    <p>Дата начала инвентаризации: <?php echo $data['lastUpdate'];?></p>
    <div class="inventoryMessages">
        <p>Если у Вас нет доступа к ОС - обратитесь к МОЛ.</p>
        <p>Если у Вас нет штрихкода - обратитесь в бухгалтерию.</p>
        <p>Для завершения инвентаризации отсканируйте все штрихкоды.</p>    
    </div>
</div>

<div class="inventoryPanel">
    <div class="inventoryButtons">
        <button name="inventoryUpdate">Обновить</button>
        <button name="inventoryFinish" <?php if($data['inventoryFinished']){ echo 'disabled';}?>>Завершить инвентаризацию</button>
    </div>
</div>


<div class="results" id="inventoryResults">
<?php echo $data['inventoryData'];?>
</div>