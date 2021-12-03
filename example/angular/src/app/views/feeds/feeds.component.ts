import {Component, OnInit} from '@angular/core';
import {environment} from '../../../environments/environment';

@Component({
    selector: 'app-feeds',
    templateUrl: './feeds.component.html',
    styleUrls: ['./feeds.component.sass']
})
export class FeedsComponent implements OnInit {

    public environment = environment;

    constructor() {
    }

    ngOnInit(): void {
    }

}
