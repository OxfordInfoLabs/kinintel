import {Component, Inject, OnInit, ViewChild} from '@angular/core';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';
import {DatasetService} from '../../../services/dataset.service';
import * as lodash from 'lodash';
import {MatLegacySelect as MatSelect} from '@angular/material/legacy-select';
import { MatLegacyOption as MatOption } from '@angular/material/legacy-core';
import {TaskTimePeriodsComponent} from '../../task-time-periods/task-time-periods.component';
import {NgForm, NgModel} from '@angular/forms';
import {DataProcessorService} from '../../../services/data-processor.service';
const _ = lodash.default;

@Component({
    selector: 'ki-snapshot-profile-dialog',
    templateUrl: './snapshot-profile-dialog.component.html',
    styleUrls: ['./snapshot-profile-dialog.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class SnapshotProfileDialogComponent implements OnInit {

    @ViewChild('selectKeyFields') selectKeyFields: MatSelect;
    @ViewChild('timePeriods') timePeriods: TaskTimePeriodsComponent;
    @ViewChild('timeLapseForm') timeLapseForm: NgForm;
    @ViewChild('selectModel') selectModel: NgModel;

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
    public allSelected = false;

    private datasetInstanceId;

    constructor(public dialogRef: MatDialogRef<SnapshotProfileDialogComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private datasetService: DatasetService,
                private dataProcessorService: DataProcessorService) {
    }

    async ngOnInit() {
        this.columns = this.data.columns || [];

        this.snapshot = this.data.snapshot || {
            type: 'tabulardatasetsnapshot',
            taskTimePeriods: [],
            trigger: 'scheduled',
            config: {
                keyFieldNames: [],
                timeLapsedFields: [],
                createHistory: true,
                createLatest: true,
                indexes: [],
                parameterValues: {}
            }
        };

        if (!this.snapshot.config.indexes) {
            this.snapshot.config.indexes = [];
        }

        if (!this.snapshot.config.parameterValues || Array.isArray(this.snapshot.config.parameterValues)) {
            this.snapshot.config.parameterValues = {};
        }

        this.datasetInstanceId = this.data.datasetInstanceId || null;

        this.snapshot.relatedObjectType = 'DatasetInstance';
        this.snapshot.relatedObjectPrimaryKey = this.datasetInstanceId;

        if (this.data.datasetInstance) {
            const values: any = await this.datasetService.getEvaluatedParameters(this.data.datasetInstance);
            _.forEach(values, parameter => {
                this.snapshot.config.parameterValues[parameter.name] = (this.snapshot.config.parameterValues[parameter.name] || parameter.defaultValue) || '';
            });
        }

        if (!this.columns.length && this.datasetInstanceId) {
            const dataset = await this.datasetService.getDataset(this.datasetInstanceId);
            const res: any = await this.datasetService.evaluateDataset(dataset);
            this.columns = res.columns;
        }
    }

    public removeIndex(index: number) {
        const message = 'Are you sure you would like to remove this Index?';
        if (window.confirm(message)) {
            this.snapshot.config.indexes.splice(index, 1);
        }
    }

    public selectAll(event) {
        event.preventDefault();
        this.allSelected = !this.allSelected;
        this.toggleAllSelection();
    }

    public toggleAllSelection() {
        if (this.allSelected) {
            this.selectKeyFields.options.forEach((item: MatOption) => item.select());
        } else {
            this.selectKeyFields.options.forEach((item: MatOption) => item.deselect());
        }
    }

    public keyFieldClick() {
        let newStatus = true;
        this.selectKeyFields.options.forEach((item: MatOption) => {
            if (!item.selected) {
                newStatus = false;
            }
        });
        this.allSelected = newStatus;
    }

    public updateCreateHistory(value) {
        if (value) {
            this.snapshot.config.createLatest = true;
        }
    }

    public removeTimeLapsedField(index) {
        const message = 'Are you sure you would like to remove this snapshot profile?';
        if (window.confirm(message)) {
            this.snapshot.config.timeLapsedFields.splice(index, 1);
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

        this.snapshot.config.timeLapsedFields.push(cloned);
    }

    public async saveSnapshot() {
        if (this.timePeriods && this.timePeriods.timeForm && this.timePeriods.timeForm.valid) {
            this.timePeriods.addTimePeriod();
        }

        if (this.timeLapseForm && this.timeLapseForm.valid) {
            this.saveTimeLapse();
        }

        setTimeout(async () => {
            if (this.selectModel.valid) {
                await this.dataProcessorService.saveProcessor(this.snapshot);
                this.dialogRef.close(true);
            }
        }, 0);
    }

    protected readonly Object = Object;
}
