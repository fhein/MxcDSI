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
        $mappings = $container->get('config')['mappings'];
        $articleConfig = $this->getArticleConfiguration();
        $log = $container->get('logger');

        return new PropertyMapper($mappings->toArray(), $articleConfig, $log);
    }

    protected function getArticleConfiguration()
    {
        if (file_exists($this->articleConfigFile)) {
            $fn = $this->articleConfigFile;
        } else {
            $distFile = $this->articleConfigFile . '.dist';
            if (file_exists($distFile)) {
                $fn = $distFile;
            } else {
                return [];
            }
        }
        /** @noinspection PhpIncludeInspection */
        $config = include $fn;
        return $config;
    }
}

