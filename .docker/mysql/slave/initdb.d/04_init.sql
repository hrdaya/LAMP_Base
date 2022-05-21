-- レプリケーションの設定

CHANGE MASTER TO MASTER_HOST = 'mysql_master',
MASTER_PORT = 3306,
MASTER_USER = 'sample_db',
MASTER_PASSWORD = 'sample_db',
MASTER_AUTO_POSITION = 1;

-- レプリケーションを開始

START SLAVE;
