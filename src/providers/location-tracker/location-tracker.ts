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
