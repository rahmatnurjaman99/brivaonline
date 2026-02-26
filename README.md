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
