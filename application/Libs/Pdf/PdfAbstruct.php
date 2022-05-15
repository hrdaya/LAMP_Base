<?php

declare(strict_types=1);

namespace App\Libs\Pdf;

use App\Libs\Support\Traits\FileSupportTrait;

abstract class PdfAbstruct implements PdfInterface
{
    use FileSupportTrait;

    /** @var \Libs\Pdf\Pdf */
    protected $pdf;

    public function __construct(Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    /**
     * PDF作成の実行.
     */
    public function handle(array $params): void
    {
    }

    /**
     * ファイルを保存.
     */
    public function save(string $filePath): void
    {
        // ディレクトリが存在しない場合は作成
        $this->makeWriteDirectory($filePath);

        // 保存
        $this->pdf->save($filePath);
    }
}
