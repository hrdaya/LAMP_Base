<?php

declare(strict_types=1);

namespace App\Libs\Excel;

/**
 * PHP_XLSXWriter(改).
 *
 * https://github.com/mk-j/PHP_XLSXWriter
 * https://github.com/mk-j/PHP_XLSXWriter/tree/master/examples
 *
 * 下記のメソッドを追加
 * - download()
 * - save()
 * - setColOptions()
 *
 * 下記のメソッドを変更
 * - writeSheetRow() ※各行ごとにセルのフォーマットを指定できるように修正
 * - styleFontIndexes() ※罫線を四方別々にセットできるように修正
 *
 * インデントをスペースに変更
 * staticなメソッドでないものを「self::」で呼び出しているところを修正
 *
 * @license MIT License
 */
class XLSXWriter
{
    //http://www.ecma-international.org/publications/standards/Ecma-376.htm
    //http://officeopenxml.com/SSstyles.php
    //------------------------------------------------------------------
    //http://office.microsoft.com/en-us/excel-help/excel-specifications-and-limits-HP010073849.aspx
    public const EXCEL_2007_MAX_ROW = 1048576;
    public const EXCEL_2007_MAX_COL = 16384;
    //------------------------------------------------------------------
    protected $title;
    protected $subject;
    protected $author;
    protected $isRightToLeft;
    protected $company;
    protected $description;
    protected $keywords = [];

    protected $current_sheet;
    protected $sheets         = [];
    protected $temp_files     = [];
    protected $cell_styles    = [];
    protected $number_formats = [];

    public function __construct()
    {
        \defined('ENT_XML1') || \define('ENT_XML1', 16); //for php 5.3, avoid fatal error
        date_default_timezone_get() || date_default_timezone_set('UTC'); //php.ini missing tz, avoid warning
        is_writable($this->tempFilename()) || self::log('Warning: tempdir '.sys_get_temp_dir().' not writeable, use ->setTempDir()');
        class_exists('ZipArchive') || self::log('Error: ZipArchive class does not exist');
        $this->addCellStyle($number_format = 'GENERAL', $style_string = null);
    }

    public function __destruct()
    {
        if (!empty($this->temp_files)) {
            foreach ($this->temp_files as $temp_file) {
                @unlink($temp_file);
            }
        }
    }

    public function setTitle($title = ''): void
    {
        $this->title = $title;
    }

    public function setSubject($subject = ''): void
    {
        $this->subject = $subject;
    }

    public function setAuthor($author = ''): void
    {
        $this->author = $author;
    }

    public function setCompany($company = ''): void
    {
        $this->company = $company;
    }

    public function setKeywords($keywords = ''): void
    {
        $this->keywords = $keywords;
    }

    public function setDescription($description = ''): void
    {
        $this->description = $description;
    }

    public function setTempDir($tempdir = ''): void
    {
        $this->tempdir = $tempdir;
    }

    public function setRightToLeft($isRightToLeft = false): void
    {
        $this->isRightToLeft = $isRightToLeft;
    }

    public function download($fileName): void
    {
        $temp_file = $this->tempFilename();
        $this->writeToFile($temp_file);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename*=UTF-8\'\''.rawurlencode($fileName));
        header('Content-Length: '.filesize($temp_file));
        header('Cache-Control: no-store');

        while (ob_get_level()) {
            ob_end_clean();
        }

        readfile($temp_file);

        exit;
    }

    public function save($filePath): void
    {
        $this->writeToFile($filePath);
    }

    public function writeToStdOut(): void
    {
        $temp_file = $this->tempFilename();
        $this->writeToFile($temp_file);
        readfile($temp_file);
    }

    public function writeToString()
    {
        $temp_file = $this->tempFilename();
        $this->writeToFile($temp_file);

        return file_get_contents($temp_file);
    }

    public function writeToFile($filename): void
    {
        foreach ($this->sheets as $sheet_name => $sheet) {
            $this->finalizeSheet($sheet_name); //making sure all footers have been written
        }

        if (file_exists($filename)) {
            if (is_writable($filename)) {
                @unlink($filename); //if the zip already exists, remove it
            } else {
                self::log('Error in '.__CLASS__.'::'.__FUNCTION__.', file is not writeable.');

                return;
            }
        }
        $zip = new \ZipArchive();
        if (empty($this->sheets)) {
            self::log('Error in '.__CLASS__.'::'.__FUNCTION__.', no worksheets defined.');

            return;
        }
        if (!$zip->open($filename, \ZipArchive::CREATE)) {
            self::log('Error in '.__CLASS__.'::'.__FUNCTION__.', unable to create zip.');

            return;
        }

        $zip->addEmptyDir('docProps/');
        $zip->addFromString('docProps/app.xml', $this->buildAppXML());
        $zip->addFromString('docProps/core.xml', $this->buildCoreXML());

        $zip->addEmptyDir('_rels/');
        $zip->addFromString('_rels/.rels', $this->buildRelationshipsXML());

        $zip->addEmptyDir('xl/worksheets/');
        foreach ($this->sheets as $sheet) {
            $zip->addFile($sheet->filename, 'xl/worksheets/'.$sheet->xmlname);
        }
        $zip->addFromString('xl/workbook.xml', $this->buildWorkbookXML());
        $zip->addFile($this->writeStylesXML(), 'xl/styles.xml');  //$zip->addFromString("xl/styles.xml"           , self::buildStylesXML() );
        $zip->addFromString('[Content_Types].xml', $this->buildContentTypesXML());

        $zip->addEmptyDir('xl/_rels/');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->buildWorkbookRelsXML());
        $zip->close();
    }

    /**
     * カラムのオプションのみをセットするものが無かったので作成
     * 行データを書き込む前に実行すること.
     *
     * @param mixed $sheet_name
     * @param mixed $col_options
     */
    public function setColOptions($sheet_name, $col_options): void
    {
        if (empty($sheet_name) || empty($col_options)) {
            return;
        }

        $col_widths     = isset($col_options['widths']) ? (array) $col_options['widths'] : [];
        $auto_filter    = isset($col_options['auto_filter']) ? (int) ($col_options['auto_filter']) : false;
        $freeze_rows    = isset($col_options['freeze_rows']) ? (int) ($col_options['freeze_rows']) : false;
        $freeze_columns = isset($col_options['freeze_columns']) ? (int) ($col_options['freeze_columns']) : false;
        $this->initializeSheet($sheet_name, $col_widths, $auto_filter, $freeze_rows, $freeze_columns);
    }

    public function writeSheetHeader($sheet_name, array $header_types, $col_options = null): void
    {
        if (empty($sheet_name) || empty($header_types) || !empty($this->sheets[$sheet_name])) {
            return;
        }
        $suppress_row = isset($col_options['suppress_row']) ? (int) ($col_options['suppress_row']) : false;
        if (\is_bool($col_options)) {
            self::log("Warning! passing {$suppress_row}=false|true to writeSheetHeader() is deprecated, this will be removed in a future version.");
            $suppress_row = (int) $col_options;
        }
        $style = &$col_options;

        $col_widths     = isset($col_options['widths']) ? (array) $col_options['widths'] : [];
        $auto_filter    = isset($col_options['auto_filter']) ? (int) ($col_options['auto_filter']) : false;
        $freeze_rows    = isset($col_options['freeze_rows']) ? (int) ($col_options['freeze_rows']) : false;
        $freeze_columns = isset($col_options['freeze_columns']) ? (int) ($col_options['freeze_columns']) : false;
        $this->initializeSheet($sheet_name, $col_widths, $auto_filter, $freeze_rows, $freeze_columns);
        $sheet          = &$this->sheets[$sheet_name];
        $sheet->columns = $this->initializeColumnTypes($header_types);
        if (!$suppress_row) {
            $header_row = array_keys($header_types);

            $sheet->file_writer->write('<row collapsed="false" customFormat="false" customHeight="false" hidden="false" ht="12.1" outlineLevel="0" r="'.(1).'">');
            foreach ($header_row as $c => $v) {
                $cell_style_idx = empty($style) ? $sheet->columns[$c]['default_cell_style'] : $this->addCellStyle('GENERAL', json_encode(isset($style[0]) ? $style[$c] : $style));
                $this->writeCell($sheet->file_writer, 0, $c, $v, $number_format_type = 'n_string', $cell_style_idx);
            }
            $sheet->file_writer->write('</row>');
            ++$sheet->row_count;
        }
        $this->current_sheet = $sheet_name;
    }

    public function writeSheetRow($sheet_name, array $row, $row_options = null, array $header_types = []): void
    {
        if (empty($sheet_name)) {
            return;
        }
        $this->initializeSheet($sheet_name);
        $sheet = &$this->sheets[$sheet_name];

        if (!empty($header_types)) {
            $sheet->columns = $this->initializeColumnTypes($header_types);
        }

        if (\count($sheet->columns) < \count($row)) {
            $default_column_types = $this->initializeColumnTypes(array_fill($from = 0, $until = \count($row), 'GENERAL')); //will map to n_auto
            $sheet->columns       = array_merge((array) $sheet->columns, $default_column_types);
        }

        if (!empty($row_options)) {
            $ht        = isset($row_options['height']) ? (float) ($row_options['height']) : 12.1;
            $customHt  = isset($row_options['height']) ? true : false;
            $hidden    = isset($row_options['hidden']) ? (bool) ($row_options['hidden']) : false;
            $collapsed = isset($row_options['collapsed']) ? (bool) ($row_options['collapsed']) : false;
            $sheet->file_writer->write('<row collapsed="'.($collapsed).'" customFormat="false" customHeight="'.($customHt).'" hidden="'.($hidden).'" ht="'.($ht).'" outlineLevel="0" r="'.($sheet->row_count + 1).'">');
        } else {
            $sheet->file_writer->write('<row collapsed="false" customFormat="false" customHeight="false" hidden="false" ht="12.1" outlineLevel="0" r="'.($sheet->row_count + 1).'">');
        }

        $style = &$row_options;
        $c     = 0;
        foreach ($row as $v) {
            $number_format      = $sheet->columns[$c]['number_format'];
            $number_format_type = $sheet->columns[$c]['number_format_type'];
            $cell_style_idx     = empty($style) ? $sheet->columns[$c]['default_cell_style'] : $this->addCellStyle($number_format, json_encode(isset($style[0]) ? $style[$c] : $style));
            $this->writeCell($sheet->file_writer, $sheet->row_count, $c, $v, $number_format_type, $cell_style_idx);
            ++$c;
        }
        $sheet->file_writer->write('</row>');
        ++$sheet->row_count;
        $this->current_sheet = $sheet_name;
    }

    public function countSheetRows($sheet_name = '')
    {
        $sheet_name = $sheet_name ?: $this->current_sheet;

        return \array_key_exists($sheet_name, $this->sheets) ? $this->sheets[$sheet_name]->row_count : 0;
    }

    public function markMergedCell($sheet_name, $start_cell_row, $start_cell_column, $end_cell_row, $end_cell_column): void
    {
        if (empty($sheet_name) || $this->sheets[$sheet_name]->finalized) {
            return;
        }
        $this->initializeSheet($sheet_name);
        $sheet = &$this->sheets[$sheet_name];

        $startCell            = self::xlsCell($start_cell_row, $start_cell_column);
        $endCell              = self::xlsCell($end_cell_row, $end_cell_column);
        $sheet->merge_cells[] = $startCell.':'.$endCell;
    }

    public function writeSheet(array $data, $sheet_name = '', array $header_types = []): void
    {
        $sheet_name = empty($sheet_name) ? 'Sheet1' : $sheet_name;
        $data       = empty($data) ? [['']] : $data;
        if (!empty($header_types)) {
            $this->writeSheetHeader($sheet_name, $header_types);
        }
        foreach ($data as $i => $row) {
            $this->writeSheetRow($sheet_name, $row);
        }
        $this->finalizeSheet($sheet_name);
    }

    //------------------------------------------------------------------

    /**
     * @param $row_number int, zero based
     * @param $column_number int, zero based
     * @param $absolute bool
     *
     * @return Cell label/coordinates, ex: A1, C3, AA42 (or if $absolute==true: $A$1, $C$3, $AA$42)
     * */
    public static function xlsCell($row_number, $column_number, $absolute = false)
    {
        $n = $column_number;
        for ($r = ''; $n >= 0; $n = (int) ($n / 26) - 1) {
            $r = \chr($n % 26 + 0x41).$r;
        }
        if ($absolute) {
            return '$'.$r.'$'.($row_number + 1);
        }

        return $r.($row_number + 1);
    }

    //------------------------------------------------------------------
    public static function log($string): void
    {
        //file_put_contents("php://stderr", date("Y-m-d H:i:s:").rtrim(is_array($string) ? json_encode($string) : $string)."\n");
        error_log(date('Y-m-d H:i:s:').rtrim(\is_array($string) ? json_encode($string) : $string)."\n");
    }

    //------------------------------------------------------------------
    public static function sanitize_filename($filename) //http://msdn.microsoft.com/en-us/library/aa365247%28VS.85%29.aspx
    {
        $nonprinting   = array_map('chr', range(0, 31));
        $invalid_chars = ['<', '>', '?', '"', ':', '|', '\\', '/', '*', '&'];
        $all_invalids  = array_merge($nonprinting, $invalid_chars);

        return str_replace($all_invalids, '', $filename);
    }

    //------------------------------------------------------------------
    public static function sanitize_sheetname($sheetname)
    {
        static $badchars  = '\\/?*:[]';
        static $goodchars = '        ';
        $sheetname        = strtr($sheetname, $badchars, $goodchars);
        $sheetname        = \function_exists('mb_substr') ? mb_substr($sheetname, 0, 31) : substr($sheetname, 0, 31);
        $sheetname        = trim(trim(trim($sheetname), "'")); //trim before and after trimming single quotes

        return !empty($sheetname) ? $sheetname : 'Sheet'.((random_int(0, getrandmax()) % 900) + 100);
    }

    //------------------------------------------------------------------
    public static function xmlspecialchars($val)
    {
        //note, badchars does not include \t\n\r (\x09\x0a\x0d)
        static $badchars  = "\x00\x01\x02\x03\x04\x05\x06\x07\x08\x0b\x0c\x0e\x0f\x10\x11\x12\x13\x14\x15\x16\x17\x18\x19\x1a\x1b\x1c\x1d\x1e\x1f\x7f";
        static $goodchars = '                              ';

        return strtr(htmlspecialchars((string) $val, ENT_QUOTES|ENT_XML1), $badchars, $goodchars); //strtr appears to be faster than str_replace
    }

    //------------------------------------------------------------------
    public static function array_first_key(array $arr)
    {
        reset($arr);

        return key($arr);
    }

    //------------------------------------------------------------------
    public static function add_to_list_get_index(&$haystack, $needle)
    {
        $existing_idx = array_search($needle, $haystack, $strict = true);
        if ($existing_idx === false) {
            $existing_idx = \count($haystack);
            $haystack[]   = $needle;
        }

        return $existing_idx;
    }

    //------------------------------------------------------------------
    public static function convert_date_time($date_input) //thanks to Excel::Writer::XLSX::Worksheet.pm (perl)
    {
        $days    = 0;    // Number of days since epoch
        $seconds = 0;    // Time expressed as fraction of 24h hours in seconds
        $year    = $month    = $day    = 0;
        $hour    = $min    = $sec    = 0;

        $date_time = $date_input;
        if (preg_match('/(\\d{4})\\-(\\d{2})\\-(\\d{2})/', $date_time, $matches)) {
            [$junk, $year, $month, $day] = $matches;
        }
        if (preg_match('/(\\d+):(\\d{2}):(\\d{2})/', $date_time, $matches)) {
            [$junk, $hour, $min, $sec] = $matches;
            $seconds                   = ($hour * 60 * 60 + $min * 60 + $sec) / (24 * 60 * 60);
        }

        //using 1900 as epoch, not 1904, ignoring 1904 special case

        // Special cases for Excel.
        if ("{$year}-{$month}-{$day}" === '1899-12-31') {
            return $seconds;
        }    // Excel 1900 epoch
        if ("{$year}-{$month}-{$day}" === '1900-01-00') {
            return $seconds;
        }    // Excel 1900 epoch
        if ("{$year}-{$month}-{$day}" === '1900-02-29') {
            return 60 + $seconds;
        }    // Excel false leapday
        // We calculate the date by calculating the number of days since the epoch
        // and adjust for the number of leap days. We calculate the number of leap
        // days by normalising the year in relation to the epoch. Thus the year 2000
        // becomes 100 for 4 and 100 year leapdays and 400 for 400 year leapdays.
        $epoch  = 1900;
        $offset = 0;
        $norm   = 300;
        $range  = $year - $epoch;

        // Set month days and check for leap year.
        $leap  = (($year % 400 === 0) || (($year % 4 === 0) && ($year % 100))) ? 1 : 0;
        $mdays = [31, ($leap ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

        // Some boundary checks
        if ($year !== 0 || $month !== 0 || $day !== 0) {
            if ($year < $epoch || $year > 9999) {
                return 0;
            }
            if ($month < 1 || $month > 12) {
                return 0;
            }
            if ($day < 1 || $day > $mdays[$month - 1]) {
                return 0;
            }
        }

        // Accumulate the number of days since the epoch.
        $days = $day;    // Add days for current month
        $days += array_sum(\array_slice($mdays, 0, $month - 1));    // Add days for past months
        $days += $range * 365;                      // Add days for past years
        $days += (int) (($range) / 4);             // Add leapdays
        $days -= (int) (($range + $offset) / 100); // Subtract 100 year leapdays
        $days += (int) (($range + $offset + $norm) / 400);  // Add 400 year leapdays
        $days -= $leap;                                      // Already counted above

        // Adjust for Excel erroneously treating 1900 as a leap year.
        if ($days > 59) {
            ++$days;
        }

        return $days + $seconds;
    }

    protected function tempFilename()
    {
        $tempdir            = !empty($this->tempdir) ? $this->tempdir : sys_get_temp_dir();
        $filename           = tempnam($tempdir, 'xlsx_writer_');
        $this->temp_files[] = $filename;

        return $filename;
    }

    protected function initializeSheet($sheet_name, $col_widths = [], $auto_filter = false, $freeze_rows = false, $freeze_columns = false): void
    {
        //if already initialized
        if ($this->current_sheet === $sheet_name || isset($this->sheets[$sheet_name])) {
            return;
        }
        $sheet_filename            = $this->tempFilename();
        $sheet_xmlname             = 'sheet'.(\count($this->sheets) + 1).'.xml';
        $this->sheets[$sheet_name] = (object) [
            'filename'           => $sheet_filename,
            'sheetname'          => $sheet_name,
            'xmlname'            => $sheet_xmlname,
            'row_count'          => 0,
            'file_writer'        => new XLSXWriter_BuffererWriter($sheet_filename),
            'columns'            => [],
            'merge_cells'        => [],
            'max_cell_tag_start' => 0,
            'max_cell_tag_end'   => 0,
            'auto_filter'        => $auto_filter,
            'freeze_rows'        => $freeze_rows,
            'freeze_columns'     => $freeze_columns,
            'finalized'          => false,
        ];
        $rightToLeftValue = $this->isRightToLeft ? 'true' : 'false';
        $sheet            = &$this->sheets[$sheet_name];
        $tabselected      = \count($this->sheets) === 1 ? 'true' : 'false'; //only first sheet is selected
        $max_cell         = self::xlsCell(self::EXCEL_2007_MAX_ROW, self::EXCEL_2007_MAX_COL); //XFE1048577
        $sheet->file_writer->write('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
        $sheet->file_writer->write('<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">');
        $sheet->file_writer->write('<sheetPr filterMode="false">');
        $sheet->file_writer->write('<pageSetUpPr fitToPage="false"/>');
        $sheet->file_writer->write('</sheetPr>');
        $sheet->max_cell_tag_start = $sheet->file_writer->ftell();
        $sheet->file_writer->write('<dimension ref="A1:'.$max_cell.'"/>');
        $sheet->max_cell_tag_end = $sheet->file_writer->ftell();
        $sheet->file_writer->write('<sheetViews>');
        $sheet->file_writer->write('<sheetView colorId="64" defaultGridColor="true" rightToLeft="'.$rightToLeftValue.'" showFormulas="false" showGridLines="true" showOutlineSymbols="true" showRowColHeaders="true" showZeros="true" tabSelected="'.$tabselected.'" topLeftCell="A1" view="normal" windowProtection="false" workbookViewId="0" zoomScale="100" zoomScaleNormal="100" zoomScalePageLayoutView="100">');
        if ($sheet->freeze_rows && $sheet->freeze_columns) {
            $sheet->file_writer->write('<pane ySplit="'.$sheet->freeze_rows.'" xSplit="'.$sheet->freeze_columns.'" topLeftCell="'.self::xlsCell($sheet->freeze_rows, $sheet->freeze_columns).'" activePane="bottomRight" state="frozen"/>');
            $sheet->file_writer->write('<selection activeCell="'.self::xlsCell($sheet->freeze_rows, 0).'" activeCellId="0" pane="topRight" sqref="'.self::xlsCell($sheet->freeze_rows, 0).'"/>');
            $sheet->file_writer->write('<selection activeCell="'.self::xlsCell(0, $sheet->freeze_columns).'" activeCellId="0" pane="bottomLeft" sqref="'.self::xlsCell(0, $sheet->freeze_columns).'"/>');
            $sheet->file_writer->write('<selection activeCell="'.self::xlsCell($sheet->freeze_rows, $sheet->freeze_columns).'" activeCellId="0" pane="bottomRight" sqref="'.self::xlsCell($sheet->freeze_rows, $sheet->freeze_columns).'"/>');
        } elseif ($sheet->freeze_rows) {
            $sheet->file_writer->write('<pane ySplit="'.$sheet->freeze_rows.'" topLeftCell="'.self::xlsCell($sheet->freeze_rows, 0).'" activePane="bottomLeft" state="frozen"/>');
            $sheet->file_writer->write('<selection activeCell="'.self::xlsCell($sheet->freeze_rows, 0).'" activeCellId="0" pane="bottomLeft" sqref="'.self::xlsCell($sheet->freeze_rows, 0).'"/>');
        } elseif ($sheet->freeze_columns) {
            $sheet->file_writer->write('<pane xSplit="'.$sheet->freeze_columns.'" topLeftCell="'.self::xlsCell(0, $sheet->freeze_columns).'" activePane="topRight" state="frozen"/>');
            $sheet->file_writer->write('<selection activeCell="'.self::xlsCell(0, $sheet->freeze_columns).'" activeCellId="0" pane="topRight" sqref="'.self::xlsCell(0, $sheet->freeze_columns).'"/>');
        } else { // not frozen
            $sheet->file_writer->write('<selection activeCell="A1" activeCellId="0" pane="topLeft" sqref="A1"/>');
        }
        $sheet->file_writer->write('</sheetView>');
        $sheet->file_writer->write('</sheetViews>');
        $sheet->file_writer->write('<cols>');
        $i = 0;
        if (!empty($col_widths)) {
            foreach ($col_widths as $column_width) {
                $column_width += 0.725;
                $sheet->file_writer->write('<col collapsed="false" hidden="false" max="'.($i + 1).'" min="'.($i + 1).'" style="0" customWidth="true" width="'.$column_width.'"/>');
                ++$i;
            }
        }
        $sheet->file_writer->write('<col collapsed="false" hidden="false" max="1024" min="'.($i + 1).'" style="0" customWidth="false" width="11.5"/>');
        $sheet->file_writer->write('</cols>');
        $sheet->file_writer->write('<sheetData>');
    }

    protected function finalizeSheet($sheet_name): void
    {
        if (empty($sheet_name) || $this->sheets[$sheet_name]->finalized) {
            return;
        }
        $sheet = &$this->sheets[$sheet_name];

        $sheet->file_writer->write('</sheetData>');

        if (!empty($sheet->merge_cells)) {
            $sheet->file_writer->write('<mergeCells>');
            foreach ($sheet->merge_cells as $range) {
                $sheet->file_writer->write('<mergeCell ref="'.$range.'"/>');
            }
            $sheet->file_writer->write('</mergeCells>');
        }

        $max_cell = self::xlsCell($sheet->row_count - 1, \count($sheet->columns) - 1);

        if ($sheet->auto_filter) {
            $sheet->file_writer->write('<autoFilter ref="A1:'.$max_cell.'"/>');
        }

        $sheet->file_writer->write('<printOptions headings="false" gridLines="false" gridLinesSet="true" horizontalCentered="false" verticalCentered="false"/>');
        $sheet->file_writer->write('<pageMargins left="0.5" right="0.5" top="1.0" bottom="1.0" header="0.5" footer="0.5"/>');
        $sheet->file_writer->write('<pageSetup blackAndWhite="false" cellComments="none" copies="1" draft="false" firstPageNumber="1" fitToHeight="1" fitToWidth="1" horizontalDpi="300" orientation="portrait" pageOrder="downThenOver" paperSize="1" scale="100" useFirstPageNumber="true" usePrinterDefaults="false" verticalDpi="300"/>');
        $sheet->file_writer->write('<headerFooter differentFirst="false" differentOddEven="false">');
        $sheet->file_writer->write('<oddHeader>&amp;C&amp;&quot;Times New Roman,Regular&quot;&amp;12&amp;A</oddHeader>');
        $sheet->file_writer->write('<oddFooter>&amp;C&amp;&quot;Times New Roman,Regular&quot;&amp;12Page &amp;P</oddFooter>');
        $sheet->file_writer->write('</headerFooter>');
        $sheet->file_writer->write('</worksheet>');

        $max_cell_tag   = '<dimension ref="A1:'.$max_cell.'"/>';
        $padding_length = $sheet->max_cell_tag_end - $sheet->max_cell_tag_start - \strlen($max_cell_tag);
        $sheet->file_writer->fseek($sheet->max_cell_tag_start);
        $sheet->file_writer->write($max_cell_tag.str_repeat(' ', $padding_length));
        $sheet->file_writer->close();
        $sheet->finalized = true;
    }

    protected function writeCell(XLSXWriter_BuffererWriter &$file, $row_number, $column_number, $value, $num_format_type, $cell_style_idx): void
    {
        $cell_name = self::xlsCell($row_number, $column_number);

        if (!is_scalar($value) || $value === '') { //objects, array, empty
            $file->write('<c r="'.$cell_name.'" s="'.$cell_style_idx.'"/>');
        } elseif (\is_string($value) && $value[0] === '=') {
            $file->write('<c r="'.$cell_name.'" s="'.$cell_style_idx.'" t="s"><f>'.self::xmlspecialchars($value).'</f></c>');
        } elseif ($num_format_type === 'n_date') {
            $file->write('<c r="'.$cell_name.'" s="'.$cell_style_idx.'" t="n"><v>'.(int) (self::convert_date_time($value)).'</v></c>');
        } elseif ($num_format_type === 'n_datetime') {
            $file->write('<c r="'.$cell_name.'" s="'.$cell_style_idx.'" t="n"><v>'.self::convert_date_time($value).'</v></c>');
        } elseif ($num_format_type === 'n_numeric') {
            $file->write('<c r="'.$cell_name.'" s="'.$cell_style_idx.'" t="n"><v>'.self::xmlspecialchars($value).'</v></c>'); //int,float,currency
        } elseif ($num_format_type === 'n_string') {
            $file->write('<c r="'.$cell_name.'" s="'.$cell_style_idx.'" t="inlineStr"><is><t>'.self::xmlspecialchars($value).'</t></is></c>');
        } elseif ($num_format_type === 'n_auto' || 1) { //auto-detect unknown column types
            if (!\is_string($value) || $value === '0' || ($value[0] !== '0' && ctype_digit($value)) || preg_match('/^\\-?(0|[1-9][0-9]*)(\\.[0-9]+)?$/', $value)) {
                $file->write('<c r="'.$cell_name.'" s="'.$cell_style_idx.'" t="n"><v>'.self::xmlspecialchars($value).'</v></c>'); //int,float,currency
            } else { //implied: ($cell_format=='string')
                $file->write('<c r="'.$cell_name.'" s="'.$cell_style_idx.'" t="inlineStr"><is><t>'.self::xmlspecialchars($value).'</t></is></c>');
            }
        }
    }

    protected function styleFontIndexes()
    {
        static $border_allowed       = ['left', 'right', 'top', 'bottom'];
        static $border_style_allowed = ['thin', 'medium', 'thick', 'dashDot', 'dashDotDot', 'dashed', 'dotted', 'double', 'hair', 'mediumDashDot', 'mediumDashDotDot', 'mediumDashed', 'slantDashDot'];
        static $horizontal_allowed   = ['general', 'left', 'right', 'justify', 'center', 'distributed'];
        static $vertical_allowed     = ['bottom', 'center', 'distributed', 'top'];
        $default_font                = ['size' => '10', 'name' => 'Arial', 'family' => '2'];
        $fills                       = ['', '']; //2 placeholders for static xml later
        $fonts                       = ['', '', '', '']; //4 placeholders for static xml later
        $borders                     = ['']; //1 placeholder for static xml later
        $style_indexes               = [];
        foreach ($this->cell_styles as $i => $cell_style_string) {
            $semi_colon_pos    = strpos($cell_style_string, ';');
            $number_format_idx = substr($cell_style_string, 0, $semi_colon_pos);
            $style_json_string = substr($cell_style_string, $semi_colon_pos + 1);
            $style             = @json_decode($style_json_string, $as_assoc = true);

            $style_indexes[$i] = ['num_fmt_idx' => $number_format_idx]; //initialize entry
            if (isset($style['border']) && \is_string($style['border'])) { //border is a comma delimited str
                $border_values = [];

                // 線種
                $borderStyle = 'hair';
                if (isset($style['border-style']) && \in_array($style['border-style'], $border_style_allowed, true)) {
                    $borderStyle = $style['border-style'];
                }

                // 色
                $borderColor = '';
                if (isset($style['border-color']) && \is_string($style['border-color']) && $style['border-color'][0] === '#') {
                    $v           = substr($style['border-color'], 1, 6);
                    $v           = \strlen($v) === 3 ? $v[0].$v[0].$v[1].$v[1].$v[2].$v[2] : $v; // expand cf0 => ccff00
                    $borderColor = 'FF'.strtoupper($v);
                }

                $borderSides = explode(',', $style['border']);
                foreach ($borderSides as $border) {
                    if (\in_array($border, $border_allowed, true)) {
                        $border_values[$border] = [
                            'style' => $borderStyle,
                            'color' => $borderColor,
                        ];
                    }
                }

                // 並び順を揃える
                ksort($border_values);
                $style_indexes[$i]['border_idx'] = self::add_to_list_get_index($borders, json_encode($border_values));
            } elseif (isset($style['borders']) && \is_array($style['borders'])) {
                $border_values = [];
                foreach ($style['borders'] as $border => $styles) {
                    if (\in_array($border, $border_allowed, true)) {
                        // 線種
                        $borderStyle = isset($styles['style']) && \in_array($styles['style'], $border_style_allowed, true) ? $styles['style'] : 'hair';

                        // 色
                        $borderColor = '';
                        if (isset($styles['color']) && \is_string($styles['color']) && $styles['color'][0] === '#') {
                            $v           = substr($styles['color'], 1, 6);
                            $v           = \strlen($v) === 3 ? $v[0].$v[0].$v[1].$v[1].$v[2].$v[2] : $v; // expand cf0 => ccff00
                            $borderColor = 'FF'.strtoupper($v);
                        }

                        $border_values[$border] = [
                            'style' => $borderStyle,
                            'color' => $borderColor,
                        ];
                    }
                }

                // 並び順を揃える
                ksort($border_values);
                $style_indexes[$i]['border_idx'] = self::add_to_list_get_index($borders, json_encode($border_values));
            }
            if (isset($style['fill']) && \is_string($style['fill']) && $style['fill'][0] === '#') {
                $v                             = substr($style['fill'], 1, 6);
                $v                             = \strlen($v) === 3 ? $v[0].$v[0].$v[1].$v[1].$v[2].$v[2] : $v; // expand cf0 => ccff00
                $style_indexes[$i]['fill_idx'] = self::add_to_list_get_index($fills, 'FF'.strtoupper($v));
            }
            if (isset($style['halign']) && \in_array($style['halign'], $horizontal_allowed, true)) {
                $style_indexes[$i]['alignment'] = true;
                $style_indexes[$i]['halign']    = $style['halign'];
            }
            if (isset($style['valign']) && \in_array($style['valign'], $vertical_allowed, true)) {
                $style_indexes[$i]['alignment'] = true;
                $style_indexes[$i]['valign']    = $style['valign'];
            }
            if (isset($style['text_rotation'])) {
                $style_indexes[$i]['alignment']     = true;
                $style_indexes[$i]['text_rotation'] = (int) $style['text_rotation'];
            }
            if (isset($style['indent'])) {
                $style_indexes[$i]['alignment'] = true;
                $style_indexes[$i]['indent']    = (int) $style['indent'];
            }
            if (isset($style['wrap_text'])) {
                $style_indexes[$i]['alignment'] = true;
                $style_indexes[$i]['wrap_text'] = (bool) $style['wrap_text'];
            }
            if (isset($style['shrink_to_fit'])) {
                $style_indexes[$i]['alignment']     = true;
                $style_indexes[$i]['shrink_to_fit'] = (bool) $style['shrink_to_fit'];
            }

            $font = $default_font;
            if (isset($style['font-size'])) {
                $font['size'] = (float) ($style['font-size']); //floatval to allow "10.5" etc
            }
            if (isset($style['font']) && \is_string($style['font'])) {
                if ($style['font'] === 'Comic Sans MS') {
                    $font['family'] = 4;
                }
                if ($style['font'] === 'Times New Roman') {
                    $font['family'] = 1;
                }
                if ($style['font'] === 'Courier New') {
                    $font['family'] = 3;
                }
                $font['name'] = (string) ($style['font']);
            }
            if (isset($style['font-style']) && \is_string($style['font-style'])) {
                if (str_contains($style['font-style'], 'bold')) {
                    $font['bold'] = true;
                }
                if (str_contains($style['font-style'], 'italic')) {
                    $font['italic'] = true;
                }
                if (str_contains($style['font-style'], 'strike')) {
                    $font['strike'] = true;
                }
                if (str_contains($style['font-style'], 'underline')) {
                    $font['underline'] = true;
                }
            }
            if (isset($style['color']) && \is_string($style['color']) && $style['color'][0] === '#') {
                $v             = substr($style['color'], 1, 6);
                $v             = \strlen($v) === 3 ? $v[0].$v[0].$v[1].$v[1].$v[2].$v[2] : $v; // expand cf0 => ccff00
                $font['color'] = 'FF'.strtoupper($v);
            }
            if ($font !== $default_font) {
                $style_indexes[$i]['font_idx'] = self::add_to_list_get_index($fonts, json_encode($font));
            }
        }

        return ['fills' => $fills, 'fonts' => $fonts, 'borders' => $borders, 'styles' => $style_indexes];
    }

    protected function writeStylesXML()
    {
        $r             = $this->styleFontIndexes();
        $fills         = $r['fills'];
        $fonts         = $r['fonts'];
        $borders       = $r['borders'];
        $style_indexes = $r['styles'];

        $temporary_filename = $this->tempFilename();
        $file               = new XLSXWriter_BuffererWriter($temporary_filename);
        $file->write('<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n");
        $file->write('<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">');
        $file->write('<numFmts count="'.\count($this->number_formats).'">');
        foreach ($this->number_formats as $i => $v) {
            $file->write('<numFmt numFmtId="'.(164 + $i).'" formatCode="'.self::xmlspecialchars($v).'" />');
        }
        //$file->write(		'<numFmt formatCode="GENERAL" numFmtId="164"/>');
        //$file->write(		'<numFmt formatCode="[$$-1009]#,##0.00;[RED]\-[$$-1009]#,##0.00" numFmtId="165"/>');
        //$file->write(		'<numFmt formatCode="YYYY-MM-DD\ HH:MM:SS" numFmtId="166"/>');
        //$file->write(		'<numFmt formatCode="YYYY-MM-DD" numFmtId="167"/>');
        $file->write('</numFmts>');

        $file->write('<fonts count="'.(\count($fonts)).'">');
        $file->write('<font><name val="Arial"/><charset val="1"/><family val="2"/><sz val="10"/></font>');
        $file->write('<font><name val="Arial"/><family val="0"/><sz val="10"/></font>');
        $file->write('<font><name val="Arial"/><family val="0"/><sz val="10"/></font>');
        $file->write('<font><name val="Arial"/><family val="0"/><sz val="10"/></font>');

        foreach ($fonts as $font) {
            if (!empty($font)) { //fonts have 4 empty placeholders in array to offset the 4 static xml entries above
                $f = json_decode($font, true);
                $file->write('<font>');
                $file->write('<name val="'.htmlspecialchars($f['name']).'"/><charset val="1"/><family val="'.(int) ($f['family']).'"/>');
                $file->write('<sz val="'.(int) ($f['size']).'"/>');
                if (!empty($f['color'])) {
                    $file->write('<color rgb="'.(string) ($f['color']).'"/>');
                }
                if (!empty($f['bold'])) {
                    $file->write('<b val="true"/>');
                }
                if (!empty($f['italic'])) {
                    $file->write('<i val="true"/>');
                }
                if (!empty($f['underline'])) {
                    $file->write('<u val="single"/>');
                }
                if (!empty($f['strike'])) {
                    $file->write('<strike val="true"/>');
                }
                $file->write('</font>');
            }
        }
        $file->write('</fonts>');

        $file->write('<fills count="'.(\count($fills)).'">');
        $file->write('<fill><patternFill patternType="none"/></fill>');
        $file->write('<fill><patternFill patternType="gray125"/></fill>');
        foreach ($fills as $fill) {
            if (!empty($fill)) { //fills have 2 empty placeholders in array to offset the 2 static xml entries above
                $file->write('<fill><patternFill patternType="solid"><fgColor rgb="'.(string) $fill.'"/><bgColor indexed="64"/></patternFill></fill>');
            }
        }
        $file->write('</fills>');

        $file->write('<borders count="'.(\count($borders)).'">');
        $file->write('<border diagonalDown="false" diagonalUp="false"><left/><right/><top/><bottom/><diagonal/></border>');
        foreach ($borders as $border) {
            if (!empty($border)) { //fonts have an empty placeholder in the array to offset the static xml entry above
                $pieces = json_decode($border, true);
                $file->write('<border diagonalDown="false" diagonalUp="false">');
                foreach (['left', 'right', 'top', 'bottom'] as $side) {
                    $show_side = isset($pieces[$side]) ? true : false;
                    if ($show_side) {
                        $style = "style=\"{$pieces[$side]['style']}\"";
                        $color = $pieces[$side]['color'] === ''
                        ? '<color auto="1"/>'
                        : "<color rgb=\"{$pieces[$side]['color']}\"/>";
                    }
                    $file->write($show_side ? "<{$side} {$style}>{$color}</{$side}>" : "<{$side}/>");
                }
                $file->write('<diagonal/>');
                $file->write('</border>');
            }
        }
        $file->write('</borders>');

        $file->write('<cellStyleXfs count="20">');
        $file->write('<xf applyAlignment="true" applyBorder="true" applyFont="true" applyProtection="true" borderId="0" fillId="0" fontId="0" numFmtId="164">');
        $file->write('<alignment horizontal="general" indent="0" shrinkToFit="false" textRotation="0" vertical="bottom" wrapText="false"/>');
        $file->write('<protection hidden="false" locked="true"/>');
        $file->write('</xf>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="2" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="2" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="0"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="43"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="41"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="44"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="42"/>');
        $file->write('<xf applyAlignment="false" applyBorder="false" applyFont="true" applyProtection="false" borderId="0" fillId="0" fontId="1" numFmtId="9"/>');
        $file->write('</cellStyleXfs>');

        $file->write('<cellXfs count="'.(\count($style_indexes)).'">');
        //$file->write(		'<xf applyAlignment="false" applyBorder="false" applyFont="false" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="164" xfId="0"/>');
        //$file->write(		'<xf applyAlignment="false" applyBorder="false" applyFont="false" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="165" xfId="0"/>');
        //$file->write(		'<xf applyAlignment="false" applyBorder="false" applyFont="false" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="166" xfId="0"/>');
        //$file->write(		'<xf applyAlignment="false" applyBorder="false" applyFont="false" applyProtection="false" borderId="0" fillId="0" fontId="0" numFmtId="167" xfId="0"/>');
        foreach ($style_indexes as $v) {
            $applyAlignment = isset($v['alignment']) ? 'true' : 'false';
            $wrapText       = !empty($v['wrap_text']) ? 'true' : 'false';
            $shrinkToFit    = !empty($v['shrink_to_fit']) ? 'true' : 'false';
            $horizAlignment = $v['halign'] ?? 'general';
            $vertAlignment  = $v['valign'] ?? 'bottom';
            $textRotation   = $v['text_rotation'] ?? '0';
            $indent         = $v['indent'] ?? '0';
            $applyBorder    = isset($v['border_idx']) ? 'true' : 'false';
            $applyFont      = 'true';
            $borderIdx      = isset($v['border_idx']) ? (int) ($v['border_idx']) : 0;
            $fillIdx        = isset($v['fill_idx']) ? (int) ($v['fill_idx']) : 0;
            $fontIdx        = isset($v['font_idx']) ? (int) ($v['font_idx']) : 0;
            //$file->write('<xf applyAlignment="'.$applyAlignment.'" applyBorder="'.$applyBorder.'" applyFont="'.$applyFont.'" applyProtection="false" borderId="'.($borderIdx).'" fillId="'.($fillIdx).'" fontId="'.($fontIdx).'" numFmtId="'.(164+$v['num_fmt_idx']).'" xfId="0"/>');
            $file->write('<xf applyAlignment="'.$applyAlignment.'" applyBorder="'.$applyBorder.'" applyFont="'.$applyFont.'" applyProtection="false" borderId="'.($borderIdx).'" fillId="'.($fillIdx).'" fontId="'.($fontIdx).'" numFmtId="'.(164 + $v['num_fmt_idx']).'" xfId="0">');
            $file->write('	<alignment horizontal="'.$horizAlignment.'" vertical="'.$vertAlignment.'" textRotation="'.$textRotation.'" wrapText="'.$wrapText.'" indent="'.$indent.'" shrinkToFit="'.$shrinkToFit.'"/>');
            $file->write('	<protection locked="true" hidden="false"/>');
            $file->write('</xf>');
        }
        $file->write('</cellXfs>');
        $file->write('<cellStyles count="6">');
        $file->write('<cellStyle builtinId="0" customBuiltin="false" name="Normal" xfId="0"/>');
        $file->write('<cellStyle builtinId="3" customBuiltin="false" name="Comma" xfId="15"/>');
        $file->write('<cellStyle builtinId="6" customBuiltin="false" name="Comma [0]" xfId="16"/>');
        $file->write('<cellStyle builtinId="4" customBuiltin="false" name="Currency" xfId="17"/>');
        $file->write('<cellStyle builtinId="7" customBuiltin="false" name="Currency [0]" xfId="18"/>');
        $file->write('<cellStyle builtinId="5" customBuiltin="false" name="Percent" xfId="19"/>');
        $file->write('</cellStyles>');
        $file->write('</styleSheet>');
        $file->close();

        return $temporary_filename;
    }

    protected function buildAppXML()
    {
        $app_xml = '';
        $app_xml .= '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n";
        $app_xml .= '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">';
        $app_xml .= '<TotalTime>0</TotalTime>';
        $app_xml .= '<Company>'.self::xmlspecialchars($this->company).'</Company>';
        $app_xml .= '</Properties>';

        return $app_xml;
    }

    protected function buildCoreXML()
    {
        $core_xml = '';
        $core_xml .= '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n";
        $core_xml .= '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
        $core_xml .= '<dcterms:created xsi:type="dcterms:W3CDTF">'.date('Y-m-d\\TH:i:s.00\\Z').'</dcterms:created>'; //$date_time = '2014-10-25T15:54:37.00Z';
        $core_xml .= '<dc:title>'.self::xmlspecialchars($this->title).'</dc:title>';
        $core_xml .= '<dc:subject>'.self::xmlspecialchars($this->subject).'</dc:subject>';
        $core_xml .= '<dc:creator>'.self::xmlspecialchars($this->author).'</dc:creator>';
        if (!empty($this->keywords)) {
            $core_xml .= '<cp:keywords>'.self::xmlspecialchars(implode(', ', (array) $this->keywords)).'</cp:keywords>';
        }
        $core_xml .= '<dc:description>'.self::xmlspecialchars($this->description).'</dc:description>';
        $core_xml .= '<cp:revision>0</cp:revision>';
        $core_xml .= '</cp:coreProperties>';

        return $core_xml;
    }

    protected function buildRelationshipsXML()
    {
        $rels_xml = '';
        $rels_xml .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $rels_xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $rels_xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>';
        $rels_xml .= '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>';
        $rels_xml .= '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>';
        $rels_xml .= "\n";
        $rels_xml .= '</Relationships>';

        return $rels_xml;
    }

    protected function buildWorkbookXML()
    {
        $i            = 0;
        $workbook_xml = '';
        $workbook_xml .= '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n";
        $workbook_xml .= '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $workbook_xml .= '<fileVersion appName="Calc"/><workbookPr backupFile="false" showObjects="all" date1904="false"/><workbookProtection/>';
        $workbook_xml .= '<bookViews><workbookView activeTab="0" firstSheet="0" showHorizontalScroll="true" showSheetTabs="true" showVerticalScroll="true" tabRatio="212" windowHeight="8192" windowWidth="16384" xWindow="0" yWindow="0"/></bookViews>';
        $workbook_xml .= '<sheets>';
        foreach ($this->sheets as $sheet_name => $sheet) {
            $sheetname = self::sanitize_sheetname($sheet->sheetname);
            $workbook_xml .= '<sheet name="'.self::xmlspecialchars($sheetname).'" sheetId="'.($i + 1).'" state="visible" r:id="rId'.($i + 2).'"/>';
            ++$i;
        }
        $workbook_xml .= '</sheets>';
        $workbook_xml .= '<definedNames>';
        foreach ($this->sheets as $sheet_name => $sheet) {
            if ($sheet->auto_filter) {
                $sheetname = self::sanitize_sheetname($sheet->sheetname);
                $workbook_xml .= '<definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">\''.self::xmlspecialchars($sheetname).'\'!$A$1:'.self::xlsCell($sheet->row_count - 1, \count($sheet->columns) - 1, true).'</definedName>';
                ++$i;
            }
        }
        $workbook_xml .= '</definedNames>';
        $workbook_xml .= '<calcPr iterateCount="100" refMode="A1" iterate="false" iterateDelta="0.001"/></workbook>';

        return $workbook_xml;
    }

    protected function buildWorkbookRelsXML()
    {
        $i            = 0;
        $wkbkrels_xml = '';
        $wkbkrels_xml .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $wkbkrels_xml .= '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        $wkbkrels_xml .= '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
        foreach ($this->sheets as $sheet_name => $sheet) {
            $wkbkrels_xml .= '<Relationship Id="rId'.($i + 2).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/'.($sheet->xmlname).'"/>';
            ++$i;
        }
        $wkbkrels_xml .= "\n";
        $wkbkrels_xml .= '</Relationships>';

        return $wkbkrels_xml;
    }

    protected function buildContentTypesXML()
    {
        $content_types_xml = '';
        $content_types_xml .= '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $content_types_xml .= '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">';
        $content_types_xml .= '<Override PartName="/_rels/.rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        $content_types_xml .= '<Override PartName="/xl/_rels/workbook.xml.rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>';
        foreach ($this->sheets as $sheet_name => $sheet) {
            $content_types_xml .= '<Override PartName="/xl/worksheets/'.($sheet->xmlname).'" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $content_types_xml .= '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>';
        $content_types_xml .= '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $content_types_xml .= '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>';
        $content_types_xml .= '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>';
        $content_types_xml .= "\n";
        $content_types_xml .= '</Types>';

        return $content_types_xml;
    }

    private function addCellStyle($number_format, $cell_style_string)
    {
        $number_format_idx = self::add_to_list_get_index($this->number_formats, $number_format);
        $lookup_string     = $number_format_idx.';'.$cell_style_string;

        return self::add_to_list_get_index($this->cell_styles, $lookup_string);
    }

    private function initializeColumnTypes($header_types)
    {
        $column_types = [];
        foreach ($header_types as $v) {
            $number_format      = self::numberFormatStandardized($v);
            $number_format_type = self::determineNumberFormatType($number_format);
            $cell_style_idx     = $this->addCellStyle($number_format, $style_string = null);
            $column_types[]     = ['number_format' => $number_format, //contains excel format like 'YYYY-MM-DD HH:MM:SS'
                'number_format_type'               => $number_format_type, //contains friendly format like 'datetime'
                'default_cell_style'               => $cell_style_idx,
            ];
        }

        return $column_types;
    }

    //------------------------------------------------------------------
    private static function determineNumberFormatType($num_format)
    {
        $num_format = preg_replace('/\\[(Black|Blue|Cyan|Green|Magenta|Red|White|Yellow)\\]/i', '', $num_format);
        if ($num_format === 'GENERAL') {
            return 'n_auto';
        }
        if ($num_format === '@') {
            return 'n_string';
        }
        if ($num_format === '0') {
            return 'n_numeric';
        }
        if (preg_match('/[H]{1,2}:[M]{1,2}(?![^"]*+")/i', $num_format)) {
            return 'n_datetime';
        }
        if (preg_match('/[M]{1,2}:[S]{1,2}(?![^"]*+")/i', $num_format)) {
            return 'n_datetime';
        }
        if (preg_match('/[Y]{2,4}(?![^"]*+")/i', $num_format)) {
            return 'n_date';
        }
        if (preg_match('/[D]{1,2}(?![^"]*+")/i', $num_format)) {
            return 'n_date';
        }
        if (preg_match('/[M]{1,2}(?![^"]*+")/i', $num_format)) {
            return 'n_date';
        }
        if (preg_match('/$(?![^"]*+")/', $num_format)) {
            return 'n_numeric';
        }
        if (preg_match('/%(?![^"]*+")/', $num_format)) {
            return 'n_numeric';
        }
        if (preg_match('/0(?![^"]*+")/', $num_format)) {
            return 'n_numeric';
        }

        return 'n_auto';
    }

    //------------------------------------------------------------------
    private static function numberFormatStandardized($num_format)
    {
        if ($num_format === 'money') {
            $num_format = 'dollar';
        }
        if ($num_format === 'number') {
            $num_format = 'integer';
        }

        if ($num_format === 'string') {
            $num_format = '@';
        } elseif ($num_format === 'integer') {
            $num_format = '0';
        } elseif ($num_format === 'date') {
            $num_format = 'YYYY-MM-DD';
        } elseif ($num_format === 'datetime') {
            $num_format = 'YYYY-MM-DD HH:MM:SS';
        } elseif ($num_format === 'time') {
            $num_format = 'HH:MM:SS';
        } elseif ($num_format === 'price') {
            $num_format = '#,##0.00';
        } elseif ($num_format === 'dollar') {
            $num_format = '[$$-1009]#,##0.00;[RED]-[$$-1009]#,##0.00';
        } elseif ($num_format === 'euro') {
            $num_format = '#,##0.00 [$€-407];[RED]-#,##0.00 [$€-407]';
        }
        $ignore_until = '';
        $escaped      = '';
        for ($i = 0,$ix = \strlen($num_format); $i < $ix; ++$i) {
            $c = $num_format[$i];
            if ($ignore_until === '' && $c === '[') {
                $ignore_until = ']';
            } elseif ($ignore_until === '' && $c === '"') {
                $ignore_until = '"';
            } elseif ($ignore_until === $c) {
                $ignore_until = '';
            }
            if ($ignore_until === '' && ($c === ' ' || $c === '-' || $c === '(' || $c === ')') && ($i === 0 || $num_format[$i - 1] !== '_')) {
                $escaped .= '\\'.$c;
            } else {
                $escaped .= $c;
            }
        }

        return $escaped;
    }

    //------------------------------------------------------------------
}

class XLSXWriter_BuffererWriter
{
    protected $fd;
    protected $buffer     = '';
    protected $check_utf8 = false;

    public function __construct($filename, $fd_fopen_flags = 'w', $check_utf8 = false)
    {
        $this->check_utf8 = $check_utf8;
        $this->fd         = fopen($filename, $fd_fopen_flags);
        if ($this->fd === false) {
            XLSXWriter::log("Unable to open {$filename} for writing.");
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public function write($string): void
    {
        $this->buffer .= $string;
        if (isset($this->buffer[8191])) {
            $this->purge();
        }
    }

    public function close(): void
    {
        $this->purge();
        if ($this->fd) {
            fclose($this->fd);
            $this->fd = null;
        }
    }

    public function ftell()
    {
        if ($this->fd) {
            $this->purge();

            return ftell($this->fd);
        }

        return -1;
    }

    public function fseek($pos)
    {
        if ($this->fd) {
            $this->purge();

            return fseek($this->fd, $pos);
        }

        return -1;
    }

    protected function purge(): void
    {
        if ($this->fd) {
            if ($this->check_utf8 && !self::isValidUTF8($this->buffer)) {
                XLSXWriter::log('Error, invalid UTF8 encoding detected.');
                $this->check_utf8 = false;
            }
            fwrite($this->fd, $this->buffer);
            $this->buffer = '';
        }
    }

    protected static function isValidUTF8($string)
    {
        if (\function_exists('mb_check_encoding')) {
            return mb_check_encoding($string, 'UTF-8') ? true : false;
        }

        return preg_match('//u', $string) ? true : false;
    }
}
