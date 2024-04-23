import {Component, OnInit} from '@angular/core';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import {DataProcessorService} from '../../services/data-processor.service';

@Component({
    selector: 'ki-query-caching',
    templateUrl: './query-caching.component.html',
    styleUrls: ['./query-caching.component.sass']
})
export class QueryCachingComponent implements OnInit {

    public queries: any = [];
    public searchText = new BehaviorSubject('');
    public limit = 10;
    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public loading = true;

    private reload = new Subject();

    constructor(private dataProcessorService: DataProcessorService) {
    }

    ngOnInit() {

        merge(this.searchText, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getQueries()
                )
            ).subscribe((queries: any) => {
            this.endOfResults = queries.length < this.limit;
            this.queries = queries;
            this.loading = false;
        });

        this.searchText.subscribe(() => {
            this.page = 1;
            this.offset = 0;
        });
    }

    private getQueries() {
        return this.dataProcessorService.filterProcessorsByType(
            'querycaching',
            this.searchText.getValue() || '',
            this.limit.toString(),
            this.offset.toString()
        ).pipe(map((queries: any) => {
                return queries;
            })
        );
    }

}
