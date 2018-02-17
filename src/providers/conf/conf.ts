export const CONF = {
    BG_GPS: {
        url: 'http://my_server_api/locator/gps',
        desiredAccuracy: 1000,
        stationaryRadius: 50,
        distanceFilter: 50,
        httpHeaders: {
            _token: 'my_super_secret_token'
        },
        debug: true,
        notificationTitle: 'Background tracking',
        notificationText: 'enabled',
        notificationIconColor: '#FEDD1E',
        interval: 60000,
        stopOnTerminate: false,
        startOnBoot: true,
        activityType: 'AutomotiveNavigation',
        saveBatteryOnBackground: true
    }
};