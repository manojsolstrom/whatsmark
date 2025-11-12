<?php

namespace Corbital\Installer\Http\Middleware;

use Closure;
use Corbital\Installer\Classes\UpdateChecker;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateBackendRequest
{
    protected $updateChecker;

    public function __construct(UpdateChecker $updateChecker)
    {
        $this->updateChecker = $updateChecker;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // License validation bypassed - allow all requests
        return $next($request);
    }
}
