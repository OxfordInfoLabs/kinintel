import {Component, Inject, OnInit} from '@angular/core';
import {Subject} from 'rxjs';
import {DatasetService} from '../../../services/dataset.service';
import {DatasourceService} from '../../../services/datasource.service';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'ki-source-selector-dialog',
    templateUrl: './source-selector-dialog.component.html',
    styleUrls: ['./source-selector-dialog.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class SourceSelectorDialogComponent implements OnInit {

    public Object = Object;
    public datasources: any = [];
    public datasets: any = [];
    public sharedDatasets: any = [];
    public snapshots: any = [];
    public dashboardDatasetInstance: any;
    public requiredParameters: any = [];
    public updateParams = new Subject();
    public dashboard: any;
    public admin: boolean;

    constructor(public dialogRef: MatDialogRef<SourceSelectorDialogComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private datasetService: DatasetService,
                private datasourceService: DatasourceService) {
    }

    ngOnInit(): void {
        this.dashboardDatasetInstance = this.data.dashboardDatasetInstance;
        this.dashboard = this.data.dashboard;
        this.admin = this.data.admin;
    }

    public setEvaluatedParameters(parameterValues, evaluate?) {
        this.requiredParameters = parameterValues;
        parameterValues.forEach(param => {
            this.dashboardDatasetInstance.parameterValues[param.name] = param.value;
        });
    }

    public async select(event, stepper?) {
        const item = event.item;
        const type = event.type;

        if (type === 'datasource') {
            const datasource: any = await this.datasourceService.getDatasource(item.key);
            this.dashboardDatasetInstance = {
                datasetInstanceId: null,
                datasourceInstanceKey: datasource.key,
                transformationInstances: [],
                parameterValues: {},
                parameters: []
            };
        } else if (type === 'snapshot') {
            this.dashboardDatasetInstance = {
                datasetInstanceId: null,
                datasourceInstanceKey: item.snapshotProfileDatasourceInstanceKey,
                transformationInstances: [],
                parameterValues: {},
                parameters: []
            };
        } else {
            this.dashboardDatasetInstance = await this.datasetService.getExtendedDataset(item.id);
        }

        if (this.data.dashboardItemInstanceKey) {
            this.dashboardDatasetInstance.instanceKey = this.data.dashboardItemInstanceKey;
        }

        return this.datasetService.getEvaluatedParameters(this.dashboardDatasetInstance).then((requiredParams: any) => {
            this.createParameterStructure(requiredParams, stepper);
            return requiredParams;
        });
    }

    public setParameters() {
        this.updateParams.next(Date.now());
        this.dialogRef.close(this.dashboardDatasetInstance);
    }

    private createParameterStructure(requiredParameters, stepper?) {
        this.requiredParameters = requiredParameters;
        const availableGlobalParamValues = [];
        if (this.dashboard.layoutSettings.parameters) {
            _.forEach(this.dashboard.layoutSettings.parameters, parameter => {
                availableGlobalParamValues.push({
                    name: parameter.name,
                    title: parameter.title,
                    value: parameter.value
                });
            });
        }

        if (availableGlobalParamValues.length) {
            this.requiredParameters.map(requiredParameter => {
                requiredParameter._prevType = requiredParameter.type;
                requiredParameter.type = 'global';
                requiredParameter.values = availableGlobalParamValues;
                requiredParameter.selectedValue = 'global';
                requiredParameter.value = '';
                return requiredParameter;
            });
        }

        if (this.requiredParameters.length) {
            setTimeout(() => {
                stepper.next();
            }, 0);
        } else {
            this.dialogRef.close(this.dashboardDatasetInstance);
        }
    }

}
