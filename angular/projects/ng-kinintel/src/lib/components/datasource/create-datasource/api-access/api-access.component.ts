import {Component, Inject, Input, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {DatasourceService} from '../../../../services/datasource.service';
import {MatSnackBar} from '@angular/material/snack-bar';
import {HttpClient} from '@angular/common/http';

@Component({
    selector: 'ki-api-access',
    templateUrl: './api-access.component.html',
    styleUrls: ['./api-access.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class ApiAccessComponent implements OnInit {

    @Input() apiKeys: any;

    public backendURL: string;
    public datasourceUpdate: any;
    public datasourceInstanceKey: any;
    public columns: any;
    public createExample: string;
    public updateExample: string;
    public showExample = false;

    constructor(public dialogRef: MatDialogRef<ApiAccessComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private datasourceService: DatasourceService,
                private snackBar: MatSnackBar,
                private http: HttpClient) {
    }

    async ngOnInit() {
        this.backendURL = this.data.backendURL;
        this.datasourceUpdate = this.data.datasourceUpdate;
        this.datasourceInstanceKey = this.data.datasourceInstanceKey;
        this.columns = this.data.columns;

        this.showExample = !!this.datasourceUpdate.instanceImportKey;

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
        this.createExample = example.join('');

        const update = ['[{'];
        this.columns.forEach((column, index) => {
            update.push('<span class="text-secondary">"' + column.name + '":</span> "' + column.type + '"');
            if (index !== this.columns.length - 1) {
                update.push(', ');
            }
        });
        update.push('}]');
        this.updateExample = update.join('');

        this.apiKeys = await this.http.get('/account/apikey/first/customdatasourceupdate').toPromise();
    }

    public copied() {
        this.snackBar.open('Copied to Clipboard', null, {
            duration: 2000,
            verticalPosition: 'top'
        });
    }

    public async saveApiAccess() {
        await this.datasourceService.updateCustomDatasource(this.datasourceInstanceKey, {
            title: this.datasourceUpdate.title,
            importKey: this.datasourceUpdate.instanceImportKey,
            fields: this.datasourceUpdate.fields,
            adds: [],
            updates: [],
            deletes: [],
            replaces: []
        });

        if (this.showExample) {
            this.dialogRef.close();
        }

        this.showExample = !!this.datasourceUpdate.instanceImportKey;
    }
}
