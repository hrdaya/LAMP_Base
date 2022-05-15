# Apache、MySQL、PHP、Node.js、RedisなDocker

## 適用しているミドルウェア

- Apache
- PHP
- Node
- MySQL
- Redis

## 特徴

- Dockerコンテナ操作用のシェルを使用することによりコマンドの簡易化
- WEBサーバの実行ユーザのUID、GIDを実行環境のUID、GIDにセット
- MySQLのVolumeのパーミッションに対応済み
- PHPのセッションはPhpRedisを使用してRedisに保存
- phpMyAdmin適用
- Mailhog適用

## Docker用コマンド

Dockerコンテナ内のUIDを実行環境と揃える為に`vessel`や`sail`を元に作成した下記のコマンドから各種コマンドを実行します。

### コマンドのヘルプ

実行できるコマンドの一覧と各種リンクを表示します。

```sh
bash ./vessel help
```

### 各種リンクを表示

- WEB
- phpMyAdmin
- Mailhog

の各サーバへのリンクを表示します。

```sh
bash ./vessel links
```

### コンテナの起動

コンテナを起動します。

```sh
bash ./vessel start
```

### コンテナの停止

コンテナを停止します。

```sh
bash ./vessel stop
```

### Docker Compose で作ったコンテナ、イメージ、ボリューム、ネットワークを一括完全消去

docker-compose.yml で作成した内容を一括削除します。

```sh
bash ./vessel destroy
```

### コンテナの再構築

コンテナをキャッシュ無しで再構築します。

```sh
bash ./vessel rebuild
```

### Docker Compose のログ表示

ログを`tail`コマンドのようにWatchしながら出力します。

第一引数の行数は各コンテナのログの最終行から遡った行数をセットします。  
セットしない場合のデフォルト値は`100`です。

```sh
bash ./vessel logs <行数>
```

### コンテナ内で PHP を実行

WEBコンテナ内でPHPファイルを実行します。

```sh
bash ./vessel php <file> <option>
```

### コンテナ内で Composer コマンドを実行

WEBコンテナ内で Composer コマンドを実行します。

```sh
bash ./vessel composer <cmd>
```

### コンテナ内で composer dump-autoload を実行

WEBコンテナ内で `composer dump-autoload` を実行します。

```sh
bash ./vessel dump
```

### コンテナ内でテストを実行

WEBコンテナ内で PHPUnit を実行します。

```sh
bash ./vessel test <options>
```

### コンテナの Node.js のバージョンを表示

WEBコンテナ内の Node.js のバージョンを表示します。

```sh
bash ./vessel node
```

### コンテナ内で npm コマンド実行

WEBコンテナ内で npm コマンドを実行します。

```sh
bash ./vessel npm <cmd>
```

### コンテナ内で npx コマンド実行

WEBコンテナ内で npx コマンドを実行します。

```sh
bash ./vessel npx <cmd>
```

### WEBコンテナ内にshellログイン

WEBコンテナ内にshellログインします。

```sh
bash ./vessel ssh
```

### WEBコンテナに root ユーザで shell ログイン

WEBコンテナ内にrootユーザでshellログインします。

```sh
bash ./vessel root
```

### MySQLコンテナのMySQLにログイン

DBコンテナのMySQLにログインします。

```sh
bash ./vessel mysql
```

### RedisコンテナのRedisにログイン

RedisコンテナのRedisにログインします。

```sh
bash ./vessel redis
```
