<?php

namespace MxcDropshipInnocigs\Listener;

use Mxc\Shopware\Plugin\ActionListener;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Filter\OptionRepository;
use Zend\Config\Config;
use Zend\EventManager\EventInterface;

class FilterTest extends ActionListener
{
    protected $filters = [
        'Produktart' => [
            'Liquid',
            'Aroma',
            'E-Zigarette',
            'Ladegerät',
        ],
    ];

    /**
     * @var OptionRepository $repo
     */
    protected $repo;

    public function __construct(OptionRepository $repository, Config $config, LoggerInterface $log) {
        parent::__construct($config, $log);
        $this->repo = $repository;
    }

    public function install(EventInterface $e) {
        $this->log->enter();
        foreach ($this->filters as $filter => $options) {
            $this->repo->createOption($filter);
            foreach($options as $option) {
                $this->repo->createValue($filter, $option);
            }
        }
        $this->repo->flush();
        $this->log->leave();
    }

    public function uninstall(EventInterface $e) {
        $this->log->enter();
        $filters = array_keys($this->filters);
        foreach($filters as $filter) {
            $this->repo->deleteOption($filter);
        }
        $this->log->leave();
    }
}