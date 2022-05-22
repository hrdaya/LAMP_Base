# マスタ側のmysqlがまだ起動していない場合があるので立ち上がるまで待機
while ! mysqladmin ping -h  mysql_master -u root --password=sample_db --silent; do
    sleep 1
done

# マスタ側のデータをダンプ
mysqldump -u sample_db -h mysql_master --databases sample_db --single-transaction --password=sample_db > /tmp/mysql_master_dump.sql
