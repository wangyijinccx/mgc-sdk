#!/bin/bash
git pull
chmod 777 -R ./admin/runtime/
chmod 777 -R ./agent/runtime/
chmod 777 -R ./mobile/runtime/
chmod 777 -R ./float/runtime/
chmod 777 -R ./tp/runtime/
chmod 777 -R ./web/runtime/

#systemctl restart php-fpm
#systemctl restart nginx.service
