import {Component, Inject, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {FeedService} from '../../../services/feed.service';
import {DatasetService} from '../../../services/dataset.service';
import {BehaviorSubject, merge} from 'rxjs';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import {MatOptionSelectionChange} from '@angular/material/core';

@Component({
    selector: 'ki-feed',
    templateUrl: './feed.component.html',
    styleUrls: ['./feed.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class FeedComponent implements OnInit {

    public feed: any = {};
    public datasets: any = [];
    public feedUrl: string;
    public searchText = new BehaviorSubject('');
    public feedDataset: any;

    constructor(public dialogRef: MatDialogRef<FeedComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private feedService: FeedService,
                private datasetService: DatasetService) {
    }

    async ngOnInit() {
        this.feed = this.data.feed ? Object.assign({}, this.data.feed) : {};
        if (!this.feed.exporterConfiguration || Array.isArray(this.feed.exporterConfiguration)) {
            this.feed.exporterConfiguration = {includeHeaderRow: true, separator: ','};
        }

        if (this.feed.datasetInstanceId) {
            this.feedDataset = await this.datasetService.getDataset(this.feed.datasetInstanceId);
        }

        this.feedUrl = this.data.feedUrl;

        merge(this.searchText)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets()
                )
            ).subscribe((datasets: any) => {
            this.datasets = datasets;
        });
    }

    public displayFn(dataset): string {
        return dataset && dataset.title ? dataset.title : '';
    }

    public async updateFeedDataset(event: MatOptionSelectionChange) {
        const dataset = event.source.value;
        this.feed.datasetInstanceId = dataset.id;
        this.feed.datasetLabel = dataset;

        this.feedDataset = await this.datasetService.getDataset(this.feed.datasetInstanceId);
    }

    public saveFeed() {
        this.feedService.saveFeed(this.feed).then(res => {
            this.dialogRef.close();
        });
    }

    private getDatasets() {
        return this.datasetService.getDatasets(
            this.searchText.getValue() || '',
            '10',
            '0'
        ).pipe(map((datasets: any) => {
                return datasets;
            })
        );
    }
}
