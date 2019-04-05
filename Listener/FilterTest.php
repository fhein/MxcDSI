<?php

namespace MxcDropshipInnocigs\Listener;

use Mxc\Shopware\Plugin\ActionListener;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Toolbox\Shopware\Filter\GroupRepository;
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

    protected $log;
    protected $config;

    /**
     * @var GroupRepository $repo
     */
    protected $repo;

    public function __construct(GroupRepository $repository, Config $config, LoggerInterface $log) {
        $this->repo = $repository;
        $this->config = $config;
        $this->log = $log;
    }

    public function install( /** @noinspection PhpUnusedParameterInspection */ EventInterface $e) {
        $this->log->enter();
        foreach ($this->filters as $filter => $options) {
            $this->repo->createGroup($filter);
            foreach($options as $option) {
                $this->repo->createOption($filter, $option);
            }
        }
        $this->repo->flush();
        $this->log->leave();
    }

    public function uninstall(/** @noinspection PhpUnusedParameterInspection */ EventInterface $e) {
        $this->log->enter();
        $filters = array_keys($this->filters);
        foreach($filters as $filter) {
            $this->repo->deleteGroup($filter);
        }
        $this->log->leave();
    }
}