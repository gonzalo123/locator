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