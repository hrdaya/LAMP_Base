<?php

declare(strict_types=1);

namespace App\Libs\Mailer;

use RuntimeException;

class MailItem
{
    private const TO_ENCODING = 'ISO-2022-JP-MS';

    /**
     * @var Address 送信元
     */
    private Address $from;

    /**
     * @var Address[] 送信先のコレクション
     */
    private array $to;

    /**
     * @var string 件名
     */
    private string $subjct;

    /**
     * @var string テキスト本文
     */
    private string $body;

    /**
     * @var string HTML本文
     */
    private string $html;

    /**
     * @var Address[] CCのコレクション
     */
    private array $cc;

    /**
     * @var Address[] BCCのコレクション
     */
    private array $bcc;

    /**
     * @var Address[] メール返信先のコレクション
     */
    private array $replyTo;

    /**
     * コンストラクタ
     *
     * @param Address   $from    送信元
     * @param Address[] $to      送信先
     * @param string    $subjct  件名
     * @param string    $body    テキスト本文
     * @param string    $html    HTML本文
     * @param Address[] $cc      CC
     * @param Address[] $bcc     BCC
     * @param Address[] $ReplyTo メール返信先
     */
    public function __construct(
        Address $from,
        array $to,
        string $subjct,
        string $body,
        string $html = '',
        array $cc = [],
        array $bcc = [],
        array $replyTo = []
    ) {
        $this->from   = $from;
        $this->subjct = $subjct;
        $this->body   = $body;
        $this->html   = $html;

        // Addressのコレクションの項目
        foreach (compact('to', 'cc', 'bcc', 'replyTo') as $key => $rows) {
            $this->addAddressArray($key, $rows);
        }
    }

    /**
     * Fromのアドレスを取得.
     */
    public function getFrom(): Address
    {
        return $this->from;
    }

    /**
     * Toのアドレスのリストを取得.
     *
     * @return Address[]
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * 件名.
     */
    public function getSubject(): string
    {
        return mb_encode_mimeheader($this->subjct);
    }

    /**
     * テキスト本文.
     */
    public function getBody(): string
    {
        return mb_convert_encoding($this->body, self::TO_ENCODING, 'UTF-8');
    }

    /**
     * HTML本文.
     */
    public function getHtml(): string
    {
        return mb_convert_encoding($this->html, self::TO_ENCODING, 'UTF-8');
    }

    /**
     * HTMLメールかどうか.
     *
     * @return bool HTMLにテキストが入っているときに true
     */
    public function isHtml(): bool
    {
        return $this->html !== '';
    }

    /**
     * CCのアドレスのリストを取得.
     *
     * @return Address[]
     */
    public function getCc(): array
    {
        return $this->cc;
    }

    /**
     * BCCのアドレスのリストを取得.
     *
     * @return Address[]
     */
    public function getBcc(): array
    {
        return $this->bcc;
    }

    /**
     * ReplyToのアドレスのリストを取得.
     *
     * @return Address[]
     */
    public function getReplyTo(): array
    {
        return $this->replyTo;
    }

    /**
     * Addressのコレクションに値をセットする.
     *
     * @param string $key  セットするプロパティのキー
     * @param array  $rows セットする値の配列
     */
    private function addAddressArray(string $key, array $rows): void
    {
        foreach ($rows as $row) {
            if ($row instanceof Address) {
                $this->{$key}[] = $row;
            } elseif (\is_string($row)) {
                $this->{$key}[] = new Address($row);
            } elseif (\is_array($row) && \count($row) > 0) {
                $this->{$key}[] = new Address($row[0], $row[1] ?? '');
            }

            throw new RuntimeException(sprintf('[%s] に送信アドレスとして正しくない値が含まれています。', $key));
        }
    }
}
