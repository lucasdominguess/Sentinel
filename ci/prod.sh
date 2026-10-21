#!/bin/bash

directory=base_api_php

echo "Iniciando atualização em produção..."

# Atualiza dependências local (opcional se quiser validar antes)
composer install
composer du

# Sincroniza arquivos para o servidor
sudo rsync -hva \
--exclude='.env' \
--exclude='.htaccess' \
--exclude='vendor' \
--exclude='node_modules' \
--exclude='.git' \
--exclude='storage/*' \
--exclude='bootstrap/cache/*' \
--exclude='docker-compose.yml' \
--exclude='Dockerfile' \
--delete-after . /var/www/html/$directory

cd /var/www/html/$directory

# Ajusta permissões
sudo chown -R www-data:www-data .

# Instala dependências no servidor
sudo -u www-data composer install --no-dev --optimize-autoloader

# Limpa e otimiza Laravel
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan optimize

# Reinicia serviço
sudo systemctl restart sgopm.service

echo "Atualização completa em produção"
ls -lA
echo "Atualização realizada em:"
pwd
