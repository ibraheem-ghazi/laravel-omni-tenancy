<?php

namespace IbraheemGhazi\OmniTenancy\Http\Controllers;

use IbraheemGhazi\OmniTenancy\Tenancy;
use IbraheemGhazi\OmniTenancy\Contracts\TenantObject;
use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Throwable;
use Closure;

class TenancyFilesController extends Controller
{

    public function __construct()
    {
        $this->middleware(static function (Request $request, Closure $next){
            abort_unless(Tenancy::context()->getCurrentTenant(), 404);
            return $next($request);
        });
    }

    public function serve($key = null, $path = null)
    {
        $servablePaths = config('tenancy.servable_paths.paths', []);
        
        abort_unless(filled($key) , 404);
        abort_unless(filled($path), 404);
        abort_unless(array_key_exists($key, $servablePaths), 404);

        $keyToPath = config($servablePaths[$key]);

        abort_unless(filled($keyToPath) || !is_dir($keyToPath), 404);

        try {
            return response()->file("$keyToPath/$path");
        } catch (Throwable $th) {
            abort(404);
        }
    }
}
