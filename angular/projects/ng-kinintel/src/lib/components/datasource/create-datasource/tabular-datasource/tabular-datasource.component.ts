import { Component } from '@angular/core';
import * as lodash from 'lodash';
import {DatasourceService} from '../../../../services/datasource.service';
const _ = lodash.default;
import '@revolist/revogrid';

@Component({
    selector: 'ki-tabular-datasource',
    templateUrl: './tabular-datasource.component.html',
    styleUrls: ['./tabular-datasource.component.sass'],
    standalone: false
})
export class TabularDatasourceComponent {

    public datasourceInstanceKey: string;
    public selectedItem: any = {};
    public autoIncrementColumn = false;
    public datasourceUpdate: any = {
        title: '',
        instanceImportKey: '',
        fields: [],
        adds: [],
        updates: [],
        deletes: []
    };
    public limit = 25;
    public page = 1;
    public endOfResults = false;
    public _ = _;
    public readonly datasourceTypes: any = [
        'string', 'integer', 'float', 'date', 'datetime', 'mediumstring', 'longstring'
    ];
    public rows: any = [];
    public columns: any = [
        {
            name: 'Column 1',
            prop: 'column_1',
            type: 'string'
        },
        {
            name: 'Column 2',
            prop: 'column_2',
            type: 'string'
        }
    ];

    private offset = 0;

    constructor(private datasourceService: DatasourceService) {
        this.rows = [{
            column_1: 'hello',
            column_2: 'world'
        }, {
            column_1: 'hello',
            column_2: 'world'
        }];
    }

    public pageSizeChange(value) {
        this.limit = value;
        this.loadDatasource();
    }

    public increaseOffset() {
        this.page = this.page + 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.loadDatasource();
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.loadDatasource();
    }

    public deleteSelectedColumn() {

    }

    public addRow() {

    }

    public addColumn() {

    }

    public updateColumnName() {

    }

    public resetTable() {
        const message = 'Are you sure you would like to reset the entire table? This will remove all data and columns. This action cannot be undone.';
        if (window.confirm(message)) {
            this.rows = [];
            this.columns = [
                {
                    title: 'Column 1',
                    name: 'column_1',
                    type: 'string'
                },
                {
                    title: 'Column 2',
                    name: 'column_2',
                    type: 'string'
                }
            ];
        }
    }

    public updateAutoIncrementColumn(value) {
        if (value) {
            this.columns.map(column => {
                column.keyField = false;
                return column;
            });

            this.columns.unshift({
                title: 'ID',
                name: 'id',
                type: 'id'
            });
        } else {
            _.remove(this.columns, {type: 'id'});
        }
    }

    public import() {

    }

    public save(exit = false) {

    }

    public apiAccess() {

    }

    private loadDatasource() {
        if (this.datasourceInstanceKey) {
            const evaluatedDatasource = {
                key: this.datasourceInstanceKey,
                transformationInstances: [],
                parameterValues: {},
                offset: this.offset,
                limit: this.limit
            };
            this.datasourceService.evaluateDatasource(evaluatedDatasource).then((res: any) => {
                this.columns = res.columns.map(column => {
                    column.previousName = column.name;
                    return column;
                });
                this.rows = res.allData;

                this.endOfResults = this.rows.length < this.limit;

                this.datasourceUpdate.title = res.instanceTitle;
                this.datasourceUpdate.instanceImportKey = res.instanceImportKey;

                this.autoIncrementColumn = _.some(this.columns, {type: 'id'});
            });
        }
    }
}
