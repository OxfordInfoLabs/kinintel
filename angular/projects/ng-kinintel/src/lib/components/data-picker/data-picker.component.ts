import {Component, EventEmitter, Input, OnInit, Output} from '@angular/core';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import * as lodash from 'lodash';
import {DataSearchService} from '../../services/data-search.service';
import {KinintelModuleConfig} from '../../ng-kinintel.module';

const _ = lodash.default;

@Component({
    selector: 'ki-data-picker',
    templateUrl: './data-picker.component.html',
    styleUrls: ['./data-picker.component.sass']
})
export class DataPickerComponent implements OnInit {

    @Input() admin: boolean;
    @Input() typeMapping: any;

    @Output() selected = new EventEmitter();

    public Object = Object;
    public _ = _;
    public searchText = new BehaviorSubject('');
    public data: any = [];
    public page = 1;
    public limit = 100;
    public offset = 0;
    public endOfResults = false;
    public reload = new Subject();
    public types: any = [];
    public selectedType = new BehaviorSubject(null);


    constructor(private dataSearchService: DataSearchService,
                private config: KinintelModuleConfig) {
    }

    ngOnInit(): void {
        this.typeMapping = this.config.dataSearchTypeMapping ? _.orderBy(this.config.dataSearchTypeMapping, 'order') : null;

        merge(this.searchText, this.reload, this.selectedType)
            .pipe(
                debounceTime(300),
                distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasets()
                )
            ).subscribe(async (datasets: any) => {
            this.types = await this.dataSearchService.getMatchingDataItemTypesForSearchTerm(this.searchText.getValue() || '');
            this.endOfResults = datasets.length < this.limit;
            this.data = datasets;
        });
    }

    public select(action, itemTitle = '') {
        action.itemTitle = itemTitle;
        this.selected.next(action);
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

    private getDatasets() {
        return this.dataSearchService.searchForDataItems(
            {search: this.searchText.getValue() || '', type: this.selectedType.getValue() || null},
            this.limit,
            this.offset
        ).pipe(map((datasets: any) => {
                return datasets;
            })
        );
    }

}
