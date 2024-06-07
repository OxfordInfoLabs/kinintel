import {Component, Inject, OnInit} from '@angular/core';
import {MatChipEditedEvent, MatChipInputEvent} from '@angular/material/chips';
import {COMMA, ENTER} from '@angular/cdk/keycodes';
import {ImportDataComponent} from '../import-data.component';
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import * as lodash from 'lodash';
import {DatasourceService} from '../../../../../services/datasource.service';

const _ = lodash.default;

@Component({
    selector: 'ki-import-wizard',
    templateUrl: './import-wizard.component.html',
    styleUrls: ['./import-wizard.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class ImportWizardComponent implements OnInit {

    public columns: any = [];
    public addOnBlur = true;
    public readonly separatorKeysCodes = [ENTER, COMMA] as const;
    public name: string;
    public reloadURL: string;
    public datasourceUpdate: any;

    constructor(private dialog: MatDialog,
                public dialogRef: MatDialogRef<ImportWizardComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private datasourceService: DatasourceService) {
    }

    ngOnInit() {
        this.reloadURL = this.data.reloadURL;
        this.datasourceUpdate = this.data.datasourceUpdate;
    }

    public addColumn(event: MatChipInputEvent) {
        const value = (event.value || '').trim();

        // Add our fruit
        if (value) {
            this.columns.push(value);
        }

        // Clear the input value
        // tslint:disable-next-line:no-non-null-assertion
        event.chipInput!.clear();
    }

    public editColumn(column: string, event: MatChipEditedEvent) {
        const value = event.value.trim();

        // Remove fruit if it no longer has a name
        if (!value) {
            this.removeColumn(column);
            return;
        }

        // Edit existing fruit
        const index = this.columns.indexOf(column);
        if (index >= 0) {
            this.columns[index].name = value;
        }
    }

    public removeColumn(column: string) {
        const index = this.columns.indexOf(column);

        if (index >= 0) {
            this.columns.splice(index, 1);
        }
    }

    public importData() {
        const dialogRef = this.dialog.open(ImportDataComponent, {
            width: '800px',
            height: '900px',
            hasBackdrop: false,
            data: {
                columns: this.columns,
                datasourceUpdate: {
                    title: this.name || '',
                    instanceImportKey: '',
                    fields: [],
                    adds: [],
                    updates: [],
                    deletes: []
                },
                rows: [],
                datasourceInstanceKey: null,
                reloadURL: this.reloadURL
            }
        });
    }

    public async createStructure() {
        this.datasourceUpdate.title = this.name;

        this.datasourceUpdate.fields = [
            {title: 'ID', name: 'id', type: 'id'}
        ];

        this.columns.forEach(column => {
            this.datasourceUpdate.fields.push({
                title: column,
                name: _.snakeCase(column),
                type: 'string'
            });
        });

        const key = await this.datasourceService.createCustomDatasource(this.datasourceUpdate);
        window.location.href = this.reloadURL + '/' + key;
    }



}
