#!/bin/bash
#Created by Vladimir Martynenko
#path="/var/www/is/application"
path="/home/developer/Code/PHP/is"

echo "----------------------------------------------"
date
echo "Монтируем удаленный каталог с выгрузкой из 1С"
mount //10.1.0.34/1C_Export/Fixed_assets $path'/application/mnt' -o user=share_access.kst,domain=nis.edu.kz,password=Pass@KST2

if grep -qs $pat'/application/mnt' /proc/mounts; then
    echo "Удаленный каталог успешно примонтирован, запускаем php скрипт для загрузки данных"
    php $path'/application/Components/Fas/sync.php'
	umount $path'/application/mnt'
	echo "Удаленный каталог успешно отмонтирован"

else
    echo "Не удалось примонтировать удаленный каталог"
fi