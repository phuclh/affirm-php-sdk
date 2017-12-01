<?php
/**
 * ClientTest
 *
 * @copyright 2017 Leaf Group, Ltd. All Rights Reserved.
 */
declare(strict_types=1);

namespace Affirm\Api;

/**
 * Unit Tests.
 *
 * @see Affirm\Api\Client
 *
 * @author Michael Funk <mike.funk@leafgroup.com>
 */
class ClientTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Affirm\Api\Client class under test
     */
    protected $client;

    /**
     * Phpunit setup.
     */
    public function setUp()
    {
        // instantiate class under test
        $this->client = new Client();
    }
}
