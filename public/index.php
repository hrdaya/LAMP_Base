<?php

use App\Libs\Date\Support\Carbon;

phpinfo();

(new Dotenv\Dotenv(__DIR__ . '/..'))->load();

// 開発環境で一時的に日時を変更したい場合にCarbonの日時を変更する
$now = getenv('DEBUG_DATETIME');
if ($now && getenv('APP_ENV') === 'local') {
    if (\count(explode(' ', $now)) === 1) {
        // 時間がセットされていない場合は現在時間を付け足す
        $now .= ' '.now()->format('H:i:s.u');
    }

    // Carbonの日付を変更する
    Carbon::setTestNow($now);
}
