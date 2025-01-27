import {AfterViewInit, Component, Input, OnDestroy, OnInit} from '@angular/core';
import * as lodash from 'lodash';

const _ = lodash.default;
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {ImportDataComponent} from '../create-datasource/import-data/import-data.component';
import {ActivatedRoute, Router} from '@angular/router';
import {DatasourceService, DatasourceUpdate} from '../../../services/datasource.service';
import {DatasetService} from "../../../services/dataset.service";
import {MatLegacySnackBar as MatSnackBar} from '@angular/material/legacy-snack-bar';
import {Location} from '@angular/common';
import {
    ApiAccessComponent
} from '../create-datasource/api-access/api-access.component';
import {
    AdvancedSettingsComponent
} from '../create-datasource/advanced-settings/advanced-settings.component';
import {
    ImportWizardComponent
} from '../create-datasource/import-data/import-wizard/import-wizard.component';
import {BehaviorSubject} from 'rxjs';
import {CreateDatasetComponent} from "../../dataset/create-dataset/create-dataset.component";


declare var window: any;

@Component({
    selector: 'ki-create-datasource',
    templateUrl: './create-datasource.component.html',
    styleUrls: ['./create-datasource.component.sass']
})
export class CreateDatasourceComponent implements OnInit, AfterViewInit, OnDestroy {

    @Input() sidenavService: any;
    @Input() backURL = '/imported-data';
    @Input() reloadURL = 'import-data';
    @Input() backendURL: string;
    @Input() namePrefix = '';
    @Input() readonly = false;

    public readonly datasourceTypes: any = {
        'string': "Text (up to 255 chars)",
        'mediumstring': "Medium Text (up to 2000 chars)",
        'longstring': "Long Text (more than 2000 chars)",
        'integer': "Number (whole number)",
        'float': "Decimal number",
        'date': "Date",
        'datetime': "Date and Time",
        "pickfromsource": "Pick From List (using another source)"
    };

    public rows: any = [];
    public columns: any = [
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
    public selectedItem: any = {};
    public _ = _;
    public showUpload = false;
    public headerRow = false;
    public adds: any = [];
    public updates: any = [];
    public deletes: any = [];
    public invalidItems: any = [];
    public editMode = false;
    public autoIncrementColumn = false;
    public datasourceInstanceKey: string;
    public datasourceUpdate: any = {
        title: '',
        instanceImportKey: '',
        fields: [],
        adds: [],
        updates: [],
        deletes: []
    };
    public offset = 0;
    public limit = 25;
    public page = 1;
    public endOfResults = false;
    public showAutoIncrement = false;
    public selectAll = false;
    public showFilters = false;
    public filterFields = [];
    public filterJunction = {
        logic: 'AND',
        filters: [{
            lhsExpression: '',
            rhsExpression: [],
            filterType: ''
        }],
        filterJunctions: []
    };
    public sortConfig = null;
    public openSide = new BehaviorSubject(false);
    public Object = Object;


    private isMouseDown = false;
    private startRowIndex = null;
    private startCellIndex = null;
    private endRowIndex = null;
    private endCellIndex = null;
    private selectedCell = null;

    // Associative list of pick from datasets used for list selection
    private columnPickFromDatasets = {};


    constructor(private dialog: MatDialog,
                private route: ActivatedRoute,
                private datasourceService: DatasourceService,
                private datasetService: DatasetService,
                private snackbar: MatSnackBar,
                private location: Location,
                private router: Router
    ) {
    }

    ngOnInit(): void {
        this.datasourceInstanceKey = this.route.snapshot.params.key;

        if (this.datasourceInstanceKey) {
            this.showAutoIncrement = localStorage.getItem(this.datasourceInstanceKey + '_show_id') === 'true';
            this.loadDatasource();
        } else {
            const dialogRef = this.dialog.open(ImportWizardComponent, {
                width: '800px',
                height: '900px',
                disableClose: true,
                data: {
                    columns: this.columns,
                    datasourceUpdate: this.datasourceUpdate,
                    rows: this.rows,
                    datasourceInstanceKey: this.datasourceInstanceKey,
                    reloadURL: this.reloadURL,
                    namePrefix: this.namePrefix
                }
            });
            dialogRef.afterClosed().subscribe(res => {
                if (res === 'closed') {
                    this.location.back();
                }
            });
        }
    }

    ngAfterViewInit() {
        if (this.sidenavService) {
            setTimeout(() => {
                this.sidenavService.close();
            }, 100);
        }

        document.addEventListener('mouseup', () => {
            this.isMouseDown = false;
        });
    }

    ngOnDestroy() {
        if (this.sidenavService) {
            this.sidenavService.open();
        }
    }

    public clearFilter() {
        this.filterJunction = {
            logic: 'AND',
            filters: [{
                lhsExpression: '',
                rhsExpression: [],
                filterType: ''
            }],
            filterJunctions: []
        };
        this.loadDatasource();
    }

    public applyFilter() {
        this.loadDatasource();
    }

    public sortColumn(column: any, direction: string) {
        this.sortConfig = {fieldName: column.name, direction};
        this.loadDatasource();
    }

    public updateSelectAll() {
        this.rows.map(row => {
            row._selected = this.selectAll;
        });
    }

    public updateSelected() {
        this.selectAll = _.every(this.rows, '_selected');
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

    public addColumn(index?) {
        this.selectedItem = {
            _tableType: 'column',
            type: 'string'
        };

        if (index > -1) {
            this.columns.splice(index, 0, this.selectedItem);
            this.selectedItem._index = index;
        } else {
            this.columns.push(this.selectedItem);
            this.selectedItem._index = this.columns.length - 1;
        }

        setTimeout(() => {
            document.getElementById(this.selectedItem.type + this.selectedItem._index).scrollIntoView();
        });
    }

    public import() {
        const dialogRef = this.dialog.open(ImportDataComponent, {
            width: '800px',
            height: '900px',
            data: {
                columns: this.columns,
                datasourceUpdate: this.datasourceUpdate,
                rows: this.rows,
                datasourceInstanceKey: this.datasourceInstanceKey,
                reloadURL: this.reloadURL
            }
        });
        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                if (!this.selectedCell) {
                    const rowColIndex = 0;
                    this.selectedCell = {rowIndex: 0, rowColIndex};
                }

                if (res.headerRow) {
                    const columns = [];
                    res.data[0].forEach(header => {
                        columns.push({
                            title: header,
                            name: _.snakeCase(header),
                            type: 'string'
                        });
                    });
                    this.columns = columns;
                    res.data.shift();
                    this.insertCellData(res.data);

                } else {
                    this.insertCellData(res.data);
                }
            }
        });
    }

    public async deleteSelectedColumns() {
        const message = 'Are you sure you would like to remove all of the selected entries? This action cannot be reversed.';
        if (window.confirm(message)) {
            const selected = _.filter(this.rows, '_selected');
            await this.datasourceService.updateCustomDatasource(this.datasourceInstanceKey, {
                title: this.datasourceUpdate.title,
                instanceImportKey: this.datasourceUpdate.instanceImportKey || '',
                fields: this.datasourceUpdate.fields,
                adds: [],
                updates: [],
                deletes: selected
            });
            window.location.href = this.reloadURL + '/' + this.datasourceInstanceKey;
        }
    }

    public addFilter() {

    }

    public advancedSettings() {
        const dialogRef = this.dialog.open(AdvancedSettingsComponent, {
            width: '800px',
            height: '900px',
            data: {
                datasourceUpdate: this.datasourceUpdate,
                datasourceInstanceKey: this.datasourceInstanceKey,
                backendURL: this.backendURL,
                columns: this.columns,
                showAutoIncrement: this.showAutoIncrement
            }
        });
        dialogRef.afterClosed().subscribe(advancedSettings => {
            if (advancedSettings) {
                this.columns = advancedSettings.columns;
                this.datasourceUpdate.indexes = advancedSettings.datasourceUpdate.indexes;
                this.showAutoIncrement = advancedSettings.showAutoIncrement;
                localStorage.setItem(this.datasourceInstanceKey + '_show_id', String(this.showAutoIncrement));
            }
        });
    }

    public apiAccess() {
        const dialogRef = this.dialog.open(ApiAccessComponent, {
            width: '800px',
            height: '900px',
            data: {
                datasourceUpdate: this.datasourceUpdate,
                datasourceInstanceKey: this.datasourceInstanceKey,
                backendURL: this.backendURL,
                columns: this.columns
            }
        });
        dialogRef.afterClosed().subscribe(res => {

        });
    }


    public selectColumn(column, index) {
        if (!column.autoIncrement) {
            this.selectedCell = null;
            document.querySelectorAll('.datasource-table .bg-indigo-50').forEach(element => {
                element.classList.remove('bg-indigo-50');
            });

            if (index === this.selectedItem._index) {
                this.selectedItem = {};
            } else {
                column._tableType = 'column';
                column._index = index;
                if (!column.previousName) {
                    column.previousName = column.name;
                }

                this.selectedItem = column;

                this.removeCellFocusBorder();
            }
        }
    }

    public deleteColumn(index) {
        this.columns.splice(index, 1);
    }

    public addRow(index?) {
        const row = {};
        this.columns.forEach(column => {
            row[column.name] = '';
        });

        if (index > -1) {
            this.rows.splice(index, 0, row);
            this.adds.push(index);
        } else {
            this.rows.push(row);
            this.adds.push(this.rows.length - 1);
        }

        setTimeout(() => {
            const tableWrapper = document.getElementById('tableWrapper');
            tableWrapper.scrollTop = tableWrapper.scrollHeight;
        }, 0);

    }

    public deleteRow(rowIndex) {
        const inAdds = this.adds.indexOf(rowIndex);
        if (inAdds > -1) {
            this.rows.splice(rowIndex, 1);
            this.adds.pop();
        } else {
            this.deletes.push(rowIndex);
        }

        const inUpdates = this.updates.indexOf(rowIndex);
        if (inUpdates > -1) {
            this.updates.splice(inUpdates, 1);
        }
    }

    public focusCell(event, rowIndex, rowColIndex) {
        this.selectedCell = {rowIndex, rowColIndex};
        this.removeCellFocusBorder();
        event.target.parentElement.classList.add('border', 'border-blue-500');
    }

    public blurCell() {
        this.selectedCell = null;
        this.removeCellFocusBorder();
    }

    public clearCell(rowIndex, rowColIndex) {
        const columnName = this.columns[rowColIndex].name;
        this.rows[rowIndex][columnName] = '';
        if (this.adds.indexOf(rowIndex) === -1) {
            this.updates.push(rowIndex);
        }
    }

    public updateField(field, rowIndex) {
        if (this.adds.indexOf(rowIndex) === -1) {
            if (this.updates.indexOf(rowIndex) === -1) {
                this.updates.push(rowIndex);
            }
        }
        this.editMode = true;
    }

    public updateColumnName() {
        this.selectedItem.name = _.snakeCase(this.selectedItem.title);
        this.rows.map(row => {
            row[this.selectedItem.name] = row[this.selectedItem.previousName] || '';
            if (row[this.selectedItem.previousName]) {
                delete row[this.selectedItem.previousName];
            }

            return row;
        });
    }

    public enterCell(rowIndex, rowColIndex, event) {
        const row = {};
        let rowElement = null;
        let rowElementIndex;
        this.columns.forEach(column => {
            row[column.name] = '';
        });
        if (rowIndex === this.rows.length - 1) {
            this.rows.push(row);
            rowElementIndex = this.rows.length - 1;
            this.adds.push(this.rows.length - 1);
        } else {
            rowElementIndex = rowIndex + 1;
        }

        setTimeout(() => {
            rowElement = document.querySelectorAll('.table-rows').item(rowElementIndex);
            const rowCell: any = rowElement.querySelectorAll('.row-cell').item(this.showAutoIncrement ? rowColIndex + 1 : rowColIndex);
            rowCell.querySelectorAll('.cell-input').item(0).focus();
        }, 0);
    }

    public async save(exit = false) {
        if (!this.datasourceUpdate.title) {
            window.alert('Please enter a title for this Datasource.');
            return;
        }

        if (!_.find(this.columns, {keyField: true}) &&
            !_.find(this.columns, {type: 'id'})) {
            window.alert('Please select one of your fields as the "Unique Key"');
            return;
        }

        this.datasourceUpdate.fields = this.columns.map(column => {
            if (column.name === column.previousName) {
                delete column.previousName;
            }
            return column;
        });

        const changes = ['adds', 'updates', 'deletes'];
        changes.forEach(change => {
            this.datasourceUpdate[change] = _.map(_.uniq(this[change]), rowIndex => {
                return this.rows[rowIndex];
            });
        });

        if (this.namePrefix && !this.datasourceUpdate.title.includes(this.namePrefix)) {
            this.datasourceUpdate.title = this.namePrefix + this.datasourceUpdate.title;
        }

        // Reset invalid items
        this.invalidItems = [];

        if (!this.datasourceInstanceKey) {
            await this.datasourceService.createCustomDatasource(this.datasourceUpdate).then(key => {
                if (!exit) {
                    window.location.href = this.reloadURL + '/' + key;
                }
                return true;
            }).catch(err => {
                const errorCode = (err.error && err.error.sqlStateCode) ? err.error.sqlStateCode : 0;
                this.displayError(errorCode);
            });
        } else {
            this.datasourceUpdate.importKey = this.datasourceUpdate.instanceImportKey;
            await this.datasourceService.updateCustomDatasource(this.datasourceInstanceKey, this.datasourceUpdate)
                .then(async (result: any) => {
                    if (result && result.rejected && result.rejected > 0) {

                        this.snackbar.open("There were validation problems with one or more of your rows - see highlighted cells below", null, {
                            duration: 5000,
                            verticalPosition: 'top'
                        });


                        let invalidAddRows = _.map(result.validationErrors.add || [], "itemNumber");
                        for (let i = this.adds.length - 1; i >= 0; i--) {
                            if (!invalidAddRows.includes(i)) {
                                this.adds.splice(i, 1);
                            } else {
                                this.invalidItems[this.adds[i]] = _.find(result.validationErrors.add, {"itemNumber": i}).validationErrors;
                            }
                        }


                        let invalidUpdateRows = _.map(result.validationErrors.update || [], "itemNumber");
                        for (let i = this.updates.length - 1; i >= 0; i--) {
                            if (!invalidUpdateRows.includes(i)) {
                                this.updates.splice(i, 1);
                            } else {
                                this.invalidItems[this.updates[i]] = _.find(result.validationErrors.update, {"itemNumber": i}).validationErrors;
                            }
                        }


                    } else {
                        this.adds = [];
                        this.updates = [];
                        this.deletes = [];


                        await this.loadDatasource();
                    }
                    return true;
                })
                .catch(err => {
                    console.log('ERROR', err);
                    const errorCode = (err.error && err.error.sqlStateCode) ? err.error.sqlStateCode : 0;
                    this.displayError(errorCode);
                });
        }

        if (exit) {
            return this.router.navigate([this.backURL]);
        }
    }

    public cancelChanges() {

        this.adds = [];
        this.updates = [];
        this.deletes = [];

        this.loadDatasource();
    }

    public deleteSelectedColumn() {
        this.columns.splice(this.selectedItem._index, 1);
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


    // Get input type for field
    public inputTypeForField(field: any) {
        switch (field.type) {
            case "integer":
            case "float":
                return "number";
            case "date":
                return "date";
            case "datetime":
                return "datetime-local";
            default:
                return "text";
        }
    }

    public updateColumnRequired(required, field: any) {
        if (required) {
            field.validatorConfigs = [{
                "validatorKey": "required"
            }]
        } else {
            field.validatorConfigs = [];
        }
    }

    private loadDatasource() {
        if (this.datasourceInstanceKey) {

            // If we have a filter in use, apply it to the next load...
            const transformationInstances = [];
            if (this.filterJunction.filterJunctions.length || this.filterJunction.filters[0].filterType) {
                transformationInstances.push({type: 'filter', config: this.filterJunction});
            }
            if (this.sortConfig) {
                transformationInstances.push({type: 'multisort', config: {sorts: [this.sortConfig]}});
            }

            const evaluatedDatasource = {
                key: this.datasourceInstanceKey,
                transformationInstances,
                parameterValues: {},
                offset: this.offset,
                limit: this.limit
            };

            this.datasourceService.evaluateDatasource(evaluatedDatasource).then((res: any) => {
                this.columns = res.columns.map(column => {
                    column.previousName = column.name;

                    if (column.type == "pickfromsource") {
                        if (!this.columnPickFromDatasets[column.name])
                            this.populateColumnPickFrom(column);
                    }

                    return column;
                });
                this.filterFields = _.map(this.columns, column => {
                    return {
                        title: column.title,
                        name: column.name
                    };
                });
                this.rows = res.allData;

                this.endOfResults = this.rows.length < this.limit;

                this.datasourceUpdate.title = res.instanceTitle;
                this.datasourceUpdate.instanceImportKey = res.instanceImportKey;
                this.datasourceUpdate.indexes = res.indexes;
                this.datasourceUpdate.adds = [];
                this.datasourceUpdate.updates = [];
                this.datasourceUpdate.deletes = [];

                this.autoIncrementColumn = _.some(this.columns, {type: 'id'});

                this.selectAll = false;
            });
        }
        this.editMode = false;
    }

    private selectCell(rowIndex, cellIndex) {
        let rowStart, rowEnd, cellStart, cellEnd;

        if (rowIndex < this.startRowIndex) {
            rowStart = rowIndex;
            rowEnd = this.startRowIndex;
        } else {
            rowStart = this.startRowIndex;
            rowEnd = rowIndex;
        }

        if (cellIndex < this.startCellIndex) {
            cellStart = cellIndex;
            cellEnd = this.startCellIndex;
        } else {
            cellStart = this.startCellIndex;
            cellEnd = cellIndex;
        }

        for (let i = rowStart; i <= rowEnd; i++) {
            const rowElement = document.querySelectorAll('.table-rows').item(i);
            const rowCells = rowElement.querySelectorAll('td');
            for (let j = cellStart; j <= cellEnd; j++) {
                rowCells.item(j).classList.add('bg-indigo-50');
            }
        }
    }

    private removeCellFocusBorder() {
        document.querySelectorAll('.input-wrapper.border-blue-500').forEach(element => {
            element.classList.remove('border-blue-500');
        });
    }

    private insertCellData(data) {
        const row = {};
        this.columns.forEach(column => {
            row[column.name] = '';
        });

        let startIndex = this.selectedCell.rowIndex;
        while (this.rows.length < (data.length + this.selectedCell.rowIndex)) {
            this.rows.push(_.clone(row));
            this.deletes.splice(startIndex, 1);
            this.updates.splice(startIndex, 1);
            this.adds.splice(startIndex, 1);
            this.adds.push(startIndex);
            startIndex++;
        }

        let rowIndex = 0;
        for (let i = this.selectedCell.rowIndex; i < (this.selectedCell.rowIndex + data.length); i++) {
            let valIndex = 0;
            const values = _.values(data[rowIndex]);
            for (let j = this.selectedCell.rowColIndex; j < (values.length + this.selectedCell.rowColIndex); j++) {
                if (this.columns[j]) {
                    this.rows[i][this.columns[j].name] = values[valIndex] || '';
                }
                valIndex++;
            }
            rowIndex++;
        }
    }

    private displayError(code: any) {
        let message = 'There was an error creating/editing your data source. Please check and try again.';

        switch (Number(code)) {
            case 23000:
                message = 'Duplicate Unique Key Found. Please check for duplicate values in unique identity column.';
                break;
        }

        this.snackbar.open(message, null, {
            duration: 5000,
            verticalPosition: 'top'
        });
    }

    public selectColumnPickFromDatasource(column: any) {

        const dialogRef = this.dialog.open(CreateDatasetComponent, {
            width: '1200px',
            height: '800px',
            data: {}
        });
        dialogRef.afterClosed().subscribe(async res => {
            if (res) {
                column.typeConfig = {
                    dataSetInstanceId: res.datasetInstanceId,
                    datasourceInstanceKey: res.datasourceInstanceKey
                };

                // Populate the column pick from
                this.populateColumnPickFrom(column);
            }
        });
    }


    // Pick from datasets
    private populateColumnPickFrom(column: any) {
        this.datasetService.evaluateDataset(column.typeConfig, '0', '10000000').then((dataset) => {
            this.columnPickFromDatasets[column.name] = dataset;
        });
    }
}
