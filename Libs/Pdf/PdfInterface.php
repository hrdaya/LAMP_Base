<?php

declare(strict_types=1);

namespace Libs\Pdf;

interface PdfInterface
{
    /**
     * PDF作成を実行.
     */
    public function handle(array $params): void;

    /**
     * ファイルを保存.
     */
    public function save(string $filePath): void;
}
