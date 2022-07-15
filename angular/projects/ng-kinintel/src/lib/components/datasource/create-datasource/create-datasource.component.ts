import {AfterViewInit, Component, Input, OnDestroy, OnInit} from '@angular/core';
import * as _ from 'lodash';
import {MatDialog} from '@angular/material/dialog';
import {ImportDataComponent} from '../create-datasource/import-data/import-data.component';
import {ActivatedRoute} from '@angular/router';
import {DatasourceService, DatasourceUpdate} from '../../../services/datasource.service';
import {MatSnackBar} from '@angular/material/snack-bar';

declare var window: any;

@Component({
    selector: 'ki-create-datasource',
    templateUrl: './create-datasource.component.html',
    styleUrls: ['./create-datasource.component.sass']
})
export class CreateDatasourceComponent implements OnInit, AfterViewInit, OnDestroy {

    @Input() sidenavService: any;

    public readonly datasourceTypes: any = [
        'string', 'integer', 'float', 'date', 'datetime'
    ];

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
    public autoIncrementColumn = false;
    public datasourceInstanceKey: string;
    public datasourceUpdate: DatasourceUpdate = {
        title: '',
        fields: [],
        adds: [],
        updates: [],
        deletes: []
    };
    public limit = 25;
    public page = 1;
    public endOfResults = false;

    private offset = 0;
    private isMouseDown = false;
    private startRowIndex = null;
    private startCellIndex = null;
    private endRowIndex = null;
    private endCellIndex = null;
    private selectedCell = null;

    constructor(private dialog: MatDialog,
                private route: ActivatedRoute,
                private datasourceService: DatasourceService,
                private snackbar: MatSnackBar) {
    }

    ngOnInit(): void {
        this.datasourceInstanceKey = this.route.snapshot.params.key;

        this.loadDatasource();
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

        document.querySelector('.datasource-table')
            .addEventListener('copy', (event: ClipboardEvent) => {
                const data = _.clone(this.rows);
                const rows = data.splice(this.startRowIndex, this.endRowIndex + 1);
                const copyData = _.map(rows, (value) => {
                    const cols = Object.keys(value).splice(this.startCellIndex, this.endCellIndex + 1);
                    const row = {};
                    cols.forEach(col => {
                        row[col] = value[col];
                    });

                    return row;
                });

                event.clipboardData.setData('text', JSON.stringify(copyData));

                event.preventDefault();
            });

        window.addEventListener('paste', (event: any) => {
            if (this.selectedCell) {
                const clipboardData = (event.clipboardData || window.clipboardData);
                let data = [];

                const text = clipboardData.getData('text');

                try {
                    data = JSON.parse(text);

                    this.insertCellData(data);
                } catch (error) {
                    data = text.split('\n');
                    data = data.map(rows => {
                        return rows.split('\t');
                    });

                    this.insertCellData(data);
                }
            }
            event.preventDefault();
        });
    }

    ngOnDestroy() {
        if (this.sidenavService) {
            this.sidenavService.open();
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

    public addColumn(index?) {
        this.selectedItem = {
            _tableType: 'column',
            type: null
        };

        if (index > -1) {
            this.columns.splice(index, 0, this.selectedItem);
            this.selectedItem._index = index;
        } else {
            this.columns.push(this.selectedItem);
            this.selectedItem._index = this.columns.length - 1;
        }
    }

    public import() {
        const dialogRef = this.dialog.open(ImportDataComponent, {
            width: '600px',
            height: '600px',
            data: {
                columns: this.columns
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

    public clearColumnContents(columnName) {
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
            this.updates.push(rowIndex);
        }
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
            const rowCell: any = rowElement.querySelectorAll('.row-cell').item(rowColIndex);
            rowCell.querySelectorAll('.cell-input').item(0).focus();
        }, 0);
    }

    public deleteCell(value, rowIndex, rowColIndex) {
        if (!value.length) {

            if (rowColIndex > 0) {
                setTimeout(() => {
                    const rowElement = document.querySelectorAll('.table-rows').item(rowIndex);
                    const rowCell: any = rowElement.querySelectorAll('.row-cell').item(rowColIndex - 1);
                    rowCell.querySelectorAll('.cell-input').item(0).focus();
                }, 0);
                return;
            }

            if (_.filter(this.rows[rowIndex]).length === 0 &&
                rowColIndex === 0 && rowIndex > 0) {

                const rowElement = document.querySelectorAll('.table-rows').item(rowIndex - 1);
                const rowCell: any = rowElement.querySelectorAll('.row-cell').item(0);
                rowCell.querySelectorAll('.cell-input').item(0).focus();

                this.deleteRow(rowIndex);
            }
        }

    }

    public save() {
        if (!this.datasourceUpdate.title) {
            window.alert('Please enter a title for this Datasource.');
            return;
        }

        if (!_.find(this.columns, {keyField: true}) &&
            !_.find(this.columns, {type: 'id'})) {
            window.alert('Please select one of your fields as the "Primary Key"');
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

        if (!this.datasourceInstanceKey) {
            this.datasourceService.createCustomDatasource(this.datasourceUpdate).then(key => {
                window.location.href = '/import-data/' + key;
            }).catch(err => {
                const errorCode = (err.error && err.error.sqlStateCode) ? err.error.sqlStateCode : 0;
                this.displayError(errorCode);
            });
        } else {
            this.datasourceService.updateCustomDatasource(this.datasourceInstanceKey, this.datasourceUpdate)
                .then(() => {
                    this.adds = [];
                    this.updates = [];
                    this.deletes = [];
                    this.loadDatasource();
                })
                .catch(err => {
                    console.log('ERROR', err);
                    const errorCode = (err.error && err.error.sqlStateCode) ? err.error.sqlStateCode : 0;
                    this.displayError(errorCode);
                });
        }
    }

    public deleteSelectedColumn() {
        this.columns.splice(this.selectedItem._index, 1);
    }

    public mousedownSelection(event, rowIndex, cellIndex) {
        this.isMouseDown = true;

        document.querySelectorAll('.datasource-table .bg-indigo-50').forEach(element => {
            element.classList.remove('bg-indigo-50');
        });

        this.startRowIndex = rowIndex;
        this.startCellIndex = cellIndex;
        this.endRowIndex = rowIndex;
        this.endCellIndex = cellIndex;
    }

    public mouseoverSelection(event, rowIndex, cellIndex) {
        if (!this.isMouseDown) {
            return;
        }

        document.querySelectorAll('.row-cell.bg-indigo-50').forEach(element => {
            element.classList.remove('bg-indigo-50');
        });

        this.selectCell(rowIndex, cellIndex);
        this.endRowIndex = rowIndex;
        this.endCellIndex = cellIndex;
    }

    public resetTable() {
        const message = 'Are you sure you would like to reset the entire table? This will remove all data and columns. This action cannot be undone.';
        if (window.confirm(message)) {
            this.selectedCell = null;
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

                this.autoIncrementColumn = _.some(this.columns, {type: 'id'});
            });
        }
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
                message = 'Duplicate Primary Key Found. Please check for duplicate values in unique identity column.';
                break;
        }

        this.snackbar.open(message, null, {
            duration: 5000,
            verticalPosition: 'top'
        });
    }
}
