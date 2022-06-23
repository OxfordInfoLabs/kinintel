import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from '@angular/router';
import {BehaviorSubject, merge} from 'rxjs';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import {DatasourceService} from '../../../services/datasource.service';
import {MatOptionSelectionChange} from '@angular/material/core';
import {DatasetService} from '../../../services/dataset.service';
import {
    DatasetEditorPopupComponent
} from '../../dataset/dataset-editor/dataset-editor.component';
import {MatDialog} from '@angular/material/dialog';
import * as _ from 'lodash';

@Component({
    selector: 'ki-document-datasource',
    templateUrl: './document-datasource.component.html',
    styleUrls: ['./document-datasource.component.sass']
})
export class DocumentDatasourceComponent implements OnInit {

    public datasourceKey: any;
    public documentConfig: any = {config: {}};
    public searchText = new BehaviorSubject('');
    public datasources: any = [];
    public selectedDatasource: any;
    public datasource: any;
    public evaluatedDatasource: any;
    public showSettings = true;
    public showUpload = false;
    public String = String;
    public files: any = null;
    public _ = _;

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

                this.documentConfig = {
                    title: this.datasource.title,
                    config: this.datasource.config
                };
                this.evaluateDatasource();

                if (this.documentConfig.config.stopWordsDatasourceKey) {
                    this.documentConfig.datasource = await this.datasourceService.getDatasource(this.documentConfig.config.stopWordsDatasourceKey);
                    this.updateDatasource(this.documentConfig.datasource);
                }
            }
        });

        merge(this.searchText)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getDatasources()
                )
            ).subscribe((datasources: any) => {
            this.datasources = datasources;
        });
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
            if (!this.documentConfig.config.minPhraseLength) {
                this.documentConfig.config.minPhraseLength = 1;
            }
            if (!this.documentConfig.config.maxPhraseLength) {
                this.documentConfig.config.maxPhraseLength = 1;
            }
        }
    }

    public humanFileSize(size: any) {
        const i = size === 0 ? 0 : Math.floor( Math.log(size) / Math.log(1024) );
        return Number(( size / Math.pow(1024, i) ).toFixed(2)) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
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

    public uploadFiles() {
        const formData = new FormData();

        for (const file of this.files) {

            formData.append(file.name, file);
        }

        return this.uploadDatasourceDocuments(formData);
    }

    public async updateDatasource(datasource) {
        this.documentConfig.config.stopWordsDatasourceKey = datasource.key;
        this.documentConfig.datasource = datasource;

        const datasetInstanceSummary = {
            datasetInstanceId: null,
            datasourceInstanceKey: datasource.key,
            transformationInstances: [],
            parameterValues: {},
            parameters: [],
            originDataItemTitle: datasource.title
        };

        this.selectedDatasource = await this.datasetService.evaluateDataset(datasetInstanceSummary, '0', '1');
    }

    public async save() {
        if (!this.datasource) {
            this.datasourceKey = await this.datasourceService.createDocumentDatasource(this.documentConfig);

            if (this.files && this.files.length) {
                await this.uploadFiles();
            }

            window.location.href = '/document-datasource/' + this.datasourceKey;

        } else {
            await this.datasourceService.updateDatasourceInstance(this.datasourceKey, this.documentConfig);

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

    private async uploadDatasourceDocuments(formData) {
        await this.datasourceService.uploadDatasourceDocuments(this.datasourceKey, formData);

        this.files = null;
        this.evaluateDatasource();
    }

    private async evaluateDatasource() {
        this.datasource.limit = 1000;
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
    }
}
