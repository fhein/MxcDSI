<?php
/**
 * Created by PhpStorm.
 * User: frank.hein
 * Date: 02.11.2018
 * Time: 13:09
 */

namespace MxcDropshipInnocigs\Client;


use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Factory\FactoryInterface;

class CredentialsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('pluginConfig');
        $user = $config->offsetGet('api_user');
        $password = null;
        if (is_string($user)) {
            $password = $config->offsetGet('api_password');
        } else {
            $dbal = $container->get('dbalConnection');
            $sql = 'SELECT user, password FROM s_plugin_mxc_dropship_innocigs_credentials';
            $credentials = $dbal->query($sql)->fetchAll();
            if (count($credentials) > 0) {
                $user = $credentials[0]['user'];
                $password = $credentials[0]['password'];
            }
        }
        if (! (is_string($user) && is_string($password) && $user !== '' && $password !== '')) {
            throw new ServiceNotCreatedException('Invalid credentials');
        };
        return new Credentials($user, $password);
    }
}