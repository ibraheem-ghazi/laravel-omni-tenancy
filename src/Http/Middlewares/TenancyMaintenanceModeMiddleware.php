<?php

namespace IbraheemGhazi\OmniTenancy\Http\Middlewares;

use IbraheemGhazi\OmniTenancy\Contracts\TenantContext;
use IbraheemGhazi\OmniTenancy\Tenancy;
use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Foundation\Http\Middleware\Concerns\ExcludesPaths;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TenancyMaintenanceModeMiddleware
{
    use ExcludesPaths;

    /**
     * The URIs that should be excluded.
     *
     * @var array<int, string>
     */
    protected array $except = [];

    /**
     * The URIs that should be accessible during maintenance.
     *
     * @var array
     */
    protected static array $neverPrevent = [];

    /**
     * Create a new middleware instance.
     *
     * @param Application $app
     * @return void
     */
    public function __construct(protected readonly Application $app)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     *
     * @throws HttpException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->inExceptArray($request)) {
            return $next($request);
        }

        $data = $this->getMaintenanceModeData();

        if (!data_get($data, 'enabled', false)) {
            return $next($request);
        }

        if (data_get($data, 'secret') === $request->path()) {
            return $this->bypassResponse($data['secret']);
        }

        if ($this->hasValidBypassCookie($request, $data)) {
            return $next($request);
        }

        $redirectTo = data_get($data, 'redirect');
        if (filled($redirectTo)) {
            $path = $redirectTo === '/'
                ? $redirectTo
                : trim($redirectTo, '/');

            if ($request->path() !== $path) {
                return redirect($path);
            }
        }

        $status = data_get($data, 'status') ?: 503;
        $template = data_get($data, 'template');
        if (filled($template)) {
            return response(
                $template,
                $status,
                $this->getHeaders($data)
            );
        }

        throw new HttpException(
            $status,
            'Service Unavailable',
            null,
            $this->getHeaders($data)
        );
    }

    protected function getMaintenanceModeData(): array
    {
        $tenant = Tenancy::context()->getCurrentTenant();
        if (!$tenant) {
            return [];
        }
        return $tenant->getMaintenanceModeData();
    }

    protected function getBypassCookieName(): string
    {
        return 'laravel_maintenance';
    }

    /**
     * Determine if the incoming request has a maintenance mode bypass cookie.
     *
     * @param Request $request
     * @param array $data
     * @return bool
     */
    protected function hasValidBypassCookie(Request $request, array $data): bool
    {
        return isset($data['secret']) &&
            $request->cookie($this->getBypassCookieName()) &&
            MaintenanceModeBypassCookie::isValid(
                $request->cookie($this->getBypassCookieName()),
                $data['secret']
            );
    }

    /**
     * Redirect the user back to the root of the application with a maintenance mode bypass cookie.
     *
     * @param string $secret
     * @return RedirectResponse
     */
    protected function bypassResponse(string $secret): RedirectResponse
    {
        return redirect('/')->withCookie(
            MaintenanceModeBypassCookie::create($secret)
        );
    }

    /**
     * Get the headers that should be sent with the response.
     *
     * @param array $data
     * @return array
     */
    protected function getHeaders(array $data): array
    {
        $headers = isset($data['retry']) ? ['Retry-After' => $data['retry']] : [];

        if (isset($data['refresh'])) {
            $headers['Refresh'] = $data['refresh'];
        }

        return $headers;
    }

    /**
     * Get the URIs that should be excluded.
     *
     * @return array
     */
    public function getExcludedPaths(): array
    {
        return array_merge($this->except, static::$neverPrevent);
    }

    /**
     * Indicate that the given URIs should always be accessible.
     *
     * @param array|string $uris
     * @return void
     */
    public static function except(array|string $uris): void
    {
        static::$neverPrevent = array_values(array_unique(
            array_merge(static::$neverPrevent, Arr::wrap($uris))
        ));
    }

    /**
     * Flush the state of the middleware.
     *
     * @return void
     */
    public static function flushState(): void
    {
        static::$neverPrevent = [];
    }
}
