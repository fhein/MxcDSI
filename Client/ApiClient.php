<?php

namespace MxcDropshipInnocigs\Client;

use DateTime;
use MxcDropshipInnocigs\Exception\ApiException;
use Zend\Http\Client;
use Zend\Http\Exception\RuntimeException as ZendClientException;
use Zend\Http\Response;
use Zend\Log\Logger;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;

class ApiClient
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

    public function __construct(Credentials $credentials, Logger $log) {
        $this->log = $log;
        $this->apiEntry = 'https://www.innocigs.com/xmlapi/api.php';
        $this->authUrl = $this->apiEntry . '?cid=' . $credentials->getUser() . '&auth=' . $credentials->getPassword();
        $this->log->info('User: '. $credentials->getUser());
        $this->log->info('Password: '. $credentials->getPassword());
        $this->connect();
    }

    private function connect() {
        $response = null;
        try {
            $response = $this->getItemInfo('mxc_connection_test');
            if (isset($response['ERRORS'])) {
                $error = $response['ERRORS']['ERROR'];
                throw new ServiceNotCreatedException(sprintf('API ERROR %s, %s', $error['CODE'], $error['MESSAGE']));
            }
        } catch(ApiException $e) {
            throw new ServiceNotCreatedException($e->getMessage());
        }
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

    protected function logXMLErrors(array $errors) {
        foreach ($errors as $error) {
            $msg = str_replace(PHP_EOL,'',$error->message);
            $this->log->err(sprintf(
                'XML Error: %s, line: %s, column: %s',
                $msg,
                $error->line,
                $error->column));
        }
    }

    protected function getArrayResult(Response $response) {
        if (!$response->isSuccess()) {
            throw new ApiException('HTTP status: ' . $response->getStatusCode());
        }
        $body = $response->getBody();

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body, 'SimpleXmlElement', LIBXML_NOERROR | LIBXML_NOWARNING);
        $errors = libxml_get_errors();

        if ($xml === false) {
            $this->logXmlErrors($errors);
            $dump = Shopware()->DocPath() . 'var/log/invalid-innocigs-api-response-' . date('Y-m-d-H-i-s') . '.txt';
            file_put_contents($dump, $body);
            $this->log->info('Invalid InnoCigs API response dumped to ' . $dump);
            throw new ApiException('InnoCigs API returned invalid XML. See log file for detailed information.');
        }
        $json = json_encode($xml);
        if ($json === false) {
            throw new ApiException('Failed to encode to JSON: ' . var_export($xml, true));
        }
        $result = json_decode($json, true);
        if ($result === false) {
            throw new ApiException('Failed to decode JSON: ' . var_export($json, true));
        }
        return $result;
    }

    /**
     * @param string $cmd
     * @return \Zend\Http\Response
     */
    protected function send($cmd)
    {
        $client = $this->getClient();
        $client->setUri($cmd);
        try {
            return $this->getArrayResult($client->send());
        } catch (ZendClientException $e) {
            // no response or response empty
            throw new ApiException($e->getMessage());
        }
    }

    /**
     * @return \Zend\Http\Client
     */
    protected function getClient()
    {
        if (null === $this->client) {
            $this->client = new Client(
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
