# Unofficial Affirm PHP SDK

[Affirm API Docs](https://docs.affirm.com/Integrate_Affirm/Direct_API#checkout_object)


## Install:
```sh
composer require saatchiart/affirm-php-sdk
```

## Usage:
```php
// get an affirm php sdk instance
$config = [
    'public_api_key' => 'MY_AFFIRM_PUBLIC_API_KEY',
    'private_api_key' => 'MY_AFFIRM_PRIVATE_API_KEY',
    'is_sandbox' => true,
];
$affirm = new \Affirm\Api\Client($config);

// authorize an affirm payment by checkout token
/** @var \stdClass $response decoded json from response */
$optionalData = ['order_id' => 'OPTIONAL_ORDER_ID'];
$response = $affirm->authorize('MY_CHECKOUT_TOKEN', $optionalData);

// capture an authorized affirm payment by charge id
$optionalData = [
    'order_id' => 'abc123',
    'shipping_carrier' => 'my carrier',
    'shipping_confirmation' => 'abc123',
];
$response = $affirm->capture('MY_CHARGE_ID', $optionalData);

// read an authorized charge by charge id
$optionalData = [
    'limit' => 123,
    'before' => 'beforeString',
    'after' => 'afterString',
];
$response = $affirm->read('MY_CHARGE_ID', $optionalData);
```
