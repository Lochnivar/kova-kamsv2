#!/bin/bash
/usr/bin/rm /usr/src/data.txt
sleep 2
sar -n DEV 1 50 >> /usr/src/data.txt
sleep 2
/usr/bin/php /usr/src/datainsert.php
exit

