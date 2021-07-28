import {Component, OnInit} from '@angular/core';
import {AuthenticationService} from 'ng-kiniauth';
import {environment} from '../../../environments/environment';

@Component({
    selector: 'app-login',
    templateUrl: './login.component.html',
    styleUrls: ['./login.component.sass']
})
export class LoginComponent implements OnInit {

    public environment = environment;

    constructor(private authService: AuthenticationService) {
    }

    ngOnInit() {
        this.authService.getSessionData();
    }

}
