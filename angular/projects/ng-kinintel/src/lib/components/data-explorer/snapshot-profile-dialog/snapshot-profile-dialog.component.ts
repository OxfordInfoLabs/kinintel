import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {DatasetService} from '../../../services/dataset.service';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'ki-snapshot-profile-dialog',
    templateUrl: './snapshot-profile-dialog.component.html',
    styleUrls: ['./snapshot-profile-dialog.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class SnapshotProfileDialogComponent implements OnInit {

    public snapshot: any;
    public columns: any = [];
    public newTimeLapse: any;
    public _ = _;
    public defaultOffsets = [
        {
            label: '1 Day Ago',
            value: 1
        },
        {
            label: '7 Days Ago',
            value: 7
        },
        {
            label: '30 Days Ago',
            value: 30
        },
        {
            label: '90 Days Ago',
            value: 90
        }
    ];

    private datasetInstanceId;

    constructor(public dialogRef: MatDialogRef<SnapshotProfileDialogComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private datasetService: DatasetService) {
    }

    ngOnInit(): void {
        this.columns = this.data.columns || [];
        this.snapshot = this.data.snapshot || {
            processorType: 'tabulardatasetsnapshot',
            taskTimePeriods: [],
            trigger: 'scheduled',
            processorConfig: {
                keyFieldNames: [],
                timeLapsedFields: [],
                createHistory: true,
                createLatest: true
            }
        };
        this.datasetInstanceId = this.data.datasetInstanceId || null;
    }

    public updateCreateHistory(value) {
        if (value) {
            this.snapshot.processorConfig.createLatest = true;
        }
    }

    public removeTimeLapsedField(index) {
        const message = 'Are you sure you would like to remove this snapshot profile?';
        if (window.confirm(message)) {
            this.snapshot.processorConfig.timeLapsedFields.splice(index, 1);
        }
    }

    public addTimeLapsedField() {
        this.newTimeLapse = {};
    }

    public saveTimeLapse() {
        const cloned = _.clone(this.newTimeLapse);
        this.newTimeLapse = null;
        const customIndex = cloned.dayOffsets.indexOf('CUSTOM');
        if (customIndex > -1) {
            cloned.dayOffsets.splice(customIndex);
            cloned.dayOffsets.push(cloned.customDayOffsets);
            delete cloned.customDayOffsets;
        }

        this.snapshot.processorConfig.timeLapsedFields.push(cloned);
    }

    public saveSnapshot() {
        this.datasetService.saveSnapshotProfile(this.snapshot, this.datasetInstanceId)
            .then(() => {
                this.dialogRef.close(true);
            });
    }

}
