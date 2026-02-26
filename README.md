# brivaonline

Laravel package for BRI VA/SNAP integrations with WSDL inquiry/payment support.

## Install

```bash
composer require rahmatnurjaman99/brivaonline
```

## Publish config and migrations

```bash
php artisan vendor:publish --tag=briva-config
php artisan vendor:publish --tag=briva-migrations
```

## Routes

Routes are registered automatically when `briva.routes.enabled` is true.

## Usage

```php
use RahmatNurjaman99\BrivaOnline\Clients\SnapClient;

$snap = app(SnapClient::class);
$token = $snap->accessToken();
```

## Custom inquiry resolver

By default, inquiry data is fetched via WSDL. Your resolver must return the final response payload:

```php
// config/briva.php
'inquiry_resolver' => App\Briva\MyInquiryResolver::class,
```

Example resolver return:

```php
return [
    'responseCode' => '2002400',
    'responseMessage' => 'Successful',
    'virtualAccountData' => [
        'partnerServiceId' => '00012345',
        'customerNo' => '123456',
        'virtualAccountNo' => '00012345123456',
        'virtualAccountName' => 'John Doe',
        'inquiryRequestId' => 'REQ-1',
        'totalAmount' => ['value' => '1000.00', 'currency' => 'IDR'],
        'inquiryStatus' => '00',
        'inquiryReason' => ['english' => 'Success', 'indonesia' => 'Sukses'],
    ],
    'additionalInfo' => [],
];
```

## Custom payment resolver

By default, payment is posted via WSDL. Your resolver must return the final response payload:

```php
// config/briva.php
'payment_resolver' => App\Briva\MyPaymentResolver::class,
```

Example resolver return:

```php
return [
    'responseCode' => '2002500',
    'responseMessage' => 'Successful',
    'paymentStatus' => '00',
];
```
