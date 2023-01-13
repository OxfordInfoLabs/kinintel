import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {DatasetService} from '../../services/dataset.service';
import {DatasourceService} from '../../services/datasource.service';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'ki-data-picker',
    templateUrl: './data-picker.component.html',
    styleUrls: ['./data-picker.component.sass']
})
export class DataPickerComponent implements OnInit {

    @Input() admin: boolean;

    @Output() selected = new EventEmitter();

    public Object = Object;
    public searchText = new BehaviorSubject('');
    public tableData: any = {
        datasources: {
            data: [],
            limit: 5,
            offset: 0,
            page: 1,
            endOfResults: false,
            shared: false,
            reload: new Subject(),
            title: 'My Data',
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
            title: 'Stored Queries',
            type: 'dataset'
        }
    };

    constructor(private datasetService: DatasetService,
                private datasourceService: DatasourceService) {
    }

    ngOnInit(): void {
        if (!this.admin) {
            this.tableData.shared = {
                data: [],
                limit: 5,
                offset: 0,
                page: 1,
                endOfResults: false,
                shared: false,
                reload: new Subject(),
                title: 'Data Feeds',
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

    public select(item, type) {
        this.selected.next({item, type});
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
