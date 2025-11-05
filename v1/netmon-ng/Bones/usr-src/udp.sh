#!/bin/bash
/usr/bin/rm /usr/src/udp-data.txt
sleep 2
sar -n UDP 1 50 >> /usr/src/udp-data.txt
sleep 2
/usr/bin/php /usr/src/udpinsert.php
exit

