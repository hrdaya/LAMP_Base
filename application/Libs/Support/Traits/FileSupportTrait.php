<?php

declare(strict_types=1);

namespace App\Libs\Support\Traits;

use DateTime;
use RuntimeException;

/**
 * ファイル読み書き用のトレイト.
 */
trait FileSupportTrait
{
    /**
     * ファイルを読み込むことができるかを確認する.
     *
     * @throws \RuntimeException ファイルが存在しないとき
     * @throws \RuntimeException ファイルに読み込み権限が存在しないとき
     */
    public function fileReadable(string $filePath): bool
    {
        if ($this->isFile($filePath) === false) {
            throw new RuntimeException(sprintf('ファイルが存在しません。[%s]', $filePath));
        }

        if ($this->isReadable($filePath) === false) {
            throw new RuntimeException(sprintf('ファイルに読み込み権限が存在しません。[%s]', $filePath));
        }

        return true;
    }

    /**
     * 保存先のディレクトリを作成して、作成したディレクトリを返す.
     *
     * @throws \RuntimeException ディレクトリを作成できなかったとき
     * @throws \RuntimeException ディレクトリのパーミッションを変更できなかったとき
     * @throws \RuntimeException ディレクトリに書き込み権限が無かったとき
     *
     * @return string ファイル名を除いたディレクトリのパス
     */
    public function makeWriteDirectory(string $filePath): string
    {
        $directory = $this->getPath($filePath);

        if ($this->isDirectory($directory) === false) {
            if ($this->makeDirectory($directory, 0777, true) === false) {
                throw new RuntimeException(sprintf('ディレクトリの作成に失敗しました。[%s]', $directory));
            }

            if ($this->chmod($directory, 0775) === false) {
                throw new RuntimeException(sprintf('ディレクトリのパーミッション変更に失敗しました。[%s]', $directory));
            }
        }

        if ($this->isWritable($directory)) {
            throw new RuntimeException(sprintf('ディレクトリに書き込み権限がありません。[%s]', $directory));
        }

        return $directory;
    }

    /**
     * ファイル名に「_YYMMDD_HHMMSS_US」のサフィックスを付けて返す.
     */
    public function addSuffix(string $filePath, string $datetimeFormat = '_ymd_His_u'): string
    {
        $this->setLocale();

        $pathinfo = pathinfo($filePath);

        $fileName = $pathinfo['filename'];

        $suffix = (new DateTime())->format($datetimeFormat);

        return $pathinfo['dirname'].\DIRECTORY_SEPARATOR.$fileName.$suffix.'.'.$pathinfo['extension'];
    }

    /**
     * ファイル名のみを拡張子付きで取得する.
     */
    public function getBasename(string $filePath): string
    {
        $this->setLocale();

        return pathinfo($filePath)['basename'];
    }

    /**
     * 拡張子を除いたファイル名を取得.
     */
    public function getFileName(string $filePath): string
    {
        $this->setLocale();

        return pathinfo($filePath)['filename'];
    }

    /**
     * 拡張子を取得する.
     */
    public function getExtension(string $filePath): string
    {
        $this->setLocale();

        return pathinfo($filePath)['extension'];
    }

    /**
     * ファイル名を除いたパスを取得する.
     */
    public function getPath(string $filePath): string
    {
        $this->setLocale();

        return pathinfo($filePath)['dirname'];
    }

    /**
     * 実行されているOSがWindowsかどうか.
     *
     * @return bool Windowsのときにtrue
     */
    private function isWin(): bool
    {
        return strncasecmp(PHP_OS, 'WIN', 3) === 0;
    }

    /**
     * ロケールをセット.
     */
    private function setLocale(): void
    {
        setlocale(LC_CTYPE, $this->isWin() ? 'Japanese_Japan.932' : 'ja_JP.UTF-8');
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param string $directory
     *
     * @return bool
     */
    private function isDirectory($directory)
    {
        return is_dir($directory);
    }

    /**
     * Determine if the given path is readable.
     *
     * @param string $path
     *
     * @return bool
     */
    private function isReadable($path)
    {
        return is_readable($path);
    }

    /**
     * Determine if the given path is writable.
     *
     * @param string $path
     *
     * @return bool
     */
    private function isWritable($path)
    {
        return is_writable($path);
    }

    /**
     * Determine if the given path is a file.
     *
     * @param string $file
     *
     * @return bool
     */
    private function isFile($file)
    {
        return is_file($file);
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int    $mode
     * @param bool   $recursive
     * @param bool   $force
     *
     * @return bool
     */
    private function makeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Get or set UNIX mode of a file or directory.
     *
     * @param string   $path
     * @param null|int $mode
     *
     * @return mixed
     */
    private function chmod($path, $mode = null)
    {
        if ($mode) {
            return chmod($path, $mode);
        }

        return substr(sprintf('%o', fileperms($path)), -4);
    }
}
