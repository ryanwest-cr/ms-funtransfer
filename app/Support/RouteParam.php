<?php
/**
 * Created by PhpStorm.
 * User: sune
 * Date: 09/03/2017
 * Time: 17.30
 */

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * This helper makes it easier to work with parameters on the Lumen array-based router.
 *
 * Class Route
 *
 * @package App\Support
 */
class RouteParam
{
    /**
     * Get a route parameter from the request.
     *
     * @param Request $request
     * @param string $param
     * @return boolean
     */
    static public function exists(Request $request, $param)
    {
        return Arr::exists($request->route()[2], $param);
    }

    /**
     * Get a route parameter from the request.
     *
     * @param Request $request
     * @param string $param
     * @param mixed|null $default
     * @return mixed
     */
    static public function get(Request $request, $param, $default = null)
    {
        return Arr::get($request->route()[2], $param, $default);
    }

    /**
     * Set a route parameter on the request.
     *
     * @param Request $request
     * @param string $param
     * @param mixed $value
     */
    static public function set(Request &$request, $param, $value)
    {
        $route = $request->route();
        $route[2][$param] = $value;

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });
    }

    /**
     * Forget a route parameter on the Request.
     *
     * @param Request $request
     * @param string $param
     */
    static public function forget(Request &$request, $param)
    {
        $route = $request->route();
        Arr::forget($route[2], $param);

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });
    }

}
