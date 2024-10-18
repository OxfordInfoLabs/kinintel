import {Component, Inject, OnInit} from '@angular/core';
import {Subject} from 'rxjs';
import {DatasetService} from '../../../services/dataset.service';
import {DatasourceService} from '../../../services/datasource.service';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';
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
        if (!this.dashboardDatasetInstance.parameterValues || Array.isArray(this.dashboardDatasetInstance.parameterValues)) {
            this.dashboardDatasetInstance.parameterValues = {};
        }
        parameterValues.forEach(param => {
            this.dashboardDatasetInstance.parameterValues[param.name] = param.value;
        });
    }

    public async select(action, stepper?) {
        if (action.datasourceKey) {
            this.dashboardDatasetInstance = {
                datasetInstanceId: null,
                datasourceInstanceKey: action.datasourceKey,
                transformationInstances: [],
                parameterValues: {},
                parameters: []
            };
        } else if (action.datasetId) {
            this.dashboardDatasetInstance = await this.datasetService.getExtendedDataset(action.datasetId);
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
