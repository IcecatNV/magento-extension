<?php
declare(strict_types=1);

namespace Icecat\DataFeed\Service;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use Icecat\DataFeed\Helper\Data;
use Magento\Framework\Webapi\Rest\Request;

class IcecatApiService
{
    /**
     * API request URL
     */
    protected const API_REQUEST_URI = 'https://live.icecat.biz/api/';

    private ClientFactory $clientFactory;
    private ResponseFactory $responseFactory;
    private Data $data;

    /**
     * @param ClientFactory $clientFactory
     * @param ResponseFactory $responseFactory
     * @param Data $data
     */
    public function __construct(
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        Data $data
    ) {
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->data = $data;
    }

    /**
     * Fetch some data from API
     * @param $icecatUri
     * @return array
     */
    public function execute($icecatUri): array
    {
        $response = $this->doRequest($icecatUri);
        $responseBody = $response->getBody();
        return json_decode($responseBody->getContents(), true);
    }

    /**
     * Do API request with provided params
     *
     * @param string $uriEndpoint
     * @param array $params
     * @param string $requestMethod
     *
     * @return Response
     */
    private function doRequest(
        string $uriEndpoint,
        array $params = [],
        string $requestMethod = Request::HTTP_METHOD_GET
    ): Response {
        /** @var Client $client */
        $client = $this->clientFactory->create(['config' => [
            'base_uri' => self::API_REQUEST_URI
        ]]);

        $userType = $this->data->getUserType();
        
        if($userType == 'full' && !empty($this->data->getAppKey())) {
            $uriEndpoint = $uriEndpoint . '&app_key=' . $this->data->getAppKey();
        }
        $uriEndpoint = $uriEndpoint . '&PlatformName=Magento2OpensourceExtension&PlatformVersion=V2';

        $params['headers'] = [
            'api-token' => $this->data->getAccessToken()
        ];
        $params['http_errors'] = false;

        try {
            $response = $client->request(
                $requestMethod,
                $uriEndpoint,
                $params
            );
        } catch (GuzzleException $exception) {
            /** @var Response $response */
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ]);
        }

        return $response;
    }
}
