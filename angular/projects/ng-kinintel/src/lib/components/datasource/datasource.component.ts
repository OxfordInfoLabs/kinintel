import {Component, Input, OnInit} from '@angular/core';
import {BehaviorSubject, merge} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {MatDialog} from '@angular/material/dialog';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {DatasourceService} from '../../services/datasource.service';

@Component({
    selector: 'ki-datasource',
    templateUrl: './datasource.component.html',
    styleUrls: ['./datasource.component.sass']
})
export class DatasourceComponent implements OnInit {

    @Input() admin: boolean;

    public datasources: any = [];
    public searchText = new BehaviorSubject('');
    public limit = new BehaviorSubject(10);
    public offset = new BehaviorSubject(0);
    public page = 1;
    public endOfResults = false;

    constructor(private dialog: MatDialog,
                private datasourceService: DatasourceService) {
    }

    ngOnInit(): void {
        merge(this.searchText, this.limit, this.offset)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasources()
                )
            ).subscribe((sources: any) => {
            this.datasources = sources;
        });
    }

    public createDatasource() {

    }

    public explore(datasource) {
        const datasetInstanceSummary = {
            datasetInstanceId: null,
            datasourceInstanceKey: datasource.key,
            transformationInstances: [],
            parameterValues: {},
            parameters: [],
            originDataItemTitle: datasource.title
        };
        const dialogRef = this.dialog.open(DataExplorerComponent, {
            width: '100vw',
            height: '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            hasBackdrop: false,
            data: {
                datasetInstanceSummary,
                showChart: false,
                admin: this.admin
            }
        });
        dialogRef.afterClosed().subscribe(res => {

        });
    }

    public increaseOffset() {
        this.page = this.page + 1;
        this.offset.next((this.limit.getValue() * this.page) - this.limit.getValue());
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset.next((this.limit.getValue() * this.page) - this.limit.getValue());
    }

    public pageSizeChange(value) {
        this.limit.next(value);
    }

    private getDatasources() {
        return this.datasourceService.getDatasources(
            this.searchText.getValue() || '',
            this.limit.getValue().toString(),
            this.offset.getValue().toString()
        ).pipe(map((sources: any) => {
                this.endOfResults = sources.length < this.limit.getValue();
                return sources;
            })
        );
    }

}
