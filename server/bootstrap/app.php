<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Http\Middleware;
use App\Model\Gps;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Laravel\Lumen\Application;
use Laravel\Lumen\Routing\Router;

(new Dotenv\Dotenv(__DIR__ . '/../env/'))->load();

$app = new Application(__DIR__ . '/..');
$app->singleton(ExceptionHandler::class, App\Exceptions\Handler::class);
$app->routeMiddleware([
    'auth'  => Middleware\AuthMiddleware::class,
    'basic' => Middleware\BasicAuthMiddleware::class,
]);

$app->router->group(['middleware' => 'auth', 'prefix' => '/locator'], function (Router $route) {
    $route->post('/gps', function (Gps $gps, Request $request) {
        $requestData = $request->all();
        foreach ($requestData as $poi) {
            $gps->persistsData([
                'date'             => date('YmdHis'),
                'serverTime'       => time(),
                'time'             => $poi['time'],
                'latitude'         => $poi['latitude'],
                'longitude'        => $poi['longitude'],
                'accuracy'         => $poi['accuracy'],
                'speed'            => $poi['speed'],
                'altitude'         => $poi['altitude'],
                'locationProvider' => $poi['locationProvider'],
            ]);
        }

        return 'OK';
    });
});

$app->router->group(['middleware' => 'basic', 'prefix' => '/map'], function (Router $route) {
    $route->get('/', function (Gps $gps) {
        return view("index", $gps->getLast());
    });

    $route->get('/last', function (Gps $gps) {
        return $gps->getLast();
    });
});

return $app;