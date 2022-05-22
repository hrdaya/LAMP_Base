# スレーブ側にリストア
mysql -u root -h localhost --password=sample_db < /tmp/mysql_master_dump.sql
