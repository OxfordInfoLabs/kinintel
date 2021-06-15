import {Component, Input, OnInit} from '@angular/core';
import {BehaviorSubject, merge} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {MatDialog} from '@angular/material/dialog';
import {DatasetEditorComponent} from '../dataset/dataset-editor/dataset-editor.component';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';

@Component({
    selector: 'ki-datasource',
    templateUrl: './datasource.component.html',
    styleUrls: ['./datasource.component.sass']
})
export class DatasourceComponent implements OnInit {

    @Input() datasourceService: any;
    @Input() datasetService: any;

    public datasources: any = [];
    public searchText = new BehaviorSubject('');
    public limit = new BehaviorSubject(10);
    public offset = new BehaviorSubject(0);

    constructor(private dialog: MatDialog) {
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

    public explore(datasource) {
        const dialogRef = this.dialog.open(DataExplorerComponent, {
            width: '100vw',
            height:  '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            hasBackdrop: false,
            data: {
                datasource,
                showChart: false,
                datasetService: this.datasetService,
                datasourceService: this.datasourceService
            }
        });
        dialogRef.afterClosed().subscribe(res => {

        });
    }

    private getDatasources() {
        return this.datasourceService.getDatasources(
            this.searchText.getValue() || '',
            this.limit.getValue().toString(),
            this.offset.getValue().toString()
        ).pipe(map((sources: any) => {
                return sources;
            })
        );
    }

}
