import {Component, Input, OnInit} from '@angular/core';
import {DatasetService} from '../../services/dataset.service';
import {BehaviorSubject} from 'rxjs';

@Component({
    selector: 'ki-whitelisted-sql-functions',
    templateUrl: './whitelisted-sql-functions.component.html',
    styleUrls: ['./whitelisted-sql-functions.component.sass']
})
export class WhitelistedSqlFunctionsComponent implements OnInit {

    @Input() search = new BehaviorSubject('');

    public sqlFunctions: any = [];

    constructor(private datasetService: DatasetService) {
    }

    ngOnInit(): void {
        this.datasetService.getWhiteListedSQLFunctions().then(sql => {
            console.log('SQL', sql);
        });
    }

}
