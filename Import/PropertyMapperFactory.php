<?php

namespace MxcDropshipInnocigs\Import;

use Interop\Container\ContainerInterface;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Import\Report\ArrayReport;
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
        $config = $container->get('config')['propertymapper'];
        $config = $config->toArray();
        $config['articles'] = $this->getArticleConfiguration();
        $log = $container->get('logger');
        $reporter = $container->get(ArrayReport::class);

        return new PropertyMapper($reporter, $config, $log);
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

