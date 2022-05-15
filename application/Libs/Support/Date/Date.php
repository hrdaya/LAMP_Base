<?php

declare(strict_types=1);

namespace App\Libs\Support;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * 日付ユーティリティクラス.
 *
 * @see https://zariganitosh.hatenablog.jp/entry/20140929/japanese_holiday_memo
 */
class Date
{
    private const KEY_FORMAT = 'Y-m-d';

    /**
     * 祝祭日の配列.
     *
     * @see https://www.wikiwand.com/ja/%E7%A5%9D%E7%A5%AD%E6%97%A5
     * @see https://www.wikiwand.com/ja/%E5%9B%BD%E6%B0%91%E3%81%AE%E7%A5%9D%E6%97%A5%E3%81%AB%E9%96%A2%E3%81%99%E3%82%8B%E6%B3%95%E5%BE%8B
     *
     * [開始年、終了年、月、日、祝祭日名]
     * 日は数字ならその日、配列なら[何回目、曜日]、文字列なら実行する関数名
     */
    private const HOLIDAYS = [
        // 1月 =================================================================
        [1874, 1948, 1,  1,      '四方節'],
        [1949, 9999, 1,  1,      '元日'],
        [1874, 1948, 1,  3,      '元始祭'],
        [1874, 1948, 1,  5,      '新年宴会'],
        [1949, 1999, 1,  15,     '成人の日'],
        [2000, 9999, 1,  [2, 1], '成人の日'],
        [1874, 1912, 1,  30,     '孝明天皇祭'],
        // 2月 =================================================================
        [1874, 1948, 2,  11,     '紀元節'],
        [1967, 9999, 2,  11,     '建国記念の日'],
        [2020, 9999, 2,  23,     '天皇誕生日'],
        [1989, 1989, 2,  24,     '昭和天皇の大喪の礼'],
        // 3月 =================================================================
        [1879, 1948, 3,  'SPR',  '春季皇霊祭'],
        [1949, 2150, 3,  'SPR',  '春分の日'], // 必要であろう範囲までの計算
        // 4月 =================================================================
        [1874, 1948, 4,  3,      '神武天皇祭'],
        [1959, 1959, 4,  10,     '皇太子・明仁親王の結婚の儀'],
        [1927, 1948, 4,  29,     '天長節'],
        [1949, 1988, 4,  29,     '天皇誕生日'],
        [1989, 2006, 4,  29,     'みどりの日'],
        [2007, 9999, 4,  29,     '昭和の日'],
        // 5月 =================================================================
        [2019, 2019, 5,  1,      '即位の礼'],
        [1949, 9999, 5,  3,      '憲法記念日'],
        [2007, 9999, 5,  4,      'みどりの日'],
        [1949, 9999, 5,  5,      'こどもの日'],
        // 6月 =================================================================
        [1993, 1993, 6,  9,      '皇太子・徳仁親王の結婚の儀'],
        // 7月 =================================================================
        [1996, 2002, 7,  20,     '海の日'],
        [2003, 2019, 7,  [3, 1], '海の日'],
        [2020, 2020, 7,  23,     '海の日'],
        [2020, 2020, 7,  24,     'スポーツの日'],
        [2021, 2021, 7,  22,     '海の日'],
        [2021, 2021, 7,  23,     'スポーツの日'],
        [2022, 9999, 7,  [3, 1], '海の日'],
        [1913, 1926, 7,  30,     '明治天皇祭'],
        // 8月 =================================================================
        [2016, 2019, 8,  11,     '山の日'],
        [2020, 2020, 8,  10,     '山の日'],
        [2021, 2021, 8,  8,      '山の日'],
        [2022, 9999, 8,  11,     '山の日'],
        [1913, 1926, 8,  31,     '天長節'],
        // 9月 =================================================================
        [1966, 2002, 9,  15,     '敬老の日'],
        [2003, 9999, 9,  [3, 1], '敬老の日'],
        [1874, 1878, 9,  17,     '神嘗祭'],
        [1878, 1947, 9,  'AUT',  '秋季皇霊祭'],
        [1948, 2150, 9,  'AUT',  '秋分の日'], // 必要であろう範囲までの計算
        // 10月 ================================================================
        [1966, 1999, 10, 10,     '体育の日'],
        [2000, 2019, 10, [2, 1], '体育の日'],
        [2022, 9999, 10, [2, 1], 'スポーツの日'],
        [1879, 1947, 10, 17,     '神嘗祭'],
        [2019, 2019, 10, 22,     '即位の礼正殿の儀'],
        [1913, 1926, 10, 31,     '天長節祝日'],
        // 11月 ================================================================
        [1873, 1911, 11, 3,      '天長節'],
        [1927, 1947, 11, 3,      '明治節'],
        [1948, 9999, 11, 3,      '文化の日'],
        [1915, 1915, 11, 10,     '即位の礼'],
        [1915, 1915, 11, 14,     '大嘗祭'],
        [1915, 1915, 11, 16,     '大饗第1日'],
        [1928, 1928, 11, 10,     '即位の礼'],
        [1928, 1928, 11, 14,     '大嘗祭'],
        [1928, 1928, 11, 16,     '大饗第1日'],
        [1990, 1990, 11, 12,     '即位の礼正殿の儀'],
        [1873, 1947, 11, 23,     '新嘗祭'],
        [1948, 9999, 11, 23,     '勤労感謝の日'],
        // 12月 ================================================================
        [1989, 2018, 12, 23,     '天皇誕生日'],
        [1927, 1947, 12, 25,     '大正天皇祭'],
    ];

    /**
     * 和暦のリスト.
     * 明治6年(1873年1月1日)より前は太陰暦のため日付は一致しない.
     *
     * @see https://zariganitosh.hatenablog.jp/entry/20140929/japanese_holiday_memo
     */
    private const WAREKIS = [
        1 => [
            'era'  => '明治',       // 元号
            'year' => '1868',       // 元年
            'date' => '1868-01-25', // 開始日
        ],
        2 => [
            'era'  => '大正',       // 元号
            'year' => '1912',       // 元年
            'date' => '1912-07-30', // 開始日
        ],
        3 => [
            'era'  => '昭和',       // 元号
            'year' => '1926',       // 元年
            'date' => '1926-12-25', // 開始日
        ],
        4 => [
            'era'  => '平成',       // 元号
            'year' => '1989',       // 元年
            'date' => '1989-01-08', // 開始日
        ],
        5 => [
            'era'  => '令和',       // 元号
            'year' => '2019',       // 元年
            'date' => '2019-05-01', // 開始日
        ],
    ];

    /**
     * 漢数字を半角数字に変換するためのリスト.
     */
    private const NUMBERS = [
        '〇' => '0',
        '一' => '1',
        '二' => '2',
        '三' => '3',
        '四' => '4',
        '五' => '5',
        '六' => '6',
        '七' => '7',
        '八' => '8',
        '九' => '9',
    ];

    /**
     * 年ごとの祝日の一覧.
     */
    private static array $years = [];

    private function __construct()
    {
    }

    /**
     * 祝日かどうかを判定.
     *
     * @return bool 祝日の場合にtrue
     */
    public static function isHoliday(string|DateTimeInterface $date): bool
    {
        // 祝日名が存在する場合にtrue
        return self::getHolidayName($date) !== null;
    }

    /**
     * 土曜日かどうかを判定.
     *
     * @return bool 日曜日のときにtrue
     */
    public static function isSaturday(string|DateTimeInterface $date): bool
    {
        $date = self::getDateTimeObject($date);

        return (int) ($date->format('w')) === 6;
    }

    /**
     * 日曜日かどうかを判定.
     *
     * @return bool 日曜日のときにtrue
     */
    public static function isSunday(string|DateTimeInterface $date): bool
    {
        $date = self::getDateTimeObject($date);

        return (int) ($date->format('w')) === 0;
    }

    /**
     * 土日かどうかを判定.
     *
     * @return bool 土日のときにtrue
     */
    public static function isWeekend(string|DateTimeInterface $date): bool
    {
        return self::isSaturday($date) || self::isSunday($date);
    }

    /**
     * 土日祝日かどうかを判定.
     *
     * @return bool 土日祝日のときにtrue
     */
    public static function isWeekendOrHoliday(string|DateTimeInterface $date): bool
    {
        return self::isWeekend($date) || self::isHoliday($date);
    }

    /**
     * 祝日名を取得する.
     *
     * @return null|string 祝日名(祝日でない場合はnull)
     */
    public static function getHolidayName(string|DateTimeInterface $date): ?string
    {
        $date = self::getDateTimeObject($date);

        // 年ごとの祝日の一覧を取得
        $holidays = self::getHolidays($date->format('Y'));

        // リストの中の祝日名を返す
        return $holidays[$date->format(self::KEY_FORMAT)] ?? null;
    }

    /**
     * 曜日名を取得する.
     */
    public static function getDayOfWeekName(string|DateTimeInterface $date, string $suffix = '曜日'): string
    {
        $date = self::getDateTimeObject($date);

        $week = ['日', '月', '火', '水', '木', '金', '土'];

        return $week[$date->format('w')].$suffix;
    }

    /**
     * 元号を取得.
     */
    public static function getEra(string|DateTimeInterface $date, bool $strict = false): ?string
    {
        [$era] = self::getEraParams($date, $strict);

        return $era;
    }

    /**
     * 西暦を和暦に変換(年のみ).
     */
    public static function seireki2wareki(
        string|DateTimeInterface $date,
        string $suffix = '年',
        bool $strict = false,
        bool $castOne = true,
    ): string {
        [$era, $year] = self::getEraParams($date, $strict);

        if ($year === 1 && $castOne === true) {
            $year = '元';
        }

        return "{$era}{$year}{$suffix}";
    }

    /**
     * 和暦を西暦に変換(年のみ).
     *
     * @see http://php.o0o0.jp/article/php-numeral_replace
     */
    public static function wareki2seireki(string $wareki, string $suffix = '年'): ?string
    {
        // 和暦のリスト
        $warekis = array_column(self::WAREKIS, 'era', 'year');

        // 漢数字の十を判定
        $pattern = '/('.implode('|', array_values($warekis)).')([一二三四五六]*十)?([一二三四五六七八九〇]|元)?/u';
        if (preg_match($pattern , $wareki, $matches) === 1) {
            $search = $matches[0] ?? '';
            $era    = $matches[1] ?? null;
            $ten    = $matches[2] ?? null;
            $first  = $matches[3] ?? null;

            // 十の位
            if ($ten === '十') {
                // 十のみの場合は1に変換(十五 ⇒ 1五)
                $ten = '1';
            }elseif ($ten === null) {
                // 十の位が存在しない場合(五 ⇒ 五)
                $ten = '0';
            } else {
                // 上記以外(五十五 ⇒ 五五)
                $ten = str_replace('十', '', $ten);
            }

            // 一の位
            if ($first === '元') {
                // 十の位が元(元年 ⇒ 1年)
                $first = '1';
            } elseif ($first === null) {
                // 一の位が存在しないとき(五十 ⇒ 五0)
                $first = '0';
            }

            // 値を置き換え
            $wareki = str_replace($search, "{$era}{$ten}{$first}", $wareki);
        }

        // 漢数字を半角数字に変換
        $wareki = strtr($wareki, self::NUMBERS);

        // 全角数字を半角数字に変換
        $wareki = mb_convert_kana($wareki, 'n');

        // 正規表現のパターンを作成
        $pattern = '/('.implode('|', array_values($warekis)).')([0-9]+)/u';

        if (preg_match($pattern, $wareki, $matches) === 1) {
            $era     = $matches[1] ?? null;
            $eraYear = $matches[2] ?? null;

            if (intval($eraYear) === 0) {
                // 元号のみの場合
                return null;
            }

            foreach ($warekis as $year => $name) {
                if ($name === $era) {
                    $seireki = $eraYear + $year - 1;

                    return "{$seireki}{$suffix}";
                }
            }
        }

        // 変換できなかったとき
        return null;
    }

    /**
     * 年ごとの祝日のリストを取得.
     */
    public static function getHolidays(string|int $year): array
    {
        $year = (string) $year;

        if (preg_match('/\A[0-9]{4}\z/', $year) !== 1) {
            throw new InvalidArgumentException('引数は4桁の数字を指定してください。');
        }

        if (isset(self::$years[$year]) === false) {
            // その年のリストが存在しない場合はリストを作成
            self::createHolidays($year);
        }

        // 祝日のリストを出力する
        return self::$years[$year];
    }

    /**
     * 引数をDateTimeのオブジェクトにして返す.
     */
    private static function getDateTimeObject(string|DateTimeInterface $date): DateTime
    {
        if (\is_string($date)) {
            $dateOrigin = $date;
            $dateString = str_replace('/', '-', $dateOrigin);
            $date       = new DateTime($dateString);

            if ($dateString !== $date->format('Y-m-d')) {
                $message = sprintf('日付の文字列が不正です。YYYY-MM-DD形式で正しく指定してください。[%s]', $dateOrigin);

                throw new RuntimeException($message);
            }
        } else {
            $date = new DateTime($date->format(self::KEY_FORMAT));
        }

        return $date;
    }

    /**
     * 元号と和暦の年に変換したデータを取得.
     */
    private static function getEraParams(string|DateTimeInterface $date, bool $strict): array
    {
        $date = self::getDateTimeObject($date);
        $year = (int) $date->format('Y');
        $era  = null;

        // 大きい数字の年から判定を行わないとおかしくなるので配列の順序を逆順にして判定
        foreach (array_reverse(self::WAREKIS, false) as $wareki) {
            $warekiDate = new DateTime($wareki['date']);
            if (($strict === false && $year >= $wareki['year']) || ($strict === true && $date >= $warekiDate)) {
                $era  = $wareki['era'];
                $year = $year - $wareki['year'] + 1;

                break;
            }
        }

        return [$era, $year];
    }

    /**
     * 指定された年の祝日を作成する.
     */
    private static function createHolidays(string $year): void
    {
        $result = [];

        foreach (self::HOLIDAYS as $holiday) {
            // 配列を展開
            [$startYear, $endYear, $month, $dateType, $name] = $holiday;

            if ($startYear <= $year && $year <= $endYear) {
                // 開始年、終了年の間におさまっている場合に祝祭日のオブジェクトを作成
                $result += static::getHoliday($year, $month, $dateType, $name);
            }
        }

        // 配列のキー順に並べ替える
        ksort($result);

        // 振替休日と国民の休日を設定していく
        foreach (array_keys($result) as $date) {
            // 振替休日をセット
            $result += self::getFurikae($date, $result);

            // 国民の休日をセット
            $result += self::getKokumin($date, $result);
        }

        // 配列のキー順に並べ替える
        ksort($result);

        self::$years[$year] = $result;
    }

    /**
     * 祝日を取得する.
     */
    private static function getHoliday(string $year, int $month, mixed $dateType, string $name): array
    {
        $date = null;

        if (\is_int($dateType)) {
            // 固定の日付の場合
            $date = new DateTime("{$year}-{$month}-{$dateType}");
        }

        if (\is_array($dateType)) {
            // 第一月曜日などの場合
            $date = self::getDayCountsInMonth(new DateTime("{$year}-{$month}-01"), $dateType[0], $dateType[1]);
        }

        if (\is_string($dateType)) {
            if (strtoupper($dateType) === 'SPR') {
                // 春分
                $date = self::getSpringHoliday($year, $month);
            }

            if (strtoupper($dateType) === 'AUT') {
                // 秋分
                $date = self::getAutumHokiday($year, $month);
            }
        }

        if ($date === null) {
            throw new RuntimeException('引数のデータ型がおかしいです');
        }

        return [$date->format(self::KEY_FORMAT) => $name];
    }

    /**
     * 1月第2月曜日などの移動日の日付にセットしたDateTimeを取得する.
     */
    private static function getDayCountsInMonth(DateTime $date, int $count, int $day): DateTime
    {
        // 第1回目の日付の取得
        // その月の第1週目の「day」で指定した曜日の日付を取得する
        $days = $day - ($date->format('w') - 1);

        // 日付が1より小さい時は「day」で指定した曜日が2週目から始まる
        $days += ($days < 1) ? ($count * 7) : (($count - 1) * 7);

        // 計算した日でDateTimeオブジェクトを生成
        return $date->setDate((int) ($date->format('Y')), (int) ($date->format('m')), $days);
    }

    /**
     * 年から春分の日を計算しDateTimeを取得する.
     *
     * @see http://mt-soft.sakura.ne.jp/kyozai/excel_high/200_jissen_kiso/60_syunbun.htm
     * @see https://www.wikiwand.com/ja/%E6%98%A5%E5%88%86%E3%81%AE%E6%97%A5
     */
    private static function getSpringHoliday(string $year, int $month): DateTime
    {
        // 春分の日を計算
        $param = match (true) {
            (1851 <= $year && $year <= 1899) => 19.8277,
            (1900 <= $year && $year <= 1979) => 20.8357,
            (1980 <= $year && $year <= 2099) => 20.8431,
            (2100 <= $year && $year <= 2150) => 21.8510,
        };

        $day = floor($param + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));

        // 計算した日でDateTimeオブジェクトを生成
        return new DateTime("{$year}-{$month}-{$day}");
    }

    /**
     * 年から秋分の日を計算しDateTimeを取得する.
     *
     * @see http://mt-soft.sakura.ne.jp/kyozai/excel_high/200_jissen_kiso/60_syunbun.htm
     * @see https://www.wikiwand.com/ja/%E7%A7%8B%E5%88%86%E3%81%AE%E6%97%A5
     */
    private static function getAutumHokiday(string $year, int $month): DateTime
    {
        // 秋分の日を計算
        $param = match (true) {
            (1851 <= $year && $year <= 1899) => 22.2588,
            (1900 <= $year && $year <= 1979) => 23.2588,
            (1980 <= $year && $year <= 2099) => 23.2488,
            (2100 <= $year && $year <= 2150) => 24.2488,
        };

        $day = floor($param + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));

        // 計算した日でDateTimeオブジェクトを生成
        return new DateTime("{$year}-{$month}-{$day}");
    }

    /**
     * 振替休日を取得.
     *
     * @see https://www.wikiwand.com/ja/%E6%8C%AF%E6%9B%BF%E4%BC%91%E6%97%A5
     */
    private static function getFurikae(string $date, array $result): array
    {
        $date = new DateTime($date);

        // 1973年4月12日以降で、日曜日に当たる場合は翌日を振替休日にする
        if ($date->format('Ymd') >= 19730412 && static::isSunday($date)) {
            // 該当日が戻り値のオブジェクトに含まれている
            // もしくは日曜日の間日付を追加
            while (isset($result[$date->format(self::KEY_FORMAT)]) || static::isSunday($date)) {
                $date = $date->modify('+1 days');
            }

            return [$date->format(self::KEY_FORMAT) => '振替休日'];
        }

        return [];
    }

    /**
     * 国民の休日を取得.
     *
     * @see https://www.wikiwand.com/ja/%E5%9B%BD%E6%B0%91%E3%81%AE%E4%BC%91%E6%97%A5
     */
    private static function getKokumin(string $date, array $result): array
    {
        // 日付を二日前にセット
        $date = (new DateTime($date))->modify('-2 days');

        // 1985年12月27日以降の時に二日前に祝日が存在する場合
        if ($date->format('Ymd') >= 19851227 && isset($result[$date->format(self::KEY_FORMAT)])) {
            // 日付を1日後（祝日と祝日の間の日）に移す
            $date = $date->modify('+1 days');

            // 挟まれた平日が休日なので該当日が火曜日以降の時に戻り値に値をセットする
            // 該当日が月曜日の場合は振替休日となっている
            // 連続した祝日の時は国民の休日とならないためすでに祝日が含まれているか確認する
            if ($date->format('w') > 1 && isset($result[$date->format(self::KEY_FORMAT)]) === false) {
                return [$date->format(self::KEY_FORMAT) => '休日'];
            }
        }

        return [];
    }
}
