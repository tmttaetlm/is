<div class="fasUpdateInfo">
<p>Перед началом работы обязательно ознакомьтесь с инструкцией: <a target="_blank" href="/public/androidApp/NISBarcodeScanner_Instruction.pdf">Инструкция по работе с системой</a></p>
<p>Скачать мобильное приложение <a href="/public/androidApp/NISBarcodeScanner.apk">NIS Barcode Scanner</a> (для Android) </p>
    <p>Дата начала инвентаризации: <?php echo $data['inventoryStartedAt'];?></p>
</div>

<div class="inventoryPanel">
    <div class="inventoryButtons">
        <button name="inventoryUpdate">Обновить</button>
        <button name="inventoryFinish" id="inventoryFinish" <?php if($data['inventoryFinished']){ echo 'disabled';}?>>Завершить инвентаризацию</button>
    </div>
</div>


<div class="results" id="inventoryResults">
<?php echo $data['inventoryData'];?>
</div>