#!/usr/bin/env bash
set -euo pipefail

# Bootstrap script for a fresh Hetzner Ubuntu 24.04 host.
#
# This intentionally installs only the infrastructure fwber currently needs in
# active runtime: Nginx, PHP-FPM, MySQL, Redis, Node, Composer, Rust, and the
# tools required to manage TLS plus long-running services.

export DEBIAN_FRONTEND=noninteractive

apt update
apt upgrade -y
apt install -y software-properties-common curl git unzip zip nginx supervisor fail2ban ufw certbot python3-certbot-nginx

add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-mysql php8.4-curl php8.4-mbstring php8.4-xml php8.4-zip php8.4-bcmath php8.4-intl php8.4-gd php8.4-redis php8.4-opcache

apt install -y mysql-server redis-server

curl -fsSL https://deb.nodesource.com/setup_lts.x | bash -
apt install -y nodejs

cd /tmp
curl -sS https://getcomposer.org/installer -o composer-setup.php
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f composer-setup.php

if ! id -u deploy >/dev/null 2>&1; then
  adduser --disabled-password --gecos "" deploy
  usermod -aG sudo deploy
fi

mkdir -p /var/www/fwber
chown -R deploy:deploy /var/www/fwber

ufw allow OpenSSH
ufw allow 80
ufw allow 443
ufw --force enable

systemctl enable nginx redis-server mysql
systemctl start nginx redis-server mysql

echo "Bootstrap complete. Next: clone the repo, configure .env, and install the files from ops/hetzner/."
