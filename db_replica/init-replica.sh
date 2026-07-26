#!/bin/bash
set -e

MASTER_HOST="${DB_MASTER_HOST:-umamis_db_master}"
MASTER_PORT="${DB_MASTER_PORT:-3306}"
REPL_USER="${REPL_USER:-replicator}"
REPL_PASSWORD="${REPL_PASSWORD:-repl_secret_pwd}"
ROOT_PASSWORD="${MYSQL_ROOT_PASSWORD:-p455w0rd}"

echo "Menunggu master ($MASTER_HOST:$MASTER_PORT) siap menerima koneksi..."
until mysql -h"$MASTER_HOST" -P"$MASTER_PORT" -uroot -p"$ROOT_PASSWORD" -e "SELECT 1" >/dev/null 2>&1; do
  sleep 2
done

echo "Master siap. Mengatur replikasi berbasis GTID..."

mysql -uroot -p"$ROOT_PASSWORD" <<-EOSQL
STOP REPLICA;
RESET REPLICA ALL;
CHANGE REPLICATION SOURCE TO
    SOURCE_HOST='${MASTER_HOST}',
    SOURCE_PORT=${MASTER_PORT},
    SOURCE_USER='${REPL_USER}',
    SOURCE_PASSWORD='${REPL_PASSWORD}',
    SOURCE_AUTO_POSITION=1;
START REPLICA;

-- Baru diaktifkan SETELAH replikasi jalan, supaya tidak ganggu proses init
SET GLOBAL read_only = ON;
SET GLOBAL super_read_only = ON;
EOSQL

echo "Replikasi dikonfigurasi. Cek dengan: SHOW REPLICA STATUS\\G"