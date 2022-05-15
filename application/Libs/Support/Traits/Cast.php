<?php

declare(strict_types=1);

namespace App\Libs\Support\Traits;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use RuntimeException;
use stdClass;

/**
 * DBから取得した値をPHPで使用する値に変換するトレイト.
 *
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
trait Cast
{
    /**
     * 取得した値のキャスト.
     * NULLは変換対象外.
     *
     * [
     *   'キー' => 'int',              // 整数
     *   'キー' => 'float',            // 浮動小数点(普通は変換しない。特に数値の計算が必要な場合)
     *   'キー' => 'bool',             // 真偽値
     *   'キー' => 'string',           // 文字列
     *   'キー' => 'date:Y-m-d H:i:s', // 「:」以降に指定した日付のフォーマットに変換した文字列
     *   'キー' => 'iso8601',          // 「YYYY-MM-DDT00:00:00.000000Z」ISO8601形式
     *   'キー' => 'carbon',           // Carbonオブジェクト
     *   'キー' => 'carbon_immutable', // CarbonImmutableオブジェクト
     *   'キー' => 'json',             // JSON型からPHPの配列
     *   'キー' => 'Enum::class',      // 「bensampo/laravel-enum」を継承しているクラスの「description()」で取得される文字列
     * ]
     *
     * @var array<string, string>
     */
    protected array $casts = [];

    /**
     * キャストの設定を取得.
     *
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * キャストの設定の置き換え.
     *
     * @param array<string, string> $casts
     */
    public function setCasts(array $casts): self
    {
        $this->casts = $casts;

        return $this;
    }

    /**
     * キャストの設定のマージ.
     *
     * @param array<string, string> $casts
     */
    public function mergeCasts(array $casts): self
    {
        $this->casts = array_merge($this->casts, $casts);

        return $this;
    }

    /**
     * Decode the given float.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function fromFloat($value)
    {
        switch ((string) $value) {
            case 'Infinity':
                return INF;

            case '-Infinity':
                return -INF;

            case 'NaN':
                return NAN;

            default:
                return (float) $value;
        }
    }

    /**
     * 値をキャストして返す.
     *
     * @param null|\stdClass $row キャストする行データ
     */
    protected function castValues(?stdClass $row): ?stdClass
    {
        if (empty($this->casts) || $row === null) {
            return $row;
        }

        foreach ($this->casts as $key => $cast) {
            if (isset($row->{$key})) {
                // NULLで無い値がセットされているときに値を変換
                $row->{$key} = $this->castAttribute($cast, $row->{$key});
            }
        }

        return $row;
    }

    /**
     * キャストを種別とパラメータに分割.
     *
     * @return string[]
     */
    protected function getSpritCast(string $cast): array
    {
        return explode(':', $cast);
    }

    /**
     * 値のキャスト.
     *
     * @param string $cast  パラメータ付きの種別
     * @param mixed  $value 変換する値
     *
     * @return mixed
     */
    protected function castAttribute(string $cast, $value)
    {
        if (method_exists($cast, 'description')) {
            // 「bensampo/laravel-enum」を継承して作成したEnumの場合(descriptionメソッドはオリジナルメソッド)
            return $cast::description($value);
        }

        // 種別とパラメータに分割
        [$type, $param] = $this->getSpritCast($cast);

        switch ($type) {
            case 'int':
                return (int) $value;

            case 'float':
                return $this->fromFloat($value);

            case 'bool':
                return (bool) $value;

            case 'string':
                return (string) $value;

            case 'date':
                return $value === null ? null : Carbon::parse($value)->format($param);

            case 'iso8601':
                return $value === null ? null : Carbon::parse($value)->toISOString();

            case 'carbon':
                return $value === null ? null : Carbon::parse($value);

            case 'carbon_immutable':
                return $value === null ? null : CarbonImmutable::parse($value);

            case 'json':
                return json_decode($value, true, 512, JSON_THROW_ON_ERROR);

            default:
                throw new RuntimeException(sprintf('[%s]という変換種別は存在しません。', $type));
        }

        // @phpstan-ignore-next-line
        return $value;
    }
}
