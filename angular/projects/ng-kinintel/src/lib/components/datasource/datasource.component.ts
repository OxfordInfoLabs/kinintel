import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {MatDialog} from '@angular/material/dialog';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {DatasourceService} from '../../services/datasource.service';
import {ProjectService} from '../../services/project.service';

@Component({
    selector: 'ki-datasource',
    templateUrl: './datasource.component.html',
    styleUrls: ['./datasource.component.sass']
})
export class DatasourceComponent implements OnInit, OnDestroy {

    @Input() admin: boolean;

    public datasources: any = [];
    public searchText = new BehaviorSubject('');
    public limit = 10;
    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public reload = new Subject();
    public isProjectAdmin = false;

    private projectSub: Subscription;

    constructor(private dialog: MatDialog,
                private datasourceService: DatasourceService,
                private projectService: ProjectService) {
    }

    ngOnInit(): void {
        this.isProjectAdmin = this.projectService.isActiveProjectAdmin();

        this.projectSub = this.projectService.activeProject.subscribe(() => {
            this.isProjectAdmin = this.projectService.isActiveProjectAdmin();
        });

        merge(this.searchText, this.reload)
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

    ngOnDestroy() {
        this.projectSub.unsubscribe();
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
        this.offset = (this.limit * this.page) - this.limit;
        this.reload.next(Date.now());
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.reload.next(Date.now());
    }

    public pageSizeChange(value) {
        this.page = 1;
        this.offset = 0;
        this.limit = value;
        this.reload.next(Date.now());
    }

    private getDatasources() {
        return this.datasourceService.getDatasources(
            this.searchText.getValue() || '',
            this.limit.toString(),
            this.offset.toString()
        ).pipe(map((sources: any) => {
                this.endOfResults = sources.length < this.limit;
                return sources;
            })
        );
    }

}
