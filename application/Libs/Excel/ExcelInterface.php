<?php

declare(strict_types=1);

namespace App\Libs\Excel;

interface ExcelInterface
{
    /**
     * Excel作成の実行.
     */
    public function handle(array $params): void;

    /**
     * ファイルを保存.
     */
    public function save(string $filePath): void;
}
