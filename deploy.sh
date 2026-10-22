#!/usr/bin/env bash
#
# deploy.sh — atualiza o Sentinel depois de um git pull (dev ou servidor Linux).
# Uso, na raiz do projeto:  bash deploy.sh
#
set -e

# Carrega o nvm/node (caso o shell não tenha o Node no PATH)
export NVM_DIR="$HOME/.nvm"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

echo "==> 1/6 Atualizando código (git pull)"
git pull

echo "==> 2/6 Dependências PHP (composer install)"
composer install --no-interaction --prefer-dist

echo "==> 3/6 Migrations (php artisan migrate)"
php artisan migrate --force

echo "==> 4/6 Dependências do front (npm install)"
npm install

echo "==> 5/6 Build do front (npm run build)"
npm run build

echo "==> 6/6 Limpando caches (optimize:clear)"
php artisan optimize:clear

echo ""
echo "✅ Deploy concluído."
echo "ℹ️  Para popular/atualizar as cotações, rode o coletor:"
echo "   cd scripts/python && ./.venv/bin/python fetch_stocks.py"
