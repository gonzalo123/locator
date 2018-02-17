## Playing with Ionic, Lumen, Firebase, Google maps, Raspberry Pi and background geolocation.

I wanna do a simple pet project. The idea is to build a mobile application. This application will track my gps location and send this information to a Firebase database. I've never play with Firebase and I want to learn a little bit. With this information I will build a simple web application hosted in my Raspberry py. This web application will show a Google map with my last location. I will put this web application in my TV and anyone in my house will see where I am every time.

That's the idea. I want a MVP. First the mobile application. I will use ionic framework. 
The mobile application is very simple it only has a toggle to activate-deactivate the background geolocation (sometimes I don't want to be tracked :).

```html
<ion-header>
    <ion-navbar>
        <ion-title>
            Ionic Blank
        </ion-title>
    </ion-navbar>
</ion-header>

<ion-header>
    <ion-toolbar [color]="toolbarColor">
        <ion-title>{{title}}</ion-title>
        <ion-buttons end>
            <ion-toggle color="light"
                        checked="{{isBgEnabled}}"
                        (ionChange)="changeWorkingStatus($event)">
            </ion-toggle>
        </ion-buttons>
    </ion-toolbar>
</ion-header>

<ion-content padding>
</ion-content>
```

And the controller:
```typescript
import {Component} from '@angular/core';
import {Platform} from 'ionic-angular';
import {LocationTracker} from "../../providers/location-tracker/location-tracker";

@Component({
    selector: 'page-home',
    templateUrl: 'home.html'
})
export class HomePage {
    public status: string = localStorage.getItem('status') || "-";
    public title: string = "";
    public isBgEnabled: boolean = false;
    public toolbarColor: string;

    constructor(platform: Platform,
                public locationTracker: LocationTracker) {

        platform.ready().then(() => {

                if (localStorage.getItem('isBgEnabled') === 'on') {
                    this.isBgEnabled = true;
                    this.title = "Working ...";
                    this.toolbarColor = 'secondary';
                } else {
                    this.isBgEnabled = false;
                    this.title = "Idle";
                    this.toolbarColor = 'light';
                }
        });
    }

    public changeWorkingStatus(event) {
        if (event.checked) {
            localStorage.setItem('isBgEnabled', "on");
            this.title = "Working ...";
            this.toolbarColor = 'secondary';
            this.locationTracker.startTracking();
        } else {
            localStorage.setItem('isBgEnabled', "off");
            this.title = "Idle";
            this.toolbarColor = 'light';
            this.locationTracker.stopTracking();
        }
    }
}
```

As you can see, the toggle button will activate-deactivate the background geolocation and it also changes de background color of the toolbar.

For background geolocation I will use one cordova plugin available as ionic native plugin: https://ionicframework.com/docs/native/background-geolocation/

Here you can see read a very nice article explaining how to use the plugin with ionic: https://www.joshmorony.com/adding-background-geolocation-to-an-ionic-2-application/

As the article explains I've created a provider 
```typescript
import {Injectable, NgZone} from '@angular/core';
import {BackgroundGeolocation} from '@ionic-native/background-geolocation';
import {CONF} from "../conf/conf";

@Injectable()
export class LocationTracker {
    constructor(public zone: NgZone,
                private backgroundGeolocation: BackgroundGeolocation) {
    }

    showAppSettings() {
        return this.backgroundGeolocation.showAppSettings();
    }

    startTracking() {
        this.startBackgroundGeolocation();
    }

    stopTracking() {
        this.backgroundGeolocation.stop();
    }

    private startBackgroundGeolocation() {
        this.backgroundGeolocation.configure(CONF.BG_GPS);
        this.backgroundGeolocation.start();
    }
}
```

The idea of the plugin is send a POST request to a url with the gps data in the body of the request. So, I will create a web api server to handle this request. I will use my Raspberry Pi3 (one of mines :) to serve the application. I will create a simple PHP/Lumen application. This application will handle the POST request of the mobile application and also will serve a html page with the map (using google maps). 

Mobile requests will be authenticated with a token in the header and web application will use a basic http authentication. Because of that I will create two middlewares to handle the the different ways to authenticate.

```php
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

return $app;
```

As we can see the route /locator/gps will handle the post request. I've created a model to persists gps data in the firebase database:

```php
<?php

namespace App\Model;

use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class Gps
{
    private $database;

    private const FIREBASE_CONF = __DIR__ . '/../../conf/firebase.json';

    public function __construct()
    {
        $serviceAccount = ServiceAccount::fromJsonFile(self::FIREBASE_CONF);
        $firebase       = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->create();

        $this->database = $firebase->getDatabase();
    }

    public function getLast()
    {
        $value = $this->database->getReference('gps/poi')
            ->orderByKey()
            ->limitToLast(1)
            ->getValue();

        $out                 = array_values($value)[0];
        $out['formatedDate'] = \DateTimeImmutable::createFromFormat('YmdHis', $out['date'])->format('d/m/Y H:i:s');

        return $out;
    }

    public function persistsData(array $data)
    {
        return $this->database
            ->getReference('gps/poi')
            ->push($data);
    }
}
```

The project is almost finished. Now we only need to create the google map.

That's the api
```php
<?php
$app->router->group(['middleware' => 'basic', 'prefix' => '/map'], function (Router $route) {
    $route->get('/', function (Gps $gps) {
        return view("index", $gps->getLast());
    });

    $route->get('/last', function (Gps $gps) {
        return $gps->getLast();
    });
});
```

And the HTML
```html
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>Locator</title>
    <style>
        #map {
            height: 100%;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
</head>
<body>
<div id="map"></div>
<script>

    var lastDate;
    var DELAY = 60;

    function drawMap(lat, long, text) {
        var CENTER = {lat: lat, lng: long};
        var contentString = '<div id="content">' + text + '</div>';
        var infowindow = new google.maps.InfoWindow({
            content: contentString
        });
        var map = new google.maps.Map(document.getElementById('map'), {
            zoom: 11,
            center: CENTER,
            disableDefaultUI: true
        });

        var marker = new google.maps.Marker({
            position: CENTER,
            map: map
        });
        var trafficLayer = new google.maps.TrafficLayer();

        trafficLayer.setMap(map);
        infowindow.open(map, marker);
    }

    function initMap() {
        lastDate = '{{ $formatedDate }}';
        drawMap({{ $latitude }}, {{ $longitude }}, lastDate);
    }

    setInterval(function () {
        fetch('/map/last', {credentials: "same-origin"}).then(function (response) {
            response.json().then(function (data) {
                if (lastDate !== data.formatedDate) {
                    drawMap(data.latitude, data.longitude, data.formatedDate);
                }
            });
        });
    }, DELAY * 1000);
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key=my_google_maps_key&callback=initMap">
</script>
</body>
</html>
```

And that's all just enough for a weekend.


