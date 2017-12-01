<?php
/**
 * ClientTest
 *
 * @copyright 2017 Leaf Group, Ltd. All Rights Reserved.
 */
declare(strict_types=1);

namespace SaatchiArt\Affirm\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase as TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit Tests.
 *
 * @see \Affirm\Api\Client
 *
 * @author Michael Funk <mike.funk@leafgroup.com>
 */
class ClientTest extends TestCase
{

    /**
     * @var array affirm config
     */
    const CONFIG = [
        'public_api_key' => 'abc123',
        'private_api_key' => 'xyz321',
        'is_sandbox' => false,
    ];

    /** @var \Affirm\Api\Client class under test */
    protected $client;

    /** @var \GuzzleHttp\ClientInterface */
    protected $httpClient;

    /**
     * Phpunit setup.
     */
    public function setUp()
    {
        $this->httpClient = $this->prophesize(ClientInterface::class);
        // instantiate class under test
        $this->client = new Client(self::CONFIG, $this->httpClient->reveal());
    }

    /**
     * @test
     */
    public function it_fails_to_authorize_if_invalid_optional_data()
    {
        $checkoutToken = 'abc1234663';
        // null is wrong type
        $orderId = null;

        // call and verify
        $this->expectException(\TypeError::class);
        $this->client->authorize($checkoutToken, ['order_id' => $orderId]);
    }

    /**
     * @test
     */
    public function it_fails_to_authorize_if_bad_response()
    {
        $checkoutToken = 'abc1234663';
        $orderId = 'xyz3214555';

        // it makes the request
        // which throws a bad response exception
        $response = $this->prophesize(ResponseInterface::class);

        $request = $this->prophesize(RequestInterface::class);
        $exception = new BadResponseException('oops', $request->reveal(), $response->reveal());

        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/',
            [
                'auth' => [
                    'abc123',
                    'xyz321',
                ],
                'json' => [
                    'checkout_token' => $checkoutToken,
                    'order_id' => $orderId,
                ],
            ]
        )->willThrow($exception);

        // call and vefify
        $this->expectException(ResponseException::class);
        $this->client->authorize($checkoutToken, ['order_id' => $orderId]);
    }

    /**
     * @test
     */
    public function it_fails_to_authorize_if_response_is_not_json()
    {
        $checkoutToken = 'abc1234663';
        $orderId = 'xyz3214555';

        // it gets the response
        // which is not json
        $response = $this->prophesize(ResponseInterface::class);
        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/',
            [
                'auth' => [
                    'abc123',
                    'xyz321',
                ],
                'json' => [
                    'checkout_token' => $checkoutToken,
                    'order_id' => $orderId,
                ],
            ]
        )->willReturn($response->reveal());
        $stream = $this->prophesize(Stream::class);
        $response->getBody()->willReturn($stream->reveal());
        $json = '<tag>totally not json</tag>';
        $stream->getContents()->willReturn($json);

        // call and verify
        $this->expectException(ResponseException::class);
        $this->client->authorize($checkoutToken, ['order_id' => $orderId]);
    }

    /**
     * @test
     */
    public function it_authorizes()
    {
        $checkoutToken = 'abc1234663';
        $orderId = 'xyz3214555';

        // it makes the request and gets the response
        $response = $this->prophesize(ResponseInterface::class);
        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/',
            [
                'auth' => [
                    'abc123',
                    'xyz321',
                ],
                'json' => [
                    'checkout_token' => $checkoutToken,
                    'order_id' => $orderId,
                ],
            ]
        )->willReturn($response->reveal());
        $stream = $this->prophesize(Stream::class);
        $response->getBody()->willReturn($stream->reveal());
        $json = json_encode(['id' => '33odofl2']);
        $stream->getContents()->willReturn($json);

        // call and verify
        $expected = (object)['id' => '33odofl2'];
        $actual = $this->client->authorize($checkoutToken, ['order_id' => $orderId]);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_fails_to_capture_if_invalid_optional_data()
    {
        $checkoutToken = 'abc1234663';
        // float is wrong type
        $orderId = 123.45;

        // call and verify
        $this->expectException(\TypeError::class);
        $this->client->capture($checkoutToken, ['order_id' => $orderId]);
    }

    /**
     * @test
     */
    public function it_fails_to_capture_if_bad_response_exception()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';

        // it sends the request
        // which throws an exception
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $exception = new BadResponseException('oops', $request->reveal(), $response->reveal());
        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of/capture',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
            ]
        )->willThrow($exception);
        // call and verify
        $this->expectException(ResponseException::class);
        $this->client->capture($chargeId);
    }

    /**
     * @test
     */
    public function it_fails_to_capture_if_response_is_not_json()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';

        // it sends the request
        // which returns non-json
        $response = $this->prophesize(ResponseInterface::class);
        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of/capture',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
            ]
        )->willReturn($response->reveal());
        $stream = $this->prophesize(Stream::class);
        $response->getBody()->willReturn($stream->reveal());
        $json = '<tag>totally not json</tag>';
        $stream->getContents()->willReturn($json);

        // call and verify
        $this->expectException(ResponseException::class);
        $this->client->capture($chargeId);
    }

    /**
     * @test
     */
    public function it_captures()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';

        // it sends the request
        // which throws an exception
        $response = $this->prophesize(ResponseInterface::class);
        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of/capture',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
            ]
        )->willReturn($response->reveal());
        $stream = $this->prophesize(Stream::class);
        $response->getBody()->willReturn($stream->reveal());
        $json = json_encode(
            [
                "fee" => 600,
                "created" => "2016-03-18T00 =>03 =>44Z",
                "order_id" => "JKLM4321",
                "currency" => "USD",
                "amount" => 6100,
                "type" => "capture",
                "id" => "O5DZHKL942503649",
                "transaction_id" => "6dH0LrrgUaMD7Llc",
            ]
        );
        $stream->getContents()->willReturn($json);

        // call and verify
        $expected = (object)[
            "fee" => 600,
            "created" => "2016-03-18T00 =>03 =>44Z",
            "order_id" => "JKLM4321",
            "currency" => "USD",
            "amount" => 6100,
            "type" => "capture",
            "id" => "O5DZHKL942503649",
            "transaction_id" => "6dH0LrrgUaMD7Llc",
        ];

        $actual = $this->client->capture($chargeId);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_fails_to_read_if_invalid_optional_data()
    {
        $chargeId = 'abc1234663';
        // float is wrong type
        $limit = 123.45;

        // call and verify
        $this->expectException(\TypeError::class);
        $this->client->read($chargeId, ['limit' => $limit]);
    }

    /**
     * @test
     */
    public function it_fails_to_read_if_bad_response_exception()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';

        // it sends the request
        // which throws an exception
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $exception = new BadResponseException('oops', $request->reveal(), $response->reveal());
        $this->httpClient->request(
            'GET',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
            ]
        )->willThrow($exception);
        // call and verify
        $this->expectException(ResponseException::class);
        $this->client->read($chargeId);
    }

    /**
     * @test
     */
    public function it_fails_to_read_if_response_is_not_json()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';

        // it sends the request
        // which returns non-json
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $exception = new BadResponseException('oops', $request->reveal(), $response->reveal());
        $this->httpClient->request(
            'GET',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
            ]
        )->willReturn($response->reveal());
        $stream = $this->prophesize(Stream::class);
        $response->getBody()->willReturn($stream->reveal());
        $json = '<tag>totally not json</tag>';
        $stream->getContents()->willReturn($json);
        // call and verify
        $this->expectException(ResponseException::class);
        $this->client->read($chargeId);
    }

    /**
     * @test
     */
    public function it_reads()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';

        // it sends the request
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $exception = new BadResponseException('oops', $request->reveal(), $response->reveal());
        $this->httpClient->request(
            'GET',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
            ]
        )->willReturn($response->reveal());
        $stream = $this->prophesize(Stream::class);
        $response->getBody()->willReturn($stream->reveal());
        $json = json_encode(['my' => 'json']);
        $stream->getContents()->willReturn($json);
        // call and verify
        $expected = (object)['my' => 'json'];
        $actual = $this->client->read($chargeId);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_fails_to_void_if_bad_response_exception()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';

        // it sends the request
        // which throws an exception
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $exception = new BadResponseException('oops', $request->reveal(), $response->reveal());
        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of/void',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
            ]
        )->willThrow($exception);
        // call and verify
        $this->expectException(ResponseException::class);
        $this->client->void($chargeId);
    }

    /**
     * @test
     */
    public function it_fails_to_void_if_response_is_not_json()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';

        // it sends the request
        // which returns non-json
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $exception = new BadResponseException('oops', $request->reveal(), $response->reveal());
        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of/void',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
            ]
        )->willReturn($response->reveal());
        $stream = $this->prophesize(Stream::class);
        $response->getBody()->willReturn($stream->reveal());
        $json = '<tag>totally not json</tag>';
        $stream->getContents()->willReturn($json);
        // call and verify
        $this->expectException(ResponseException::class);
        $this->client->void($chargeId);
    }

    /**
     * @test
     */
    public function it_voids()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';

        // it sends the request
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $exception = new BadResponseException('oops', $request->reveal(), $response->reveal());
        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of/void',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
            ]
        )->willReturn($response->reveal());
        $stream = $this->prophesize(Stream::class);
        $response->getBody()->willReturn($stream->reveal());
        $json = json_encode(['my' => 'json']);
        $stream->getContents()->willReturn($json);
        // call and verify
        $expected = (object)['my' => 'json'];
        $actual = $this->client->void($chargeId);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_fails_to_refund_if_bad_response_exception()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';
        $amount = 400;

        // it sends the request
        // which throws an exception
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $exception = new BadResponseException('oops', $request->reveal(), $response->reveal());
        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of/refund',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
                'json' => [
                    'amount' => $amount,
                ],
            ]
        )->willThrow($exception);
        // call and verify
        $this->expectException(ResponseException::class);
        $this->client->refund($chargeId, ['amount' => $amount]);
    }

    /**
     * @test
     */
    public function it_fails_to_refund_if_response_is_not_json()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';
        $amount = 400;

        // it sends the request
        // which returns non-json
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $exception = new BadResponseException('oops', $request->reveal(), $response->reveal());
        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of/refund',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
                'json' => [
                    'amount' => $amount,
                ],
            ]
        )->willReturn($response->reveal());
        $stream = $this->prophesize(Stream::class);
        $response->getBody()->willReturn($stream->reveal());
        $json = '<tag>totally not json</tag>';
        $stream->getContents()->willReturn($json);
        // call and verify
        $this->expectException(ResponseException::class);
        $this->client->refund($chargeId, ['amount' => $amount]);
    }

    /**
     * @test
     */
    public function it_refunds()
    {
        $chargeId = 'ddkr5k5of';
        $orderId = 'addkg55';
        $amount = 400;

        // it sends the request
        $response = $this->prophesize(ResponseInterface::class);
        $request = $this->prophesize(RequestInterface::class);
        $exception = new BadResponseException('oops', $request->reveal(), $response->reveal());
        $this->httpClient->request(
            'POST',
            'https://api.affirm.com/api/v2/charges/ddkr5k5of/refund',
            [
                'auth' => [
                    'abc123',
                    'xyz321'
                ],
                'json' => [
                    'amount' => $amount,
                ],
            ]
        )->willReturn($response->reveal());
        $stream = $this->prophesize(Stream::class);
        $response->getBody()->willReturn($stream->reveal());
        $json = json_encode(['my' => 'json']);
        $stream->getContents()->willReturn($json);
        // call and verify
        $expected = (object)['my' => 'json'];
        $actual = $this->client->refund($chargeId, ['amount' => $amount]);
        $this->assertEquals($expected, $actual);
    }
}
