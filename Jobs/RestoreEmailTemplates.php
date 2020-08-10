<?php

namespace MxcDropshipIntegrator\Jobs;

use MxcDropshipIntegrator\MxcDropshipIntegrator;
use Shopware\Models\Mail\Mail;

class RestoreEmailTemplates
{
    public static function run()
    {
        $manager = Shopware()->Container()->get('models');
        $mailRepository = $manager->getRepository(Mail::class);

        $filename = __DIR__ . '/../Config/MailTemplates.config.php';
        $mailConfigs = include $filename;

        $log = MxcDropshipIntegrator::getServices()->get('logger');
        if (! file_exists($filename)) {
            $log->err('File does not exist.');
        }

        foreach ($mailConfigs as $name => $config) {
            /** @var Mail $mail */
            $mail = $mailRepository->findOneBy([ 'name' => $name]);
            if ($mail !== null) {
                $mail->setMailtype($config['type']);
                $mail->setIsHtml($config['isHtml']);
                $mail->setContent($config['content']);
                $mail->setContentHtml($config['html']);
                $mail->setFromMail($config['fromMail']);
                $mail->setFromName($config['fromName']);
                $mail->setSubject($config['subject']);
                $mail->setContext($config['context']);
            }
        }
        $manager->flush();
    }
}