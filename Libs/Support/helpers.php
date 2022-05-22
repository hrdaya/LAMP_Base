<?php

declare(strict_types=1);

use Carbon\Carbon;
use Carbon\CarbonImmutable;

// Carbon::parse($time)で＄timeにNULLを渡すと現在時刻のCarbonインスタンスが返ってくるためNULLのときはNULLを返すようにするマクロ
if (!Carbon::hasMacro('make')) {
    Carbon::macro('make', static function ($time = null, $tz = null) {
        return $time === null ? null : self::this()->parse($time, $tz);
    });
}

// CarbonImmutable::parse($time)で＄timeにNULLを渡すと現在時刻のCarbonImmutableインスタンスが返ってくるためNULLのときはNULLを返すようにするマクロ
if (!CarbonImmutable::hasMacro('make')) {
    CarbonImmutable::macro('make', static function ($time = null, $tz = null) {
        return $time === null ? null : self::this()->parse($time, $tz);
    });
}

if (!function_exists('now')) {
    /**
     * Create a new Carbon instance for the current time.
     *
     * @param null|\DateTimeZone|string $tz
     *
     * @return \Carbon\Carbon
     */
    function now($tz = null)
    {
        return Carbon::now($tz);
    }
}

if (!function_exists('dump')) {
    /**
     * dumpを整形して出力
     *
     * @param mixed $value 出力する値
     *
     * @return void
     */
    function dump($value)
    {
        echo '<pre class="xdebug">';
        var_dump($value);
        echo '</pre>';
    }
}

if (!function_exists('dd')) {
    /**
     * dumpを整形して出力し、処理を終了
     *
     * @param mixed $value 出力する値
     *
     * @return void
     */
    function dd($value)
    {
        dump($value);
        exit;
    }
}

if (!function_exists('normalize_filename')) {
    /**
     * ファイル名に使用出来ない文字を削除して返す.
     *
     * @see http://msdn.microsoft.com/en-us/library/aa365247%28VS.85%29.aspx
     */
    function normalize_filename(string $fileName): string
    {
        $ranges        = range(0, 31);
        $ranges[]      = 127;
        $nonePrintings = array_map('chr', $ranges);
        $invalidChars  = ['<', '>', ':', '"', '/', '\\', '|', '?', '*', '&'];
        $allInvalids   = array_merge($nonePrintings, $invalidChars);

        return str_replace($allInvalids, '', $fileName);
    }
}

if (!function_exists('replace_hyphen')) {
    /**
     * ハイフンに似ている横棒を全て半角マイナス(U+002D)に変換する.
     *
     * @see https://qiita.com/non-caffeine/items/77360dda05c8ce510084
     *
     * @param string $str              変換する値
     * @param string $hyphen           変換する記号(デフォルトは半角マイナス(U+002D))
     * @param bool   $includeLongVowel trueのときは長音(U+30FC)を含める(デフォルトtrue)
     */
    function replace_hyphen(string $str, string $hyphen = '-', bool $includeLongVowel = true): string
    {
        $hyphens = '--˗ᅳ᭸‐‑‒–—―⁃⁻−▬─━➖ㅡ﹘﹣－ｰ𐄐𐆑 '.($includeLongVowel ? 'ー' : '');

        return str_replace($hyphens, $hyphen, $str);
    }
}

if (!function_exists('normalize_phone_number')) {
    /**
     * ハイフンに似ている横棒を全て半角マイナスに変換し、全角数字を半角数字に変換する.
     */
    function normalize_phone_number(?string $str): ?string
    {
        return mb_convert_kana(replace_hyphen($str), 'n', 'utf-8');
    }
}

if (!function_exists('array_is_list')) {
    /**
     * 配列が連想配列でないことを判定.
     *
     * PHP8.1からはネイティブに実装済み
     *
     * @see https://qiita.com/rana_kualu/items/4363107370f508717851
     *
     * @phpstan-ignore-next-line
     */
    function array_is_list(array $array): bool
    {
        $expectedKey = 0;
        foreach (array_keys($array) as $i) {
            if ($i !== $expectedKey) {
                return false;
            }
            ++$expectedKey;
        }

        return true;
    }
}

if (!function_exists('array_merge_unique')) {
    /**
     * 配列をマージして値をユニークにする.
     *
     * @phpstan-ignore-next-line
     */
    function array_merge_unique(array ...$arrays): array
    {
        // 配列をマージする
        $array = array_merge(...$arrays);

        // 値をユニークにしてキーの歯抜けを直す
        return array_values(array_unique($array));
    }
}

if (!function_exists('is_filled')) {
    /**
     * 空でない文字列か数値かどうか.
     *
     * クエリビルダのwhenなどに使用する
     */
    function is_filled(mixed $value): bool
    {
        // 空文字でなく文字列か数値であればtrue
        return $value !== '' && (is_string($value) || is_numeric($value));
    }
}

if (!function_exists('is_filled_arr')) {
    /**
     * 空でない配列かどうか.
     *
     * クエリビルダのwhenなどに使用する
     */
    function is_filled_arr(mixed $value): bool
    {
        // 配列で空でなればtrue
        return is_array($value) && empty($value) === false;
    }
}

if (!function_exists('is_date')) {
    /**
     * 日付かどうか.
     */
    function is_date(?string $date, string $format = 'Y-m-d'): bool
    {
        // empty値でなくCarbonでフォーマットした日付と同じであれば日付として正しい
        return empty($date) === false && Carbon::parse($date)->format($format) === $date;
    }
}

if (!function_exists('is_mobile_address')) {
    /**
     * DocomoかAUのメールアドレスかどうか.
     *
     * @param mixed $value
     *
     * @return bool DocomoかAUのメールアドレスのときにtrue
     */
    function is_mobile_address($value): bool
    {
        // DocomoとAUのRFC違反のメールを許可する場合
        // @see http://ke-tai.org/blog/2009/05/29/ketaimailmatome/
        return preg_match('/\A[a-zA-z0-9._-]{3,30}@(docomo|ezweb)\.ne\.jp\z/', (string) $value) === 1;
    }
}

if (!function_exists('is_ssl')) {
    /**
     * HTTPSかどうか.
     *
     * @see https://qiita.com/mpyw/items/a17bf1a19ff56319cb21
     */
    function is_ssl(): bool
    {
        $forwarded = filter_input(INPUT_SERVER, 'HTTP_FORWARDED') ?: '';

        return
            filter_input(INPUT_SERVER, 'HTTPS', FILTER_VALIDATE_BOOL) // Apache
            || filter_input(INPUT_SERVER, 'SSL', FILTER_VALIDATE_BOOL) // IIS
            || str_contains($forwarded, 'proto=https') // Reverse proxy(RFC 7239)
            || filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_PROTO') === 'https' // Reverse proxy
            // || filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_PORT', FILTER_VALIDATE_INT) === 443 // Reverse proxy
            // || filter_input(INPUT_SERVER, 'SERVER_PORT', FILTER_VALIDATE_INT) === 443
        ;
    }
}

if (!function_exists('check_digit')) {
    /**
     * JANコード・ITFコード等のチェックデジットを取得する.
     *
     * http://www.dsri.jp/jan/check_digit.html
     *
     * - モジュラス10/ウェイト3
     *   1. データキャラクタの最も右側にある桁を奇数とし、全てのキャラクタを奇数位置と偶数位置に分類します。
     *   2. 奇数位置にあるキャラクタを合計し、その結果を3倍します。
     *   3. 偶数位置にあるキャラクタを合計します。
     *   4. 奇数位置の結果(2)と偶数位置の結果(3)を合計します。
     *   5. (4)の結果の1の位の数字を「10」から引いた数字がチェックデジットです。
     *
     * @param string $value チェックデジットを計算する値
     *
     * @return string 計算したチェックデジット
     */
    function check_digit(string $value): string
    {
        // 文字列を分割
        $arr = str_split($value);

        // 偶数の和
        $even = 0;

        // 奇数の和
        $odd = 0;

        // 奇数の和・偶数の和を計算する
        $count = count($arr) - 1;
        $index = 0;
        while ($count >= 0) {
            if (($index % 2) === 0) {
                $even += $arr[$count];
            } else {
                $odd += $arr[$count];
            }

            ++$index;
            --$count;
        }

        // 偶数の和を3倍+奇数の総和を加算して、下1桁の数字を10から引く
        $digit = 10 - substr((string) (($even * 3) + $odd), -1);

        // 10なら1の位は0なので、0にする
        return $digit === 10 ? '0' : (string) $digit;
    }
}
