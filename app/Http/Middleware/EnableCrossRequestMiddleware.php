<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnableCrossRequestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] :'';
        $origin_array = array('http://www.xiaodian.com');
        if($origin && in_array($origin,$origin_array)){
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Allow-Methods: *");
            header("Access-Control-Allow-Headers: Content-Type,Access-Token");
            header("Access-Control-Expose-Headers: *");
        }
        return $next($request);
    }
}
