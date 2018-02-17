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
