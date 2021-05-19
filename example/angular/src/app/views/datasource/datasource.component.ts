import {Component, OnInit} from '@angular/core';
import {DatasourceService} from '../../services/datasource.service';

@Component({
    selector: 'app-datasource',
    templateUrl: './datasource.component.html',
    styleUrls: ['./datasource.component.sass']
})
export class DatasourceComponent implements OnInit {

    constructor(public datasourceService: DatasourceService) {
    }

    ngOnInit(): void {
    }

}
