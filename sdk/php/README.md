# PHP SDK foundation

Minimal HTTP client for JobVisa.lk `/api/v1`.

## In-app

```php
use JobVisa\App\Domain\Api\Sdk\JobVisaClient;

$client = new JobVisaClient('https://example.com/jobvisa/api/v1', $token);
$client->health();
$client->get('/me');
```

## Standalone package

`JobVisa\Sdk\Client` under `sdk/php/src` for external Composer projects.
