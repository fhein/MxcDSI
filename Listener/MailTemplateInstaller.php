<?php


namespace MxcDropshipIntegrator\Listener;

use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use Shopware\Models\Mail\Log;
use Shopware\Models\Mail\Mail;

class MailTemplateInstaller
{
    private $templates = [
        'sMXCDSIINVOICE',
        'sMXCDSIDELIVERYNOTE',
        'sMXCDSICANCELLATION',
        'sMXCDSIREDIT',
    ];

    public function install(InstallContext $context)
    {
        $manager = Shopware()->Container()->get('models');
        $mailRepository = $manager->getRepository(Mail::class);
        foreach ($this->templates as $template) {
            $mail = $mailRepository->findOneBy([ 'name' => $template]);
            if ($mail === null) {
                $mail = new Mail();
                $mail->setName($template);
                $mail->setFromMail('{config name=mail}');
                $mail->setFromName('{config name=shopName}');
                $mail->setSubject("Ihre {config name=shopName} Bestellung");
                $mail->setIsHtml(true);
                $mail->setContentHtml('');
                $mail->setMailtype(Mail::MAILTYPE_USER);

                $manager->persist($mail);
            }
        }
        $manager->flush();
    }

    private function uninstall(UninstallContext $context) {
        $manager = Shopware()->Container()->get('models');
        $mailRepository = $manager->getRepository(Mail::class);
        $keepUserData = $context->keepUserData();
        foreach ($this->templates as $template) {
            /** @var Mail $mail */
            $mail = $mailRepository->findOneBy([ 'name' => $template]);
            if ($mail === null) continue;
            if (! $keepUserData) {
                $manager->remove($mail);
                if (Shopware()->Config()->get('Version') >= '5.6.0') {
                    $logEntries = $manager->getRepository(Log::class)->findBy(['type' => $mail->getId()]);
                    foreach ($logEntries as $logEntry) {
                        $manager->remove($logEntry);
                    }
                }
            }
        }
    }
}