<?php

namespace IbraheemGhazi\OmniTenancy\Core\Traits;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait HasRemoteHttpClient
{
    protected string $httpClientBaseUrl;
    protected array $httpClientHeaders;
    protected int $httpClientTimeout = 30;

    protected function initRemoteHttpClient(): void
    {
        $this->httpClientBaseUrl = config('tenancy.remote_registry.base_url', '');
        $this->httpClientHeaders = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], config('tenancy.remote_registry.headers', []));
        $this->httpClientTimeout = config('tenancy.remote_registry.timeout', 30);
    }

    protected function requestHttp(): PendingRequest
    {
        return Http::withHeaders($this->httpClientHeaders)
            ->timeout($this->httpClientTimeout)
            ->baseUrl($this->httpClientBaseUrl);
    }

}
