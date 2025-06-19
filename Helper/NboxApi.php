<?php

namespace Nbox\Shipping\Helper;

use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Nbox\Shipping\Utils\Constants;
use Nbox\Shipping\Exception\ApiException; // Custom Exception for API Errors

/**
 * NboxApi is responsible for making API requests to Nbox services such as login, activation, rates, etc.
 */
class NboxApi
{
    /**
     * @var StoreSource
     */
    protected $storeSource;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var Client
     */
    protected $client;

    /**
     * NboxApi constructor.
     *
     * @param StoreSource $storeSource
     * @param ConfigHelper $configHelper
     * @param Client $client
     */
    public function __construct(
        StoreSource $storeSource,
        ConfigHelper $configHelper,
        Client $client
    ) {
        $this->storeSource = $storeSource;
        $this->configHelper = $configHelper;
        $this->client = $client;
    }

    /**
     * Retrieves the Logger instance.
     *
     * @return LoggerInterface
     */
    private function getLogger()
    {
        return ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * Makes a POST request to a given URL with request data and headers.
     *
     * @param string $url The URL to send the POST request to.
     * @param array $requestData The data to be sent in the POST request.
     * @param array|null $headers Optional headers for the request.
     * @return array The decoded response from the API or an error message.
     * @throws ApiException If the API request fails or if the HTTP response code is not in the success range.
     */
    private function makePostRequest($url, $requestData, $headers = null)
    {
        try {
            // Default headers if none are provided
            if ($headers === null) {
                $headers = [
                    'Content-Type' => 'application/json',
                    Constants::NBOX_NOW_HEADER_DOMAIN => $this->getStoreDomain(),
                    Constants::NBOX_NOW_HEADER_TOKEN => $this->getApiToken()
                ];
            }

            $response = $this->client->post($url, [
                'json' => $requestData,
                'headers' => $headers
            ]);

            $body = (string) $response->getBody();
            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new ApiException("HTTP Error {$statusCode}: {$body}");
            }

            return json_decode($body, true);
        } catch (RequestException $e) {
            // Log HTTP request-specific errors
            $this->getLogger()->debug("Nbox API Error: " . $e->getMessage());
            return ["status" => "failed", "message" => $e->getMessage()];
        } catch (\Exception $e) {
            // Log generic errors
            $this->getLogger()->debug("Nbox API Error: " . $e->getMessage());
            return ["status" => "failed", "message" => $e->getMessage()];
        }
    }

    /**
     * Retrieves the store domain from the store's shipping origins.
     *
     * @return string The store domain or 'default-domain' if not available.
     */
    private function getStoreDomain()
    {
        $stores = $this->storeSource->getStoreShippingOrigins();
        return !empty($stores) ? $stores[0]['store_domain'] : 'default-domain';
    }

    /**
     * Retrieves the API token from the config helper.
     *
     * @return string The API token or 'default-token' if not set.
     */
    private function getApiToken()
    {
        return $this->configHelper->getApiToken() ?? 'default-token';
    }

    /**
     * Logs in the user by sending a POST request to the login API.
     *
     * @param array $requestData The data to be sent in the login request.
     * @return array The decoded response from the login API.
     */
    public function login($requestData)
    {
        return $this->makePostRequest(Constants::NBOX_LOGIN, $requestData, ['Content-Type' => 'application/json']);
    }

    /**
     * Activates the service by sending a POST request to the activation API.
     *
     * @param array $requestData The data to be sent in the activation request.
     * @return array The decoded response from the activation API.
     */
    public function activate($requestData)
    {
        return $this->makePostRequest(Constants::NBOX_ACTIVATION, $requestData);
    }

    /**
     * Retrieves shipping rates by sending a POST request to the rates API.
     *
     * @param array $requestData The data to be sent in the rates request.
     * @return array The decoded response from the rates API.
     */
    public function getRates($requestData)
    {
        return $this->makePostRequest(Constants::NBOX_RATES, $requestData);
    }

    /**
     * Creates an order by sending a POST request to the order API.
     *
     * @param array $requestData The data to be sent in the order request.
     * @return array The decoded response from the order API.
     */
    public function checkout($requestData)
    {
        return $this->makePostRequest(Constants::NBOX_ORDER, $requestData);
    }

    /**
     * Cancels an order by sending a POST request to the cancellation API.
     *
     * @param array $requestData The data to be sent in the cancellation request.
     * @return array The decoded response from the cancellation API.
     */
    public function cancelled($requestData)
    {
        return $this->makePostRequest(Constants::NBOX_CANCELLED, $requestData);
    }

    /**
     * Marks an order as fulfilled by sending a POST request to the fulfilled API.
     *
     * @param array $requestData The data to be sent in the fulfilled request.
     * @return array The decoded response from the fulfilled API.
     */
    public function fulfilled($requestData)
    {
        return $this->makePostRequest(Constants::NBOX_FULFILLED, $requestData);
    }

    /**
     * Retrieves the locations by sending a POST request to the locations API.
     *
     * @param array $requestData The data to be sent in the locations request.
     * @return array The decoded response from the locations API.
     */
    public function locations($requestData)
    {
        return $this->makePostRequest(Constants::NBOX_LOCATIONS, $requestData);
    }

    /**
     * Deactivates the service by sending a POST request to the activation API with activate=false.
     *
     * @return array The decoded response from the deactivation API.
     */
    public function deactivate()
    {
        $requestData = [
            "activate" => false
        ];
        return $this->makePostRequest(Constants::NBOX_ACTIVATION, $requestData);
    }
}
