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

    public Object = Object;
    public datasources: any = [];
    public datasets: any = [];
    public sharedDatasets: any = [];
    public snapshots: any = [];
    public searchText = new BehaviorSubject('');
    public dashboardDatasetInstance: any;
    public requiredParameters: any = [];
    public updateParams = new Subject();
    public dashboard: any;
    public admin: boolean;
    public tableData: any = {
        datasources: {
            data: [],
            limit: 5,
            offset: 0,
            page: 1,
            endOfResults: false,
            shared: false,
            reload: new Subject(),
            title: 'Datasources',
            type: 'datasource'
        },
        datasets: {
            data: [],
            limit: 5,
            offset: 0,
            page: 1,
            endOfResults: false,
            shared: false,
            reload: new Subject(),
            title: 'My Account Datasets',
            type: 'dataset'
        }
    };

    constructor(public dialogRef: MatDialogRef<SourceSelectorDialogComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private datasetService: DatasetService,
                private datasourceService: DatasourceService) {
    }

    ngOnInit(): void {
        this.dashboardDatasetInstance = this.data.dashboardDatasetInstance;
        this.dashboard = this.data.dashboard;
        this.admin = this.data.admin;

        if (!this.admin) {
            this.tableData.shared = {
                data: [],
                limit: 5,
                offset: 0,
                page: 1,
                endOfResults: false,
                shared: false,
                reload: new Subject(),
                title: 'Datasets Shared With Account',
                type: 'dataset'
            };
        }

        this.tableData.snapshots = {
            data: [],
                limit: 5,
                offset: 0,
                page: 1,
                endOfResults: false,
                shared: false,
                reload: new Subject(),
                title: 'Snapshots',
                type: 'snapshot'
        };

        merge(this.searchText, this.tableData.datasets.reload)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets(false, this.tableData.datasets.limit, this.tableData.datasets.offset)
                )
            ).subscribe((datasets: any) => {
            this.tableData.datasets.endOfResults = datasets.length < this.tableData.datasets.limit;
            this.tableData.datasets.data = datasets;
        });

        merge(this.searchText, this.tableData.snapshots.reload)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getSnapshots(this.tableData.snapshots.limit, this.tableData.snapshots.offset)
                )
            ).subscribe((snapshots: any) => {
            this.tableData.snapshots.endOfResults = snapshots.length < this.tableData.snapshots.limit;
            this.tableData.snapshots.data = snapshots;
        });

        merge(this.searchText, this.tableData.datasources.reload)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasources(this.tableData.datasources.limit, this.tableData.datasources.offset)
                )
            ).subscribe((datasources: any) => {
            this.tableData.datasources.endOfResults = datasources.length < this.tableData.datasources.limit;
            this.tableData.datasources.data = datasources;
        });

        if (!this.admin) {
            merge(this.searchText, this.tableData.shared.reload)
                .pipe(
                    debounceTime(300),
                    distinctUntilChanged(),
                    switchMap(() =>
                        this.getDatasets(true, this.tableData.shared.limit, this.tableData.shared.offset)
                    )
                ).subscribe((shared: any) => {
                this.tableData.shared.endOfResults = shared.length < this.tableData.shared.limit;
                this.tableData.shared.data = shared;
            });
        }
    }

    public increaseOffset(dataItem) {
        dataItem.page = dataItem.page + 1;
        dataItem.offset = (dataItem.limit * dataItem.page) - dataItem.limit;
        dataItem.reload.next(Date.now());
    }

    public decreaseOffset(dataItem) {
        dataItem.page = dataItem.page <= 1 ? 1 : dataItem.page - 1;
        dataItem.offset = (dataItem.limit * dataItem.page) - dataItem.limit;
        dataItem.reload.next(Date.now());
    }

    public pageSizeChange(value, dataItem) {
        dataItem.page = 1;
        dataItem.offset = 0;
        dataItem.limit = value;
        dataItem.reload.next(Date.now());
    }

    public setEvaluatedParameters(parameterValues, evaluate?) {
        this.requiredParameters = parameterValues;
        parameterValues.forEach(param => {
            this.dashboardDatasetInstance.parameterValues[param.name] = param.value;
        });
    }

    public async select(item, type, stepper?) {
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

    private getDatasets(shared, limit, offset) {
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            limit.toString(),
            offset.toString(),
            shared ? null : ''
        ).pipe(map((datasets: any) => {
                return datasets;
            })
        );
    }

    private getDatasources(limit, offset) {
        return this.datasourceService.getDatasources(
            this.searchText.getValue() || '',
            limit.toString(),
            offset.toString()
        ).pipe(map((sources: any) => {
                return sources;
            })
        );
    }

    private getSnapshots(limit, offset) {
        return this.datasetService.listSnapshotProfiles(
            this.searchText.getValue() || '',
            limit.toString(),
            offset.toString()
        ).pipe(map((snapshots: any) => {
                return _.filter(snapshots, snapshot => {
                    return snapshot.taskStatus !== 'PENDING';
                });
            })
        );
    }

}
