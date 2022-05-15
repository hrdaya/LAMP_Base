<?php

declare(strict_types=1);

namespace App\Libs\Excel;

use App\Libs\Support\Traits\FileSupportTrait;

abstract class ExcelAbstruct implements ExcelInterface
{
    use FileSupportTrait;

    /** @var \Libs\Excel\XLSXWriter */
    protected $excel;

    /**
     * 現在の行番号.
     */
    protected ?int $rowNum = null;

    /**
     * コンストラクタ
     */
    public function __construct(XLSXWriter $excel)
    {
        $this->excel = $excel;
    }

    /**
     * Excel作成の実行.
     */
    abstract public function handle(array $rows): void; /** @phpstan-ignore-line */

    /**
     * ファイルを保存.
     */
    public function save(string $filePath): void
    {
        // ディレクトリが存在しない場合は作成
        $this->makeWriteDirectory($filePath);

        // 保存
        $this->excel->save($filePath);
    }

    /**
     * シートの列などの設定を行う.
     */
    protected function setColOptions(string $sheetName, array $colOptions): void
    {
        $this->excel->setColOptions($sheetName, $colOptions);
    }

    /**
     * 行への書き込みと行番号のインクリメント.
     */
    protected function writeSheetRow(string $sheetName, array $row, array $rowStyles = null, array $rowFormat = []): int
    {
        // Excelへの書き込み
        $this->excel->writeSheetRow($sheetName, $row, $rowStyles, $rowFormat);

        // 行番号のインクリメント
        $this->rowNum = $this->rowNum === null ? 0 : ++$this->rowNum;

        // インクリメントした行番号を返す
        return $this->rowNum;
    }

    /**
     * セルの結合.
     */
    protected function markMergedCell(string $sheetName, int $startCellRow, int $startCellColumn, int $endCellRow, int $endCellColumn): void
    {
        $this->excel->markMergedCell($sheetName, $startCellRow, $startCellColumn, $endCellRow, $endCellColumn);
    }

    /**
     * セルのインデックスからセル名を取得.
     *
     * @param int  $columnNumber 0スタート
     * @param int  $rowNumber    0スタート
     * @param bool $absolute     $を付けるかどうか
     *
     * @return string label/coordinates, ex: A1, C3, AA42 (or if $absolute==true: $A$1, $C$3, $AA$42)
     */
    protected function xlsCell(int $columnNumber, int $rowNumber, bool $absolute = false)
    {
        $colString = '';
        $alphabet  = range('A', 'Z');

        while ($columnNumber >= 0) {
            $one = (int) fmod($columnNumber, 26);
            $colString .= $alphabet[$one];
            $columnNumber = ($columnNumber - $one - 1) / 26;
        }

        $colString = strrev($colString);

        ++$rowNumber;

        if ($absolute) {
            return '$'.$colString.'$'.$rowNumber;
        }

        return $colString.$rowNumber;
    }
}
