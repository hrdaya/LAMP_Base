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
- Adminer適用
- RedisInsight適用
- Mailhog適用

## Docker用コマンド

Dockerコンテナ内のUIDを実行環境と揃える為に`vessel`や`sail`を元に作成した下記のコマンドから各種コマンドを実行します。

使用できるコマンドは下記のヘルプを表示するコマンドから確認してください。

```sh
bash ./vessel help
```
