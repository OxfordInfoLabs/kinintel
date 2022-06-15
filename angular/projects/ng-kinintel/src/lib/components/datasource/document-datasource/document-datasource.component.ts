import {Component, OnInit} from '@angular/core';
import {ActivatedRoute} from '@angular/router';
import {BehaviorSubject, merge} from 'rxjs';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import {DatasourceService} from '../../../services/datasource.service';
import {MatOptionSelectionChange} from '@angular/material/core';
import {DatasetService} from '../../../services/dataset.service';

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
    public showUpload = true;

    constructor(private route: ActivatedRoute,
                private datasourceService: DatasourceService,
                private datasetService: DatasetService) {
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
                this.evaluatedDatasource = await this.datasourceService.evaluateDatasource(this.datasource);
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

    public displayFn(dataset): string {
        return dataset && dataset.title ? dataset.title : '';
    }

    public dragOverHandler(ev) {
        // Prevent default behavior (Prevent file from being opened)
        ev.preventDefault();
    }

    public dropHandler(ev) {
        console.log('File(s) dropped');

        // Prevent default behavior (Prevent file from being opened)
        ev.preventDefault();

        if (ev.dataTransfer.items) {
            // Use DataTransferItemList interface to access the file(s)
            for (let i = 0; i < ev.dataTransfer.items.length; i++) {
                // If dropped items aren't files, reject them
                if (ev.dataTransfer.items[i].kind === 'file') {
                    const file = ev.dataTransfer.items[i].getAsFile();
                    console.log('... file[' + i + '].name = ' + file.name);
                }
            }
        } else {
            // Use DataTransfer interface to access the file(s)
            for (let i = 0; i < ev.dataTransfer.files.length; i++) {
                console.log('... file[' + i + '].name = ' + ev.dataTransfer.files[i].name);
            }
        }
    }

    public fileUpload(event) {
        const files = event.target.files;

        const formData = new FormData();

        for (const file of files) {
            console.log(file);
            formData.append(file.name, file);
        }

        this.uploadFiles(formData);
    }

    public async updateDatasource(event: MatOptionSelectionChange) {
        const datasource = event.source.value;
        this.documentConfig.stopWordsDatasourceKey = datasource.key;
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

    public save() {
        if (!this.datasource) {
            this.datasourceService.createDocumentDatasource(this.documentConfig);
        } else {
            this.datasourceService.updateDatasourceInstance(this.datasourceKey, this.documentConfig);
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

    private uploadFiles(files) {
        this.datasourceService.uploadDatasourceDocuments(this.datasourceKey, files);
    }

}
