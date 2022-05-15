#!/bin/bash

# Set UID of user "vessel"
if [ ! -z "$WWWUSER" ]; then
    usermod -u $WWWUSER vessel
fi

# Apacheをフォアグラウンドで実行
/usr/local/bin/apache2-foreground
