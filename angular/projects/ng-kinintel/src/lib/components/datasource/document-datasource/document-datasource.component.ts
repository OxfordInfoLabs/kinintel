import {Component, Input, OnInit} from '@angular/core';
import {ActivatedRoute} from '@angular/router';
import {BehaviorSubject, merge} from 'rxjs';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import {DatasourceService} from '../../../services/datasource.service';
import {DatasetService} from '../../../services/dataset.service';
import {
    DatasetEditorPopupComponent
} from '../../dataset/dataset-editor/dataset-editor.component';
import {MatDialog} from '@angular/material/dialog';
import * as lodash from 'lodash';

const _ = lodash.default;

@Component({
    selector: 'ki-document-datasource',
    templateUrl: './document-datasource.component.html',
    styleUrls: ['./document-datasource.component.sass']
})
export class DocumentDatasourceComponent implements OnInit {

    @Input() showCustomParser: boolean;

    public datasourceKey: any;
    public searchText = new BehaviorSubject('');
    public searchParser = new BehaviorSubject('');
    public datasources: any = [];
    public documentParsers: any = [];
    public selectedDatasource: any;
    public datasource: any = {config: {stopWords: []}};
    public evaluatedDatasource: any;
    public showSettings = true;
    public showUpload = false;
    public String = String;
    public files: any = null;
    public _ = _;
    public builtInStopWords = {
        builtIn: false
    };
    public uploadResults: any;
    public failedResults: any = [];
    public uploading = false;
    public page = 1;
    public endOfResults = false;
    public limit = 25;

    private offset = 0;

    constructor(private route: ActivatedRoute,
                private datasourceService: DatasourceService,
                private datasetService: DatasetService,
                private dialog: MatDialog) {
    }

    async ngOnInit(): Promise<any> {
        this.route.params.subscribe(async param => {
            this.datasourceKey = param.key;
            if (this.datasourceKey) {
                this.showSettings = false;
                this.datasource = await this.datasourceService.getDatasource(this.datasourceKey);

                this.evaluateDatasource();

                if (this.datasource.config.stopWords && this.datasource.config.stopWords.length) {
                    for (const stopWord of this.datasource.config.stopWords) {
                        if (stopWord.custom) {
                            stopWord.datasource = await this.datasourceService.getDatasource(stopWord.datasourceKey);
                            this.updateDatasource(stopWord, stopWord.datasource);
                        }
                        if (stopWord.builtIn) {
                            this.builtInStopWords = stopWord;
                        }
                    }
                }
            }
        });

        this.searchText
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasources()
                )
            ).subscribe((datasources: any) => {
            this.datasources = datasources;
        });

        this.searchParser
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDocumentParsers()
                )
            ).subscribe((parsers: any) => {
            this.documentParsers = parsers;
        });
    }

    public increaseOffset() {
        this.page = this.page + 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.evaluateDatasource();
    }

    public decreaseOffset() {
        this.page = this.page <= 1 ? 1 : this.page - 1;
        this.offset = (this.limit * this.page) - this.limit;
        this.evaluateDatasource();
    }

    public pageSizeChange(value) {
        this.limit = value;
        this.evaluateDatasource(true);
    }

    public viewFullItemData(data, columnName) {
        this.dialog.open(DatasetEditorPopupComponent, {
            width: '800px',
            height: '400px',
            data: {
                fullData: data,
                columnName
            }
        });
    }

    public indexDocumentChange(change) {
        if (change) {
            if (!this.datasource.config.minPhraseLength) {
                this.datasource.config.minPhraseLength = 1;
            }
            if (!this.datasource.config.maxPhraseLength) {
                this.datasource.config.maxPhraseLength = 1;
            }
        }
    }

    public updateBuiltInStopWordConfig(value) {
        _.remove(this.datasource.config.stopWords, stopWord => {
            return stopWord.builtIn !== undefined;
        });
        if (!this.datasource.config.stopWords) {
            this.datasource.config.stopWords = [];
        }
        this.datasource.config.stopWords.push(this.builtInStopWords);
    }

    public removeCustomStopWord(index) {
        this.datasource.config.stopWords.splice(index, 1);
    }

    public humanFileSize(size: any) {
        const i = size === 0 ? 0 : Math.floor(Math.log(size) / Math.log(1024));
        return Number((size / Math.pow(1024, i)).toFixed(2)) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
    }

    public displayFn(dataset): string {
        return dataset && dataset.title ? dataset.title : '';
    }

    public dragOverHandler(ev) {
        // Prevent default behavior (Prevent file from being opened)
        ev.preventDefault();
    }

    public dropHandler(ev) {
        // Prevent default behavior (Prevent file from being opened)
        ev.preventDefault();

        this.files = [];

        if (ev.dataTransfer.items) {
            // Use DataTransferItemList interface to access the file(s)
            for (let i = 0; i < ev.dataTransfer.items.length; i++) {
                // If dropped items aren't files, reject them
                if (ev.dataTransfer.items[i].kind === 'file') {
                    const file = ev.dataTransfer.items[i].getAsFile();
                    this.files.push(file);
                }
            }
        } else {
            // Use DataTransfer interface to access the file(s)
            for (let i = 0; i < ev.dataTransfer.files.length; i++) {
                const file = ev.dataTransfer.files[i];
                this.files.push(file);
            }
        }
    }

    public fileUpload(event) {
        this.files = Array.from(event.target.files);
    }

    public removeFile(index) {
        this.files.splice(index, 1);
    }

    public downloadDoc(url) {
        window.open(url);
    }

    public async uploadFiles() {
        const formData = new FormData();

        for (const file of this.files) {

            formData.append(file.name, file);
        }

        await this.uploadDatasourceDocuments(formData);
        this.evaluateDatasource();
    }

    public async updateDatasource(stopWord, datasource) {
        stopWord.datasourceKey = datasource.key;
        stopWord.datasource = datasource;

        const datasetInstanceSummary = {
            datasetInstanceId: null,
            datasourceInstanceKey: datasource.key,
            transformationInstances: [],
            parameterValues: {},
            parameters: [],
            originDataItemTitle: datasource.title
        };

        stopWord.selectedDatasource = await this.datasetService.evaluateDataset(datasetInstanceSummary, '0', '1');
    }

    public updateCustomParser(value) {
        this.datasource.config.customDocumentParser = value;
    }

    public async save() {
        if (!this.datasourceKey) {
            this.datasourceKey = await this.datasourceService.createDocumentDatasource(this.datasource);

            if (this.files && this.files.length) {
                await this.uploadFiles();
                this.evaluateDatasource();
            }

            window.location.href = window.location.href + '/' + this.datasourceKey;

        } else {
            await this.datasourceService.updateDatasourceInstance(this.datasourceKey, this.datasource);

            this.evaluateDatasource();
        }
    }

    private getDatasources() {
        return this.datasourceService.getDatasources(
            this.searchText.getValue() || '',
            '10',
            '0'
        ).pipe(map((datasources: any) => {
                return datasources;
            })
        );
    }

    private getDocumentParsers() {
        return this.datasourceService.getDatasources(
            this.searchText.getValue() || '',
            '10',
            '0'
        ).pipe(map((datasources: any) => {
                return [
                    {
                        title: 'Plenipot Bucharest 22',
                        value: 'plenipotBucharest22'
                    },
                    {
                        title: 'ITU Study Group',
                        value: 'itustudygroup'
                    }
                ];
            })
        );
    }

    private async uploadDatasourceDocuments(formData) {
        this.uploading = true;
        const trackingKey = Date.now() + (Math.random() + 1).toString(36).substr(2, 5);

        this.datasourceService.uploadDatasourceDocuments(this.datasourceKey, formData, trackingKey).catch(err => {
            // Ignore - we have set off a long-running task which we are tracking below
        });

        this.files = null;

        return new Promise((resolve, reject) => {
            setTimeout(() => {
                const resultSub = this.datasourceService.getDataTrackingResults(trackingKey).subscribe((res: any) => {
                    this.uploadResults = res;
                    if (res && res.status === 'COMPLETED') {
                        this.uploading = false;
                        resultSub.unsubscribe();
                        this.failedResults = res.progressData.failed || [];
                        resolve(true);
                        setTimeout(() => {
                            this.uploadResults = null;
                            this.failedResults = [];
                        }, 5000);
                    }
                });
            }, 100);
        });
    }

    private async evaluateDatasource(resetPager = false) {
        if (resetPager) {
            this.offset = 0;
            this.page = 1;
        }

        this.datasource.limit = this.limit;
        this.datasource.offset = this.offset;
        this.evaluatedDatasource = await this.datasourceService.evaluateDatasource(this.datasource);
        this.evaluatedDatasource.allData.map(data => {
            if (data.file_size) {
                data.file_size = this.humanFileSize(data.file_size);
            }
            if (data.file_type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                data.file_type = 'application/docx';
            }
            return data;
        });

        this.endOfResults = this.evaluatedDatasource.allData.length < this.limit;
    }
}
