<?php

declare(strict_types=1);

namespace Libs\Mailer;

use Exception;

// エラーメッセージ用日本語言語ファイルを読み込む場合
require 'vendor/phpmailer/phpmailer/language/phpmailer.lang-ja.php';

/**
 * メール送信クラス.
 */
class Mailer
{
    private $mail;

    public function __construct()
    {
        mb_language('japanese');
        mb_internal_encoding('UTF-8');

        $this->mail = new PHPMailer(true);

        // 日本語用設定(UTF-8でよければ不要)
        $this->mail->CharSet  = 'iso-2022-jp';
        $this->mail->Encoding = '7bit';

        // エラーメッセージ用言語ファイルを使用する場合に指定
        $this->mail->setLanguage('ja', 'vendor/phpmailer/phpmailer/language/');
    }

    /**
     * メールの送信
     */
    public function send(MailItem $item): bool
    {
        try {
            // 送信者(FROM) ============================================================================================
            $this->mail->setFrom($item->getFrom()->getAddress(), $item->getFrom()->getName());

            // 送信先(TO) ==============================================================================================
            foreach ($item->getTo() as $address) {
                $this->mail->addAddress($address->getAddress(), $address->getName());
            }

            // CC ======================================================================================================
            foreach ($item->getCc() as $address) {
                $this->mail->addCC($address->getAddress(), $address->getName());
            }

            // BCC =====================================================================================================
            foreach ($item->getBcc() as $address) {
                $this->mail->addBCC($address->getAddress(), $address->getName());
            }

            // 返信先(REPLY_TO) ========================================================================================
            foreach ($item->getReplyTo() as $address) {
                $this->mail->addReplyTo($address->getAddress(), $address->getName());
            }

            // 件名 ====================================================================================================
            $this->mail->Subject = $item->getSubject();

            // 本文 ====================================================================================================
            if ($item->isHtml()) {
                // HTMLメール ======================================================================
                // コンテンツ設定
                $this->mail->isHTML(true);

                // HTML形式の本文
                $this->mail->Body = $item->getHtml();

                // テキスト形式の本文
                $this->mail->AltBody = $item->getBody();
            } else {
                // テキストメール ==================================================================
                // コンテンツ設定
                $this->mail->isHTML(false);

                // テキスト形式の本文
                $this->mail->Body = $item->getBody();
            }

            // 送信
            return $this->mail->send();
        } catch (Exception $e) {
            // エラー（例外：Exception）が発生した場合
            echo "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
        }
    }
}
