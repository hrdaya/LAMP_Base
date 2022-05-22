<?php

declare(strict_types=1);

namespace Libs\Mailer;

use RuntimeException;

class Address
{
    /**
     * @var string メールアドレス
     */
    private string $address = '';

    /**
     * @var string 名前
     */
    private string $name = '';

    public function __construct(string $address, string $name = '')
    {
        if (PHPMailer::validateAddress($address, 'mobile') === false) {
            throw new RuntimeException(sprintf('[%s] はメールアドレスとして正しくありません。', $address));
        }

        // DocomoかAUのアドレスでRFC違反の場合は@より前をダブルクォーテーションで囲む
        if (is_mobile_address($address) && filter_var($address, FILTER_VALIDATE_EMAIL) === false) {
            $split   = explode('@', $address);
            $address = "\"{$split[0]}\"@{$split[1]}";
        }

        $this->address = $address;

        $this->name = $name;
    }

    /**
     * メールアドレスを取得.
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * 名前を取得.
     */
    public function getName(): string
    {
        return mb_encode_mimeheader($this->name);
    }
}
