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
import {HttpClient} from '@angular/common/http';
import {MatLegacySnackBar as MatSnackBar} from '@angular/material/legacy-snack-bar';
import {KinintelModuleConfig} from '../../../../../../lib/ng-kinintel.module';

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
    public name: string;
    public reloadURL: string;
    public datasourceUpdate: any;
    public importChoice: string = null;
    public importKey: string = null;
    public backendURL: string = null;

    public readonly separatorKeysCodes = [ENTER, COMMA] as const;

    private namePrefix: string;

    constructor(private dialog: MatDialog,
                public dialogRef: MatDialogRef<ImportWizardComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private datasourceService: DatasourceService,
                private snackBar: MatSnackBar,
                private config: KinintelModuleConfig) {
    }

    async ngOnInit() {
        this.reloadURL = this.data.reloadURL;
        this.datasourceUpdate = this.data.datasourceUpdate;
        this.backendURL = this.config.backendURL;
        this.namePrefix = this.data.namePrefix || '';
    }

    public copied() {
        this.snackBar.open('Copied to Clipboard', null, {
            duration: 2000,
            verticalPosition: 'top'
        });
    }

    public createExample() {
        const example = ['[{'];

        this.columns.forEach((column, index) => {
            if (column.type !== 'id') {
                example.push('<span class="text-secondary">"' + column.name + '":</span> "' + column.type + '"');
                if (index !== this.columns.length - 1) {
                    example.push(', ');
                }
            }
        });
        example.push('}]');
        return example.join('');
    }

    public addColumn(event: MatChipInputEvent) {
        const value = (event.value || '').trim();

        // Add our fruit
        if (value) {
            this.columns.push(
                {
                    title: value,
                    name: _.snakeCase(value),
                    type: 'string'
                }
            );
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

    public removeColumn(column: any) {
        _.remove(this.columns, {name: column.name});
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
        this.datasourceUpdate.title = this.namePrefix + this.name;

        this.datasourceUpdate.fields = [
            {title: 'ID', name: 'id', type: 'id'}
        ];

        this.columns.forEach(column => {
            this.datasourceUpdate.fields.push(column);
        });

        const key = await this.datasourceService.createCustomDatasource(this.datasourceUpdate);

        if (this.importKey) {
            await this.datasourceService.updateCustomDatasource(key, {
                title: this.datasourceUpdate.title,
                importKey: this.importKey,
                fields: this.datasourceUpdate.fields,
                adds: [],
                updates: [],
                deletes: [],
                replaces: []
            });
        }

        window.location.href = this.reloadURL + '/' + key;
    }


}
