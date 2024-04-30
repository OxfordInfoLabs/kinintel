import {Component, Inject, OnInit} from '@angular/core';
import {
    MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA,
    MatLegacyDialogRef as MatDialogRef
} from '@angular/material/legacy-dialog';
import {DataProcessorService} from '../../../services/data-processor.service';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'ki-edit-query-cache',
    templateUrl: './edit-query-cache.component.html',
    styleUrls: ['./edit-query-cache.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class EditQueryCacheComponent implements OnInit {

    public cache: any;
    public columns: any = [];
    public _ = _;

    constructor(public dialogRef: MatDialogRef<EditQueryCacheComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private dataProcessorService: DataProcessorService) {
    }

    ngOnInit() {
        this.columns = this.data.columns || [];

        this.cache = this.data.cache || {
            type: 'querycaching',
            config: {
                sourceQueryId: this.data.datasetInstanceId,
                cacheExpiryDays: '',
                cacheExpiryHours: '',
                primaryKeyColumnNames: [],
            }
        };
    }

    public async saveQueryCache() {
        await this.dataProcessorService.saveProcessor(this.cache, true);
        this.dialogRef.close(true);
    }
}
