#!/bin/bash
#Created by Vladimir Martynenko

echo "----------------------------------------------"
date
echo "Монтируем удаленный каталог с выгрузкой из 1С"
mount //10.1.0.34/1C_Export/Fixed_assets /var/www/is/application/mnt -o user=share_access.kst,domain=nis.edu.kz,password=Pass@KST2
#mount //10.1.0.34/1C_Export/Fixed_assets /home/developer/Code/PHP/is/application/mnt -o user=share_access.kst,domain=nis.edu.kz,password=Pass@KST2

if grep -qs '/home/developer/Code/PHP/is/application/mnt' /proc/mounts; then
    echo "Удаленный каталог успешно примонтирован, запускаем php скрипт для загрузки данных"
    php /var/www/is/application/Components/Fas/sync.php
	umount /var/www/is/application/mnt
    #php /home/developer/Code/PHP/is/application/Components/Fas/sync.php
	#umount /home/developer/Code/PHP/is/application/mnt
	echo "Удаленный каталог успешно отмонтирован"

else
    echo "Не удалось примонтировать удаленный каталог"
fi