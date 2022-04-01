import {Component, Input, OnInit} from '@angular/core';
import {DatasetService} from '../../services/dataset.service';
import {BehaviorSubject} from 'rxjs';
import * as _ from 'lodash';

@Component({
    selector: 'ki-whitelisted-sql-functions',
    templateUrl: './whitelisted-sql-functions.component.html',
    styleUrls: ['./whitelisted-sql-functions.component.sass']
})
export class WhitelistedSqlFunctionsComponent implements OnInit {

    @Input() search = new BehaviorSubject('');
    @Input() fields: any = [];

    public docs: any = [];
    public sqlFunctions: any = {};
    public showColumns = false;
    public Object = Object;

    constructor(private datasetService: DatasetService) {
    }

    ngOnInit(): void {

        this.datasetService.getWhiteListedSQLFunctions().then((sql: any) => {
            _.forEach(sql, (sqlFunction, name) => {
                if (!this.sqlFunctions[sqlFunction.category]) {
                    this.sqlFunctions[sqlFunction.category] = {category: _.startCase(sqlFunction.category), functions: []};
                }
                sqlFunction.name = name;
                this.sqlFunctions[sqlFunction.category].functions.push(sqlFunction);
            });
            console.log(this.sqlFunctions);
        });
    }

}
