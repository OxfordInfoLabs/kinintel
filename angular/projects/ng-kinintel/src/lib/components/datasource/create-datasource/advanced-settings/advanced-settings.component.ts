import {CdkDragDrop, moveItemInArray, transferArrayItem} from '@angular/cdk/drag-drop';
import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import * as lodash from 'lodash';
import {DatasourceService} from '../../../../services/datasource.service';

const _ = lodash.default;

@Component({
    selector: 'ki-advanced-settings',
    templateUrl: './advanced-settings.component.html',
    styleUrls: ['./advanced-settings.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class AdvancedSettingsComponent implements OnInit {

    public _ = _;
    public columns: any = [];
    public columns2: any = [];
    public advancedSettings: any = {
        showAutoIncrement: false,
        primaryKeys: [],
        indexes: [{fieldNames: []}]
    };
    public datasourceUpdate: any;
    public errorMessage = '';

    private datasourceInstanceKey: string;
    private allColumns: any;

    constructor(public dialogRef: MatDialogRef<AdvancedSettingsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private datasourceService: DatasourceService) {
    }

    ngOnInit() {
        this.datasourceUpdate = this.data.datasourceUpdate;
        this.datasourceInstanceKey = this.data.datasourceInstanceKey;

        this.advancedSettings.showAutoIncrement = this.data.showAutoIncrement || false;
        this.advancedSettings.primaryKeys = _.clone(_.filter(this.data.columns, {keyField: true}));
        this.allColumns = _.clone(this.data.columns);
        this.columns = _.clone(_.filter(this.data.columns, col => {
            return !col.keyField;
        }));
        this.columns2 = _.clone(_.filter(this.data.columns, col => {
            return true;
        }));

        if (this.datasourceUpdate.indexes && this.datasourceUpdate.indexes.length) {
            this.advancedSettings.indexes = this.datasourceUpdate.indexes;
            this.advancedSettings.indexes.forEach(indexObj => {
                indexObj.indexCols = _.clone(_.filter(this.data.columns, col => {
                    return indexObj.fieldNames.indexOf(col.name) > -1;
                }));
            });
        }
    }

    public drop(event: CdkDragDrop<string[]>) {
        if (event.previousContainer === event.container) {
            moveItemInArray(event.container.data, event.previousIndex, event.currentIndex);
        } else {
            transferArrayItem(
                event.previousContainer.data,
                event.container.data,
                event.previousIndex,
                event.currentIndex,
            );
        }
    }

    public dropIndex(event: CdkDragDrop<string[]>, data?: any) {
        moveItemInArray(data || event.container.data, event.previousIndex, event.currentIndex);
    }

    public updateIndexes(index: any) {
        index.indexCols.forEach(col => {
            if (index.fieldNames.indexOf(col.name) === -1) {
                index.fieldNames.push(col.name);
            }
        });
        index.fieldNames.forEach((fieldName: string, nameIndex: number) => {
            if (_.findIndex(index.indexCols, {name: fieldName}) === -1) {
                index.fieldNames.splice(nameIndex, 1);
            }
        });
    }

    public async saveChanges() {
        this.errorMessage = '';
        this.datasourceUpdate.indexes = _.filter(this.advancedSettings.indexes, index => {
            return index.fieldNames.length;
        });
        let showAutoIncrement = this.advancedSettings.showAutoIncrement;

        if (this.advancedSettings.primaryKeys.length) {
            showAutoIncrement = false;
            _.remove(this.allColumns, {type: 'id'});

            this.allColumns.map(column => {
                if (_.find(this.advancedSettings.primaryKeys, {name: column.name})) {
                    column.keyField = true;
                }
                return column;
            });

        } else {
            if (!_.find(this.allColumns, {type: 'id'})) {
                this.allColumns.map(column => {
                    column.keyField = false;
                    return column;
                });

                this.allColumns.unshift({
                    title: 'ID',
                    name: 'id',
                    type: 'id'
                });
            }
        }

        this.datasourceUpdate.fields = this.allColumns.map(column => {
            if (column.name === column.previousName) {
                delete column.previousName;
            }
            return column;
        });

        try {
            await this.datasourceService.updateCustomDatasource(this.datasourceInstanceKey, this.datasourceUpdate);
            this.dialogRef.close({datasourceUpdate: this.datasourceUpdate, columns: this.allColumns, showAutoIncrement});
        } catch (e) {
            const error = e.error;
            this.errorMessage = error.message || 'There was a problem updating this datasource. Please check your settings and try again.';
        }
    }

}
