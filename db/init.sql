-- Dijalankan otomatis saat container db_umamis_master pertama kali start
CREATE USER IF NOT EXISTS 'replicator'@'%'
    IDENTIFIED WITH mysql_native_password BY 'repl_secret_pwd';

GRANT REPLICATION SLAVE ON *.* TO 'replicator'@'%';

FLUSH PRIVILEGES;