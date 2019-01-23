<?php

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class PropertyMapperFactory implements FactoryInterface
{
    /** @var string $articleConfigFile */
    protected $articleConfigFile = __DIR__ . '/../Config/article.config.php';

    /** @var LoggerInterface $log */
    protected $log;
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
        $this->log = $container->get('logger');
        $mappings = $container->get('config')['mappings'];
        $articleConfig = $this->getArticleConfiguration();

        return new PropertyMapper($mappings->toArray(), $articleConfig);
    }

    protected function getArticleConfiguration() {
        $this->log->enter();
        if ( ! file_exists($this->articleConfigFile)) {
            $distributedFile = $this->articleConfigFile . '.dist';
            if (file_exists($distributedFile)) {
                $this->log->debug('Creating brand/supplier file from plugin distribution.');
                copy($distributedFile, $this->articleConfigFile);
            } else {
                $this->log->debug(sprintf(
                    'Distributed brand/supplier %sfile missing. Nothing done.',
                    $distributedFile
                ));
                return [];
            }
        }
        /** @noinspection PhpIncludeInspection */
        $articleConfig = include $this->articleConfigFile;
        $this->log->leave();
        return $articleConfig;
    }

}