import {Component, Input, OnInit} from '@angular/core';

@Component({
    selector: 'ki-datasource',
    templateUrl: './datasource.component.html',
    styleUrls: ['./datasource.component.sass']
})
export class DatasourceComponent implements OnInit {

    @Input() datasourceService: any;

    constructor() {
    }

    ngOnInit(): void {
        this.datasourceService.getDatasources().then(sources => {
            console.log(sources);
            this.datasourceService.getDatasource(sources[0].key).then(data => {
                console.log(data);
            });
        });
    }

}
