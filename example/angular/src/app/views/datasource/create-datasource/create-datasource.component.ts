import {Component, OnInit} from '@angular/core';
import {SidenavService} from '../../../services/sidenav.service';

@Component({
    selector: 'app-create-datasource',
    templateUrl: './create-datasource.component.html',
    styleUrls: ['./create-datasource.component.sass']
})
export class CreateDatasourceComponent implements OnInit {

    constructor(public sidenavService: SidenavService) {
    }

    ngOnInit(): void {
    }

}
