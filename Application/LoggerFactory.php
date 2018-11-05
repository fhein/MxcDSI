<?php
namespace MxcDropshipInnocigs\Application;

use Interop\Container\ContainerInterface;
use Zend\Log\Formatter\Simple;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\ServiceManager\Factory\FactoryInterface;

class LoggerFactory implements FactoryInterface
{

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $logPath = Shopware()->DocPath().'var/log/mxc_dropship_innocigs-'.date('Y-m-d').'.log';
        $writer = new Stream($logPath);
        $formatter = new Simple('%timestamp% %priorityName%: %message% %extra%');
        $formatter->setDateTimeFormat("H:i:s");
        $writer->setFormatter($formatter);
        $logger = new Logger();
        $logger->addWriter($writer);
        return $logger;
    }
}