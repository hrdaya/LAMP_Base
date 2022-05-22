<?php

require __DIR__.'/../vendor/autoload.php';

use Libs\Support\Carbon as LibsCarbon;
use Carbon\Carbon;
use Dotenv\Dotenv;

// Dotenvファイルの読み込み
Dotenv::createImmutable(realpath(__DIR__ . '/..'))->load();

// 開発環境で一時的に日時を変更したい場合にCarbonの日時を変更する
$now = trim($_ENV['DEBUG_DATETIME']);
if ($now && $_ENV['APP_ENV'] === 'local') {
    if (\count(explode(' ', $now)) === 1) {
        // 時間がセットされていない場合は現在時間を付け足す
        $now .= ' '.now()->format('H:i:s.u');
    }

    // Carbonの日付を変更する
    LibsCarbon::setTestNow($now);
}

// .envのDEBUG_DATETIME確認
dump(Carbon::now()->format('Y-m-d H:i:s'));

// カーボンに追加したマクロ確認
dump(Carbon::make(NULL));
dump(Carbon::make('2000-01-01 12:34:56')->format('Y-m-d H:i:s'));

// Redisの読み書き確認
$redis = new Redis();
$redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']);

$redis->set('key', json_encode([
    'key1' => 'value1',
    'key2' => 'value2',
]));

dump(json_decode($redis->get('key'), true));

$redis->close();

// メール送信でMailhogに飛んでいるかの確認
mail(
    'test@example.com',
    'テストメール',
    'メール本文',
    [
        'From'      => 'from@example.com',
        'Replay-To' => 'replay@example.com',
    ]
);

// phpinfoの出力
phpinfo();
