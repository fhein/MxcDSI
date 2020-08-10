<?php

namespace MxcDropshipIntegrator\Jobs;

use Shopware\Models\Mail\Mail;
use MxcCommons\Config\Factory;

class SaveEmailTemplates
{
    public static function run()
    {
        $filename = __DIR__ . '/../Config/MailTemplates.config.php';
        $manager = Shopware()->Container()->get('models');
        $mails = $manager->getRepository(Mail::class)->findAll();

        $store = [];
        /** @var Mail $mail */
        foreach ($mails as $mail) {
            $attr = [
                'type'     => $mail->getMailtype(),
                'isHtml'   => $mail->isHtml(),
                'content'  => $mail->getContent(),
                'html'     => $mail->getContentHtml(),
                'fromMail' => $mail->getFromMail(),
                'fromName' => $mail->getFromName(),
                'subject'  => $mail->getSubject(),
            // @todo: Should we ignore context??
                'context'  => $mail->getContext(),
            ];
            $store[$mail->getName()] = $attr;

            Factory::toFile($filename, $store);
        }
    }
}