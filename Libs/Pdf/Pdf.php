<?php

declare(strict_types=1);

namespace Libs\Pdf;

use Libs\Support\FileSupportTrait;
use OutOfBoundsException;
use RuntimeException;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * PDF出力の為のラッパー
 *
 * @see https://tcpdf.org/examples/
 * @see https://www.setasign.com/products/fpdi/about/
 * @see https://takahashi-it.com/php/pdf-template-tcpdf-fpdi/
 * @see http://tcpdf.penlabo.net/pickup.html
 * @see http://www.t-net.ne.jp/~cyfis/tcpdf/index.html
 */
class Pdf extends Fpdi
{
    use FileSupportTrait;

    // 用紙の向き ==============================================================
    public const PAGE_ORIENTATION_PORTRAIT  = 'P'; // 縦
    public const PAGE_ORIENTATION_LANDSCAPE = 'L'; // 横

    // フォントの種類 ==========================================================
    public const FONT_GOTHIC = 'kozgopromedium';   // 小塚ゴシックPro M
    public const FONT_MINCHO = 'kozminproregular'; // 小塚明朝Pro M

    // フォントのデフォルト設定 ================================================
    public const FONT_DEFAULT_SIZE = 12;

    // フォントのスタイル ======================================================
    public const FONT_STYLE_NONE      = '';  // スタイル無し
    public const FONT_STYLE_BOLD      = 'B'; // 太字
    public const FONT_STYLE_ITALIC    = 'I'; // イタリック
    public const FONT_STYLE_UNDERLINE = 'U'; // 下線
    public const FONT_STYLE_DLETE     = 'D'; // 取り消し線

    // セル内の水平位置 ========================================================
    public const CELL_HORIZONTAL_LEFT    = 'L'; // 左寄せ
    public const CELL_HORIZONTAL_CENTER  = 'C'; // 真ん中
    public const CELL_HORIZONTAL_RIGHT   = 'R'; // 右寄せ
    public const CELL_HORIZONTAL_JUSTIFY = 'J'; // 均等配置

    // セル内の垂直位置 ========================================================
    public const CELL_VERTICAL_TOP    = 'T'; // 上
    public const CELL_VERTICAL_MID    = 'M'; // 中央
    public const CELL_VERTICAL_BOTTOM = 'B'; // 下

    // 画像挿入後のポインタの位置 ==============================================
    public const IMAGE_AFTER_TOP       = 'T'; // 右上
    public const IMAGE_AFTER_MID       = 'M'; // 右中央
    public const IMAGE_AFTER_BOTTOM    = 'B'; // 右下
    public const IMAGE_AFTER_NEXT_LINE = 'N'; // 次の行

    // 画像を現在の行のどこに入れるか ==========================================
    public const IMAGE_ALIGN_DEFAULT = '';  // デフォルト(左)
    public const IMAGE_ALIGN_LEFT    = 'L'; // 左寄せ
    public const IMAGE_ALIGN_CENTER  = 'C'; // 中央
    public const IMAGE_ALIGN_RIGHT   = 'R'; // 右寄せ

    // 枠線 ====================================================================
    public const BORDER_NONE   = 0;   // 無し
    public const BORDER_ALL    = 1;   // 全て
    public const BORDER_TOP    = 'T'; // 上
    public const BORDER_RIGHT  = 'R'; // 右
    public const BORDER_BOTTOM = 'B'; // 下
    public const BORDER_LEFT   = 'L'; // 左

    // 塗りつぶし ==============================================================
    public const FILL_TRANSPARENT = false; // 塗りつぶし無し
    public const FILL_PAINT       = true;  // 塗りつぶし

    // 改行 ====================================================================
    public const LN_NONE      = 0; // 右に移動
    public const LN_NEXT_LINE = 1; // 次の行へ移動
    public const LN_BELOW     = 2; // 下へ移動

    // 前回の行の高さ ==========================================================
    public const HEIGHT_RESET      = true;  // 前の高さを引き継がない
    public const HEIGHT_NONE_RESET = false; // 前の高さを引き継ぐ

    // テキストの伸縮(ストレッチ)モード ========================================
    public const STRETCH_NONE                 = 0; // なし
    public const STRETCH_NECESSARY_HORIZONTAL = 1; // 必要に応じて水平伸縮
    public const STRETCH_ALLWAYS_HORIZONTAL   = 2; // 水平伸縮
    public const STRETCH_NECESSARY_SPACE      = 3; // 必要に応じてスペース埋め
    public const STRETCH_ALLWAYS_SPACE        = 4; // スペース埋め

    // 色 ======================================================================
    // https://www.rapidtables.com/web/color/RGB_Color.html
    public const COLOR_BLACK          = [0,   0,   0];   // 黒
    public const COLOR_DIM_GRAY       = [105, 105, 105]; // 濃いグレー
    public const COLOR_GRAY           = [128, 128, 128]; // グレー
    public const COLOR_DARK_SILVER    = [169, 169, 169]; // 濃いシルバー
    public const COLOR_SILVER         = [192, 192, 192]; // シルバー
    public const COLOR_LIGHT_GRAY     = [211, 211, 211]; // 薄いグレー
    public const COLOR_WHITE          = [255, 255, 255]; // 白
    public const COLOR_RED            = [255, 0,   0];   // 赤
    public const COLOR_LIME           = [0,   255, 0];   // 黄緑
    public const COLOR_BLUE           = [0,   0,   255]; // 青
    public const COLOR_YELLOW         = [255, 255, 0];   // 黄色
    public const COLOR_CYAN           = [0,   255, 255]; // シアン
    public const COLOR_MAGENTA        = [255, 0,   255]; // マゼンダ
    public const COLOR_MAROON         = [128, 0,   0];   // マルーン
    public const COLOR_OLIVE          = [128, 128, 0];   // オリーブ
    public const COLOR_GREEN          = [0,   128, 0];   // 緑
    public const COLOR_PURPLE         = [128, 0,   128]; // 紫
    public const COLOR_TEAL           = [0,   128, 128]; // 青緑
    public const COLOR_NAVY           = [0,   0,   128]; // 濃紺
    public const COLOR_LIGHT_SKY_BLUE = [135, 206, 250]; // 明るい水色
    public const COLOR_LINEN          = [250, 240, 230]; // リネン

    // 色を適用する時のタイプ ==================================================
    public const COLOR_TYPE_DRAW = 'draw'; // 塗りつぶし
    public const COLOR_TYPE_FILL = 'fill'; // セルの背景
    public const COLOR_TYPE_TEXT = 'text'; // 文字の色

    // 線の末端部のスタイル ====================================================
    public const LINE_CAP_BUTT   = 'butt';
    public const LINE_CAP_ROUND  = 'round';
    public const LINE_CAP_SQUARE = 'square';

    // 線の結合部のスタイル ====================================================
    public const LINE_JOIN_MITER = 'miter';
    public const LINE_JOIN_ROUND = 'round';
    public const LINE_JOIN_BEVEL = 'bevel';

    // 画像出力後のカーソル位置 ================================================
    public const IMAGE_NEXT_ALIGN_TOP       = '';  // 右上
    public const IMAGE_NEXT_ALIGN_MIDDLE    = 'M'; // 右中
    public const IMAGE_NEXT_ALIGN_BOTTOM    = 'B'; // 右下
    public const IMAGE_NEXT_ALIGN_NEXT_LINE = 'N'; // 次の行

    // 画像フォーマットの種類 ==================================================
    public const IMAGE_TYPE_JPEG = 'JPEG';
    public const IMAGE_TYPE_PNG  = 'PNG';

    /**
     * テンプレートのパス.
     *
     * @var string
     */
    protected $templatePath = '';

    /**
     * テンプレートで使用するページ.
     *
     * @var int
     */
    protected $templatePage = 1;

    /**
     * デフォルトの用紙の向き.
     *
     * @var string
     */
    protected $defaultOrientation = self::PAGE_ORIENTATION_PORTRAIT;

    /**
     * デフォルトの用紙のサイズ.
     *
     * @var string
     */
    protected $defaultFormat = 'A4';

    /**
     * 新しい TCPDF のインスタンスを作成.
     *
     * @return void
     */
    public function __construct()
    {
        // TCPDF のコンストラクタを実行
        parent::__construct($this->defaultOrientation, 'mm', $this->defaultFormat, true, 'UTF-8');

        // PDF の余白(上左右)を設定
        $this->SetMargins(0, 0, 0);

        // CellPadding を設定
        $this->SetCellPaddings(0, 0, 0, 0);

        // 自動改ページ無効
        $this->SetAutoPageBreak(false);

        // ヘッダーの出力を無効化
        $this->setPrintHeader(false);

        // フッターの出力を無効化
        $this->setPrintFooter(false);

        // 日本語フォント
        $this->SetFont(self::FONT_GOTHIC, '', 10);
    }

    /**
     * テンプレートをセット.
     *
     * @param string $template テンプレートファイル
     * @param int    $page     テンプレートで使用するページ番号を指定
     *
     * @throws OutOfBoundsException ファイルが存在しないとき
     */
    public function setTemplate(string $template, int $page = 1): self
    {
        $file = template_path($template);

        if (!is_file($file)) {
            throw new OutOfBoundsException(sprintf('[%s]がテンプレートフォルダに存在しません。', $file));
        }

        $this->templatePath = $file;
        $this->templatePage = $page;

        return $this;
    }

    /**
     * テンプレートのクリア.
     */
    public function clearTemplate(): self
    {
        $this->templatePath = '';
        $this->templatePage = 1;

        return $this;
    }

    /**
     * ページの追加.
     *
     * @param bool        $useTemplate テンプレートを使用するかどうか
     * @param null|string $orientation 用紙の向き
     * @param null|string $format      用紙のサイズ
     *
     * @throws RuntimeException テンプレートがセットされていないとき
     */
    public function addNewPage(bool $useTemplate = true, string $orientation = null, string $format = null): void
    {
        $this->AddPage($orientation ?? $this->defaultOrientation, $format ?? $this->defaultFormat);

        if ($this->templatePath === '' && $useTemplate) {
            throw new RuntimeException('テンプレートがセットされていません。');
        }

        if ($useTemplate) {
            // テンプレート読み込み
            $this->setSourceFile($this->templatePath);
            $this->useTemplate($this->importPage($this->templatePage), 0, 0, null, null, true);
        }
    }

    /**
     * 色を配列で指定.
     *
     * @param string $type  どこに適用するか ('draw', 'fill', 'text')
     * @param array  $color rgbの配列
     */
    public function setTypeColor(string $type, array $color): void
    {
        $this->setColorArray($type, $color);
    }

    /**
     * テキストの色をセット.
     *
     * @param array $color rgbの配列
     */
    public function setFontColor(array $color): void
    {
        $this->setColorArray('text', $color);
    }

    /**
     * 背景色をセット.
     *
     * @param array $array
     */
    public function setBackgroundColor(array $color): void
    {
        $this->setColorArray('fill', $color);
    }

    /**
     * フォント、テキストカラー設定.
     */
    public function setFontParams(array $params = []): void
    {
        $default = [
            'family' => self::FONT_GOTHIC,
            'style'  => self::FONT_STYLE_NONE,
            'size'   => self::FONT_DEFAULT_SIZE,
            'color'  => self::COLOR_BLACK,
        ];

        $params = array_merge($default, $params);

        // フォント設定
        $this->SetFont($params['family'], $params['style'], $params['size']);

        // テキストカラー設定
        $this->setColorArray('text', $params['color']);
    }

    /**
     * 改行を追加.
     */
    public function addLn(): void
    {
        $this->Ln();
    }

    /**
     * 文字列の書き込み
     *
     * @see http://tcpdf.penlabo.net/method/m/MultiCell.html
     *
     * @param string      $text       書き込む文字列
     * @param array       $params     セルの設定(MultiCell)
     * @param array       $fontParams フォント、テキストカラー設定(setFontParams()に渡す配列)
     * @param array|float $padding    セルのパディングをセット(配列のときは左上右下)
     */
    public function addCell(string $text, array $params = [], array $fontParams = [], $padding = 0): void
    {
        $this->setFontParams($fontParams);

        if (is_numeric($padding)) {
            // 数値が指定されたとき
            $this->SetCellPadding($padding);
        } elseif (\is_array($padding)) {
            // 配列が指定されたとき
            $this->setCellPaddings(...$padding);
        }

        $default = [
            'x'           => '',                         // X座標(省略時は現在位置)
            'y'           => '',                         // Y座標(省略時は現在位置)
            'width'       => null,                       // セル幅、0とすると右端まで
            'height'      => null,                       // セルの最小の高さ(隣のセルと高さを合わせるには$this->getLastH()を渡す)
            'maxheight'   => 0,                          // 高さの上限
            'resetheight' => self::HEIGHT_RESET,         // 前回のセルの高さ設定をリセットする場合はtrue、引き継ぐ場合はfalse
            'halign'      => self::CELL_HORIZONTAL_LEFT, // 水平方向のテキストの整列
            'valign'      => self::CELL_VERTICAL_TOP,    // 垂直方向のテキストの配置
            'border'      => self::BORDER_NONE,          // 枠線の描画方法(array('LTRB' => array('width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(0, 0, 0))))
            'fill'        => self::FILL_TRANSPARENT,     // 背景の塗つぶし指定
            'ln'          => self::LN_NONE,              // 出力後のカーソルの移動方法
            'stretch'     => self::STRETCH_NONE,         // テキストの伸縮(ストレッチ)モード
            'autopadding' => true,                       // 行幅に自動調整する場合にtrueとする
            'fitcell'     => true,                       // trueの場合、フォントサイズを小さくしてセル内にすべてのテキストを収める
        ];

        $params = array_merge($default, $params);

        if (\is_array($params['fill'])) {
            $this->setColorArray('fill', $params['fill']);
            $params['fill'] = self::FILL_PAINT;
        }

        $this->MultiCell(
            $params['width'],
            $params['height'],
            $text,
            $params['border'],
            $params['halign'],
            $params['fill'],
            $params['ln'],
            $params['x'],
            $params['y'],
            $params['resetheight'],
            $params['stretch'],
            false,
            $params['autopadding'],
            $params['maxheight'],
            $params['valign'],
            $params['fitcell']
        );
    }

    /**
     * 線のスタイルを取得.
     *
     * @see http://tcpdf.penlabo.net/method/s/SetLineStyle.html
     */
    public function getLineStyle(array $params): array
    {
        $default = [
            'width' => 0.3,                   // 線の太さ
            'cap'   => self::LINE_CAP_BUTT,   // 線の末端部のスタイル
            'join'  => self::LINE_JOIN_MITER, // 線の結合部のスタイル
            'dash'  => '0',                   // 破線パターン on-offの組み合わせ
            'phase' => 0,                     // 破線パターンの開始位置のシフトする長さ
            'color' => self::COLOR_BLACK,     // 線の色
        ];

        return array_merge($default, $params);
    }

    /**
     * 実線のスタイルを取得.
     */
    public function getLineStyleStrate(array $params): array
    {
        $params         = $this->getLineStyle($params);
        $params['dash'] = '0';

        return $params;
    }

    /**
     * 点線のスタイルを取得.
     */
    public function getLineStyleDot(array $params): array
    {
        $params         = $this->getLineStyle($params);
        $params['dash'] = '1';

        return $params;
    }

    /**
     * 破線のスタイルを取得.
     */
    public function getLineStyleDash(array $params): array
    {
        $params         = $this->getLineStyle($params);
        $params['dash'] = '2';

        return $params;
    }

    /**
     * 線を引く.
     *
     * @see http://tcpdf.penlabo.net/method/s/SetLineStyle.html
     * @see https://system-essence.hatenablog.com/entry/php-tcpdf-4
     */
    public function addLine(array $params): void
    {
        $params = $this->getLineParams($params);

        $this->Line(
            $params['startX'],
            $params['startY'],
            $params['endX'],
            $params['endY'],
            $params['style']
        );
    }

    /**
     * 二重線を引く.
     *
     * @see http://blog.physis.jp/article/112447005.html
     */
    public function addDoubleLine(array $params, float $gap = 0.3): void
    {
        $params = $this->getLineParams($params);

        $gapX = $params['startY'] === $params['endY'] ? 0 : $gap;
        $gapY = $params['startX'] === $params['endX'] ? 0 : $gap;

        $this->Line(
            $params['startX'],
            $params['startY'],
            $params['endX'],
            $params['endY'],
            $params['line']
        );

        $this->Line(
            $params['startX'] + $gapX,
            $params['startY'] + $gapY,
            $params['endX'] + $gapX,
            $params['endY'] + $gapY,
            $params['line']
        );
    }

    /**
     * 画像を描画する.
     *
     * @see http://tcpdf.penlabo.net/method/i/Image.html
     *
     * @param string $filePath 画像ファイルのパス
     * @param array  $params   設定
     */
    public function addImage(string $filePath, array $params = []): void
    {
        $default = [
            'type'      => 'PNG',                     // 画像フォーマット
            'x'         => '',                        // 領域左上のX座標
            'y'         => '',                        // 領域左上のY座標
            'width'     => 0,                         // 領域の幅 [指定しない場合、自動計算される]
            'height'    => 0,                         // 領域の高さ [指定しない場合、自動計算される]
            'align'     => self::IMAGE_AFTER_TOP,     // 画像挿入後のポインタの位置('': 右上, M: 右中, B: 右下, 次の行)
            'palign'    => self::IMAGE_ALIGN_DEFAULT, // 画像を現在の行のどこに配置するか
            'resize'    => false,                     // trueとすると、画像サイズをwidthやheightに合わせてリサイズ(縮小)する(GDライブラリが必要)。
            'fitbox'    => false,                     // tureとすると、(width, height)で指定する領域に合わせて拡縮する
            'fitonpage' => false,                     // tureとすると、頁の大きさに拡大する
            'dpi'       => 300,                       // 解像度・DPI(dot per inch)
            'border'    => self::BORDER_NONE,         // 境界線の描画方法
            'link'      => '',                        // AddLink()で作成したリンク識別子
        ];

        $params = array_merge($default, $params);

        $this->Image(
            $filePath,
            $params['x'],
            $params['y'],
            $params['width'],
            $params['height'],
            $params['type'],
            $params['link'],
            $params['align'],
            $params['resize'],
            $params['dpi'],
            $params['palign'],
            false,
            false,
            $params['border'],
            $params['fitbox'],
            false,
            $params['fitonpage'],
            false,
            []
        );
    }

    /**
     * PDF ファイルを保存.
     *
     * @param string $savePath 保存先のパス
     */
    public function save(string $savePath): void
    {
        // PDF をファイルとして出力
        $this->output($savePath, 'F');
    }

    /**
     * ライン描画用のパラメータをデフォルト値とマージ.
     */
    protected function getLineParams(array $params): array
    {
        $defaults = [
            'startX' => 0, // 開始横位置
            'startY' => 0, // 開始縦位置
            'endX'   => 0, // 終了横位置
            'endY'   => 0, // 終了縦位置
            'style'  => [], // 線のスタイル設定
        ];

        foreach ($defaults as $key => $default) {
            if (!isset($params[$key])) {
                $params[$key] = $default;
            }

            if ($key === 'style') {
                $params[$key] = $this->getLineStyle(\is_array($params[$key]) ? $params[$key] : ['width' => $params[$key]]);
            }
        }

        return $params;
    }
}
