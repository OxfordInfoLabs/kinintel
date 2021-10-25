import {Component, Inject, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {DatasetService} from '../../../services/dataset.service';
import {DatasourceService} from '../../../services/datasource.service';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import * as _ from 'lodash';

@Component({
    selector: 'ki-source-selector-dialog',
    templateUrl: './source-selector-dialog.component.html',
    styleUrls: ['./source-selector-dialog.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class SourceSelectorDialogComponent implements OnInit {

    public datasources: any = [];
    public datasets: any = [];
    public sharedDatasets: any = [];
    public searchText = new BehaviorSubject('');
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

        merge(this.searchText)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets(false)
                )
            ).subscribe((datasets: any) => {
            this.datasets = datasets;
        });

        if (this.admin) {
            merge(this.searchText)
                .pipe(
                    debounceTime(300),
                    distinctUntilChanged(),
                    switchMap(() =>
                        this.getDatasources()
                    )
                ).subscribe((sources: any) => {
                this.datasources = sources;
            });
        } else {
            merge(this.searchText)
                .pipe(
                    debounceTime(300),
                    distinctUntilChanged(),
                    switchMap(() =>
                        this.getDatasets(true)
                    )
                ).subscribe((datasets: any) => {
                this.sharedDatasets = datasets;
            });
        }

    }

    public setEvaluatedParameters(parameterValues, evaluate?) {
        this.requiredParameters = parameterValues;
        parameterValues.forEach(param => {
            this.dashboardDatasetInstance.parameterValues[param.name] = param.value;
        });
    }

    public select(item, type, stepper?) {
        if (type === 'datasource') {
            this.datasourceService.getDatasource(item.key).then(datasource => {
                this.dashboardDatasetInstance.datasourceInstanceKey = item.key;
                return this.datasourceService.getEvaluatedParameters({
                    key: item.key
                }).then(requiredParams => {
                    this.createParameterStructure(requiredParams, stepper);
                    return requiredParams;
                });
            });
        } else {
            this.datasetService.getDataset(item.id).then(dataset => {
                this.dashboardDatasetInstance.datasetInstanceId = item.id;
                this.dashboardDatasetInstance.datasourceInstanceKey = dataset.datasourceInstanceKey;
                return this.datasetService.getEvaluatedParameters(item.id).then((requiredParams: any) => {
                    this.createParameterStructure(requiredParams, stepper);
                    return requiredParams;
                });
            });
        }
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
                if (parameter.value) {
                    availableGlobalParamValues.push({
                        name: parameter.name,
                        title: parameter.title,
                        value: parameter.value
                    });
                }
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

    private getDatasets(shared) {
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            '5',
            '0',
            shared ? null : ''
        ).pipe(map((datasets: any) => {
                return datasets;
            })
        );
    }

    private getDatasources() {
        return this.datasourceService.getDatasources(
            this.searchText.getValue() || '',
            '5',
            '0'
        ).pipe(map((sources: any) => {
                return sources;
            })
        );
    }

}
