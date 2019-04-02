<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace MxcDropshipInnocigs\Import;

use DateTime;
use Exception;
use Mxc\Shopware\Plugin\Service\LoggerInterface;
use MxcDropshipInnocigs\Exception\ApiException;
use Zend\Http\Client;
use Zend\Http\Exception\RuntimeException as ZendClientException;
use Zend\Http\Response;

class ApiClient extends XmlClient
{
    /**
     * @var string $apiEntry
     */
    protected $apiEntry;

    /**
     * @var string $authUrl
     */
    protected $authUrl;

    /**
     * @var string $user
     */
    protected $user;

    /**
     * @var string $password
     */
    protected $password;

    /**
     * @var Client $client
     */
    protected $client = null;

    /**
     * @var int $logLevel
     */
    protected $logLevel;

    protected $log;

    public function __construct(Credentials $credentials, LoggerInterface $log)
    {
        parent::__construct($log);
        $this->apiEntry = 'https://www.innocigs.com/xmlapi/api.php';
        $this->authUrl = $this->apiEntry . '?cid=' . $credentials->getUser() . '&auth=' . $credentials->getPassword();
    }

    /**
     * @param string $model
     * @return array
     */
    public function getItemInfo($model)
    {
        $cmd = $this->authUrl . "&command=product&model=" . $model;
        return $this->modelsToArray($this->send($cmd)->getBody());
    }

    /**
     * @return array
     */
    public function getItemList()
    {
        $cmd = $this->authUrl . '&command=products&type=extended';
        return $this->modelsToArray($this->send($cmd)->getBody());
    }

    /**
     * @param DateTime $date
     * @return array
     * @throws Exception
     */
    public function getTrackingData($date = null)
    {
        if (!$date instanceof DateTime) {
            $date = (new DateTime())->format('Y-m-d');
        }
        $cmd = $this->authUrl . '&command=tracking&day=' . $date;
        return $this->xmlToArray($this->send($cmd)->getBody());
    }

    public function getStockInfo(string $model)
    {
        $cmd = $this->authUrl . '&command=quantity&model=' . urlencode($model);
        $data = $this->xmlToArray($this->send($cmd)->getBody());
        return $data['QUANTITIES']['PRODUCT']['QUANTITY'];
    }

    public function getAllStockInfo() {
        $cmd = $this->authUrl . '&command=quantity_all';
        return $this->xmlToArray($this->send($cmd)->getBody());
    }

    /**
     * @param string $cmd
     * @return Response
     */
    protected function send($cmd)
    {
        $client = $this->getClient();
        $client->setUri($cmd);
        try {
            $response = $client->send();
            if (! $response->isSuccess()) {
                throw new ApiException('HTTP status: ' . $response->getStatusCode());
            }
            return $client->send();
        } catch (ZendClientException $e) {
            // no response or response empty
            throw new ApiException($e->getMessage());
        }
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        if (null === $this->client) {
            $this->client = new Client(
                "",
                [
                    'maxredirects' => 0,
                    'timeout'      => 30,
                    'useragent'    => 'maxence Dropship',
                ]
            );
        }
        return $this->client;
    }
}
