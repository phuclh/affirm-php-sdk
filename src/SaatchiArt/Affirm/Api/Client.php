<?php
/**
 * Client
 *
 * @copyright Leaf Group, Ltd. All Rights Reserved.
 */
declare(strict_types=1);

namespace SaatchiArt\Affirm\Api;

use const Shape\bool;
use const Shape\int;
use const Shape\string;
use function Shape\shape;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\Exception\BadResponseException;

/**
 * Interact with the affirm api.
 *
 * @author Michael Funk <mike.funk@leafgroup.com>
 */
class Client
{

    /** @var string */
    const LIVE_URL = 'https://api.affirm.com/api/v2/';

    /** @var string */
    const SANDBOX_URL = 'https://sandbox.affirm.com/api/v2/';

    /** @var \GuzzleHttp\ClientInterface */
    protected $httpClient;

    /** @var string */
    protected $publicApiKey;

    /** @var string */
    protected $privateApiKey;

    /** @var bool */
    protected $isSandbox;

    /**
     * Dependency injection.
     *
     * @param (string|bool)[] $config
     * @param \GuzzleHttp\ClientInterface|null $httpClient
     */
    public function __construct(array $config, HttpClientInterface $httpClient = null)
    {
        // validate config shape (will throw TypeError if missing or wrong type)
        shape([
            'public_api_key' => string,
            'private_api_key' => string,
            'is_sandbox' => bool,
        ])($config);
        $this->publicApiKey = $config['public_api_key'];
        $this->privateApiKey = $config['private_api_key'];
        $this->isSandbox = $config['is_sandbox'];
        $this->httpClient = $httpClient ?: new HttpClient();
    }

    protected function getBaseUrl(): string
    {
        return $this->isSandbox ? self::SANDBOX_URL : self::LIVE_URL;
    }

    /**
     * Authorize a payment that has been initiated.
     *
     * @throws \Affirm\Api\ResponseException if something goes wrong
     *
     * @param string[] $optionalData In this case it's just `order_id`
     * that is optional. Kept as an associate array for consistency with other
     * public methods.
     *
     * @return \stdClass the decoded json from the response
     */
    public function authorize(
        string $checkoutToken,
        array $optionalData = []
    ): \stdClass {
        $postData = ['checkout_token' => $checkoutToken];
        // ensure it's the correct type if it's set
        $this->validateOptionalData([
            'order_id' => string,
        ], $optionalData);
        // get only the available optional data
        $paramNames = ['order_id'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        // add to the post data and send
        $postData = array_merge($postData, $optionalData);
        return $this->request(
            'POST',
            $this->getBaseUrl() . 'charges/',
            $postData
        );
    }

    /**
     * Capture a payment amount that has been initiated and authorized.
     *
     * @param string[] $optionalData
     *
     * @return \stdClass the decoded json from the response
     */
    public function capture(string $chargeId, array $optionalData = []): \stdClass
    {
        $this->validateOptionalData([
            'order_id' => string,
            'shipping_carrier' => string,
            'shipping_confirmation' => string,
        ], $optionalData);
        $paramNames = ['order_id', 'shipping_carrier', 'shipping_confirmation'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        $url = $this->getBaseUrl() . "charges/$chargeId/capture";
        return $this->request('POST', $url, $optionalData);
    }

    /**
     * Get details of a charge.
     *
     * @param (string|int)[] $optionalData can include `limit`, `before`, `after`
     *
     * @return \stdClass the decoded json from the response.
     */
    public function read(string $chargeId, array $optionalData = []): \stdClass
    {
        // if optional data is passed, it must be of this type
        $this->validateOptionalData([
            'limit' => int,
            'before' => string,
            'after' => string,
        ], $optionalData);
        // only include params available to send for this request
        $paramNames = ['limit', 'before', 'after'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);
        $queryString = $optionalData ? '?' . http_build_query($optionalData) : '';
        return $this->request('GET', $this->getBaseUrl() . "charges/$chargeId$queryString");
    }

    /**
     * If optional data is included, ensure it is of the right type. Will throw
     * a TypeError if invalid type.
     *
     * @return void
     */
    public function validateOptionalData(array $optionalShape, array $optionalData)
    {
        $shape = [];
        foreach ($optionalShape as $key => $value) {
            if (array_key_exists($key, $optionalData)) {
                $shape[$key] = $value;
            }
        }
        shape($shape)($optionalData);
    }

    /**
     * Void a charge.
     *
     * @return \stdClass the decoded json from the response.
     */
    public function void(string $chargeId): \stdClass
    {
        return $this->request('POST', $this->getBaseUrl() . "charges/$chargeId/void");
    }

    /**
     * Refund a charge or part of it.
     *
     * @param int[] $optionalData In this case it's just `amount` that is
     * optional. Kept as an associate array for consistency with other public
     * methods.
     *
     * @return \stdClass the decoded json from the response.
     */
    public function refund(string $chargeId, array $optionalData): \stdClass
    {
        // if optional data is passed, it must be of this type
        $this->validateOptionalData(['amount' => int], $optionalData);
        // only include params available to send for this request
        $paramNames = ['amount'];
        $optionalData = $this->whitelistArray($optionalData, $paramNames);

        return $this->request(
            'POST',
            $this->getBaseUrl() . "charges/$chargeId/refund",
            $optionalData
        );
    }

    /**
     * Internal method to send a request to affirm and get a response.
     *
     * @return \stdClass the decoded json from the response
     */
    protected function request(
        string $httpVerb,
        string $url,
        array $postData = []
    ): \stdClass {
        try {
            // request via guzzle
            $requestData = [
                'auth' => [
                    $this->publicApiKey,
                    $this->privateApiKey,
                ],
            ];
            if ($postData) {
                $requestData['json'] = $postData;
            }
            $response = $this->httpClient->request(
                $httpVerb,
                $url,
                $requestData
            );
        } catch (BadResponseException $exception) {
            // if guzzle fails, rethrow affirm exception
            throw new ResponseException(
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
        /** @var string $responseBody */
        $responseBody = $response->getBody()->getContents();
        $responseData = json_decode($responseBody);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // if json couldn't be decoded, throw exception
            $message = "Json could not be decoded from affirm response. " .
                "Response body: $responseBody";
            throw new ResponseException($message);
        }
        return $responseData;
    }

    /**
     * Get only the elements in an array whose keys match a whitelist of keys
     *
     * @param mixed[] $array
     * @param (int|string)[] $whitelistedKeys
     *
     * @return mixed[]
     */
    protected function whitelistArray(array $array, array $whitelistedKeys): array
    {
        return array_filter(
            array_intersect_key(
                $array,
                array_flip($whitelistedKeys)
            )
        );
    }
}
