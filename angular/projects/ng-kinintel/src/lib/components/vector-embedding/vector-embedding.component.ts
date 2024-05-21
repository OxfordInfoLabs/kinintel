import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {BehaviorSubject, interval, merge, Subject, Subscription} from 'rxjs';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import {DataProcessorService} from '../../services/data-processor.service';
import {MatLegacyDialog as MatDialog} from '@angular/material/legacy-dialog';
import {
    EditVectorEmbeddingComponent
} from '../vector-embedding/edit-vector-embedding/edit-vector-embedding.component';
import * as lodash from 'lodash';
import {DataExplorerComponent} from '../data-explorer/data-explorer.component';
import {Router} from '@angular/router';
const _ = lodash.default;

@Component({
    selector: 'ki-vector-embedding',
    templateUrl: './vector-embedding.component.html',
    styleUrls: ['./vector-embedding.component.sass']
})
export class VectorEmbeddingComponent implements OnInit, OnDestroy {

    @Input() admin: boolean;

    public embeddings: any = [];
    public searchText = new BehaviorSubject('');
    public limit = 10;
    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public loading = true;
    public _ = _;

    private reload = new Subject();
    private projectChanges: Subscription;

    constructor(private dataProcessorService: DataProcessorService,
                private dialog: MatDialog,
                private router: Router) {
    }

    ngOnInit() {
        merge(this.searchText, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getEmbeddings()
                )
            ).subscribe((embeddings: any) => {
            this.endOfResults = embeddings.length < this.limit;
            this.embeddings = embeddings;
            this.loading = false;
        });

        this.watchSnapshotChanges();

        this.searchText.subscribe(() => {
            this.page = 1;
            this.offset = 0;
        });
    }

    ngOnDestroy() {
        this.stopSnapshotWatch();
    }

    public async triggerProcessor(processorKey: string) {
        await this.dataProcessorService.triggerProcessor(processorKey);
        this.reload.next(Date.now());
    }

    public viewData(embedding: any) {
        if (embedding.taskStatus !== 'PENDING') {
            const datasetInstanceSummary = {
                datasetInstanceId: null,
                datasourceInstanceKey: embedding.key,
                transformationInstances: [],
                parameterValues: {},
                parameters: []
            };
            this.openDialogEditor(datasetInstanceSummary);
        }
    }

    public editEmbedding(embedding?: any) {
        const dialogRef = this.dialog.open(EditVectorEmbeddingComponent, {
            width: '900px',
            height: '900px',
            data: {
                embedding
            }
        });

        dialogRef.afterClosed().subscribe(() => {
            this.reload.next(Date.now());
        });
    }

    public delete(embeddingKey: string) {
        const message = 'Are you sure you would like to remove this Embedding?';
        if (window.confirm(message)) {
            this.dataProcessorService.removeProcessor(embeddingKey).then(() => {
                this.reload.next(Date.now());
            });
        }
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

    private getEmbeddings() {
        return this.dataProcessorService.filterProcessorsByType(
            'vectorembedding',
            this.searchText.getValue() || '',
            this.limit.toString(),
            this.offset.toString()
        ).pipe(map((embeddings: any) => {
                this.endOfResults = embeddings.length < embeddings.limit;
                return embeddings;
            })
        );
    }

    private openDialogEditor(datasetInstanceSummary) {
        this.router.navigate(['/vector-embedding'], {fragment: _.kebabCase(datasetInstanceSummary.title || datasetInstanceSummary.datasourceInstanceKey)});
        const dialogRef = this.dialog.open(DataExplorerComponent, {
            width: '100vw',
            height: '100vh',
            maxWidth: '100vw',
            maxHeight: '100vh',
            hasBackdrop: false,
            data: {
                datasetInstanceSummary,
                showChart: false,
                admin: this.admin,
                breadcrumb: 'Vector Embedding'
            }
        });
        this.stopSnapshotWatch();
        dialogRef.afterClosed().subscribe(res => {
            if (res && res.breadcrumb) {
                return this.router.navigate([res.breadcrumb], {fragment: null});
            } else {
                this.router.navigate(['/vector-embedding'], {fragment: null});
            }
            this.reload.next(Date.now());
            this.watchSnapshotChanges();
        });
    }

    private watchSnapshotChanges() {
        this.projectChanges = interval(3000)
            .pipe(
                switchMap(() =>
                    this.dataProcessorService.filterProcessorsByType(
                        'vectorembedding',
                        this.searchText.getValue() || '', String(this.limit), String(this.offset), 'NONE').pipe(
                        map(result => {
                            return result;
                        }))
                )
            ).subscribe(embeddings => {
                this.embeddings = embeddings;
            });
    }

    private stopSnapshotWatch() {
        if (this.projectChanges) {
            this.projectChanges.unsubscribe();
        }
    }
}
