<?php

namespace MxcDropshipInnocigs\Import;

use Doctrine\DBAL\Connection;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\FactoryInterface;

class CredentialsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('shopwareConfig');
        $user = $config->offsetGet('api_user');
        $password = null;
        if (is_string($user)) {
            $password = $config->offsetGet('api_password');
        } else {
            $credentialsTable = 's_plugin_mxc_dropship_innocigs_credentials';
            /**
             * @var Connection $dbal
             */
            $dbal = $container->get('dbalConnection');
            if ($dbal->getSchemaManager()->tablesExist([$credentialsTable])) {
                $sql = "SELECT user, password FROM $credentialsTable WHERE type = 'production'";
                /** @noinspection PhpUnhandledExceptionInspection */
                $credentials = $dbal->query($sql)->fetchAll();
                if (count($credentials) > 0) {
                    $user = $credentials[0]['user'];
                    $password = $credentials[0]['password'];
                }
            }
        }
        if (! (is_string($user) && is_string($password) && $user !== '' && $password !== '')) {
            throw new ServiceNotCreatedException('No valid InnoCigs API credentials available.');
        };
        return new Credentials($user, $password);
    }
}