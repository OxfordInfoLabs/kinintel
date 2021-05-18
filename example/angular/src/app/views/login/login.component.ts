import {Component, OnInit} from '@angular/core';
import {AuthenticationService} from 'ng-kiniauth';

@Component({
    selector: 'app-login',
    templateUrl: './login.component.html',
    styleUrls: ['./login.component.sass']
})
export class LoginComponent implements OnInit {

    constructor(private authService: AuthenticationService) {
    }

    ngOnInit() {
        this.authService.getSessionData();
    }

}
