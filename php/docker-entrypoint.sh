#!/bin/bash
set -e

# Tujuan file env di dalam container
ENV_FILE="/var/www/html/.env"

# ──────────────────────────────────────────────────────────
# Loop XDEBUG, PHP_IDE_CONFIG, REMOTE_HOST dari .env jika belum di-set
# ──────────────────────────────────────────────────────────
for VAR in XDEBUG PHP_IDE_CONFIG REMOTE_HOST
do
  if [ -z "${!VAR}" ] && [ -f "${ENV_FILE}" ]; then
    VALUE=$(grep "^${VAR}=" "$ENV_FILE" | cut -d '=' -f 2- || true)
    if [ -n "${VALUE}" ]; then
      sed -i "/$VAR/d" ~/.bashrc
      echo "export $VAR=$VALUE" >> ~/.bashrc
    fi
  fi
done

# Source bashrc jika ada (hindari error saat non-interactive shell)
if [ -f ~/.bashrc ]; then
  . ~/.bashrc
fi

# Default REMOTE_HOST untuk Windows/Mac (Docker Desktop)
if [ -z "${REMOTE_HOST}" ]; then
  REMOTE_HOST="host.docker.internal"
  sed -i "/REMOTE_HOST/d" ~/.bashrc 2>/dev/null || true
  echo "export REMOTE_HOST=\"$REMOTE_HOST\"" >> ~/.bashrc
  . ~/.bashrc
fi

# ──────────────────────────────────────────────────────────
# Jalankan cron service
#
# CATATAN PERBAIKAN:
#   'service cron start' butuh package 'cron' (sudah ter-install
#   di Dockerfile) DAN systemd-style init. Di base image php:8.3-fpm
#   (Debian slim) 'service' tersedia via sysvinit-utils bawaan,
#   tapi command ini sering silent-fail jika /etc/cron.d kosong
#   permission-nya salah. Ditambahkan fallback langsung ke cron binary.
# ──────────────────────────────────────────────────────────
service cron start 2>/dev/null || cron

# ──────────────────────────────────────────────────────────
# Toggle Xdebug
#
# CATATAN PERBAIKAN BESAR:
#   Setting asli ('xdebug.remote_enable', 'xdebug.remote_autostart',
#   'xdebug.remote_connect_back', 'xdebug.remote_host') adalah
#   SINTAKS XDEBUG 2.x. Di Xdebug 3.x (yang kita install: 3.3.2)
#   semua nama config tersebut SUDAH TIDAK VALID dan akan
#   menyebabkan PHP gagal start dengan warning "Unknown directive".
#   Diganti ke sintaks Xdebug 3.x yang benar.
# ──────────────────────────────────────────────────────────
XDEBUG_INI="/usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini"

if [ "true" == "$XDEBUG" ] && [ ! -f "$XDEBUG_INI" ]; then
  sed -i '/PHP_IDE_CONFIG/d' /etc/cron.d/laravel-scheduler 2>/dev/null || true
  if [ -n "${PHP_IDE_CONFIG}" ]; then
    echo -e "PHP_IDE_CONFIG=\"$PHP_IDE_CONFIG\"\n$(cat /etc/cron.d/laravel-scheduler)" > /etc/cron.d/laravel-scheduler
  fi

  docker-php-ext-enable xdebug

  {
    echo "xdebug.mode=develop,debug"
    echo "xdebug.start_with_request=yes"
    echo "xdebug.client_host=$REMOTE_HOST"
    echo "xdebug.client_port=9003"
    echo "xdebug.discover_client_host=false"
  } >> "$XDEBUG_INI"

elif [ -f "$XDEBUG_INI" ] && [ "true" != "$XDEBUG" ]; then
  sed -i '/PHP_IDE_CONFIG/d' /etc/cron.d/laravel-scheduler 2>/dev/null || true
  rm -f "$XDEBUG_INI"
fi

# ──────────────────────────────────────────────────────────
# Pastikan permission storage & cache benar (umum bikin 500 error)
# ──────────────────────────────────────────────────────────
if [ -d /var/www/html/storage ]; then
  chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
fi

exec "$@"