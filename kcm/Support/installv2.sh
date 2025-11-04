#!/bin/bash                # Tells the computer: "Use Bash to run this!"  


# Step 1. Copy code to directory

cp -r KCM /usr/src/KCM

# Step 2. Import Database

cat v2-tables.sql | mysql 

# Step 3. Copy Service File

cp /usr/src/KCM/src/Modules/Systemd/kcm-cron.service /etc/systemd/system/kcm-cron.service

# Step 4. Enable Service File

systemctl daemon-reload

systemctl enable kcm-cron.service

systemctl start kcm-cron.service