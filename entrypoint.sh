#!/bin/bash

SERVER_NAME=confmapper

echo "Dealing with certificate files"
if [ ! -f  /etc/apache2/apache.pem ]
then
    echo "Generate self signed certificate for apache"
    openssl req -x509 -newkey rsa:4096 -keyout key.pem -out cert.pem -days 10000 -nodes -subj '/CN='$SERVER_NAME
    cat key.pem cert.pem > /etc/apache2/apache.pem
    rm key.pem cert.pem
fi

# Init local DataBase
python3 /usr/local/ConferenceMapper/src/policyserver/scripts/createDb.py

#Start Apache
/usr/sbin/apache2ctl -DFOREGROUND
