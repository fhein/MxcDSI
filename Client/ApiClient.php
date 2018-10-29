<?php

namespace MxcDropshipInnocigs\Client;

use MxcDropshipInnocigs\Helper\Log;
use Zend\Http\Client as HttpClient;
use DateTime;

class ApiClient
{
    /**
     * @var string $apiEntry
     */
    protected $apiEntry;

    /**
     * @var string $authUrl;
     */
    protected $authUrl;

    /**
     * @var string $user;
     */
    protected $user;

    /**
     * @var string $password;
     */
    protected $password;

    /**
     * @var Zend\Http\Client $client;
     */
    protected $client = null;

    protected $log;

    /**
     * @param string $user
     * @param string $password
     */
    public function __construct(string $user = null, string $password = null)
    {
        $this->log = new Log();
        $this->log->log('Hallo');
        if (null === $user) {
            $credentials = Shopware()->Db()->fetchAll('SELECT user, password FROM s_plugin_mxc_dropship_innocigs_credentials');
            $this->log->log('Loaded credentials.');
            if (count($credentials) > 0) {
                $this->user = $credentials[0]['user'];
                $this->password = $credentials[0]['password'];
            } else {
                // @TODO: add error handling
            }
        } else {
            $this->user = $user;
            $this->password = $password;
        }

        $this->log->log('API user: ' . $this->user);
        $this->log->log('API password: '. $this->password);

        $this->apiEntry = 'https://www.innocigs.com/xmlapi/api.php';
        $this->authUrl = $this->apiEntry . '?cid=' . $this->user . '&auth=' . $this->password;
    }

    /**
     * @return \Zend\Http\Response
     */
    public function getItemList()
    {
        $cmd = $this->authUrl . '&command=products';
        return $this->send($cmd);
    }

    /**
     * @param string $model
     * @return \Zend\Http\Response
     */
    public function getItemInfo($model)
    {
        $cmd = $this->authUrl . "&command=product&model=" . $model;
        return $this->send($cmd);
    }

    /**
     * @param \DateTime $date
     * @return \Zend\Http\Response
     */
    public function getTrackingData($date = null)
    {
        if (! $date instanceof DateTime) {
            $date = (new \DateTime())->format('Y-m-d');
        }
        $cmd = $this->authUrl . '&command=tracking&day=' . $date;
        return $this->send($cmd);
    }

    /**
     * @param string $model
     * @return \Zend\Http\Response
     */
    public function getStockInfo($model = null)
    {
        $cmd = is_string($model)
            ? $this->authUrl . '&command=quantity&model=' . urlencode($model)
            : $this->authUrl . '&command=quantity_all';
        return $this->send($cmd);
    }

    /**
     * @return NULL
     */
    public function order()
    {
        return null;
    }

    /**
     * @param string $cmd
     * @return \Zend\Http\Response
     */
    protected function send($cmd)
    {

        $client = $this->getClient();
        $client->setUri($cmd);
        return $client->send();
    }

    /**
     * @return \Zend\Http\Client
     */
    protected function getClient()
    {
        if (null === $this->client) {
            $this->client = new HttpClient(
                "",
                [
                    'maxredirects' => 0,
                    'timeout' => 30,
                    'useragent' => 'maxence Dropship',
                ]
            );
        }
        return $this->client;
    }
}
