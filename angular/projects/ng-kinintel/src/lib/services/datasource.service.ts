import {Injectable} from '@angular/core';
import {HttpClient, HttpHeaders} from '@angular/common/http';
import {KinintelModuleConfig} from '../ng-kinintel.module';
import {ProjectService} from '../services/project.service';

export interface DatasourceUpdate {
    title: string;
    fields: any[];
    adds: any[];
    updates: any[];
    deletes: any[];
}

@Injectable({
    providedIn: 'root'
})
export class DatasourceService {

    constructor(private config: KinintelModuleConfig,
                private http: HttpClient,
                private projectService: ProjectService) {
    }

    public createCustomDatasource(datasourceUpdate: DatasourceUpdate) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.post(this.config.backendURL + '/datasource/custom?projectKey=' + projectKey, datasourceUpdate)
            .toPromise();
    }

    public updateCustomDatasource(datasourceInstanceKey, datasourceUpdate: DatasourceUpdate) {
        return this.http.put(this.config.backendURL + '/datasource/custom/' + datasourceInstanceKey, datasourceUpdate)
            .toPromise();
    }

    public getDatasources(filterString = '', limit = '10', offset = '0') {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.get(this.config.backendURL + '/datasource?projectKey=' + projectKey, {
            params: {
                filterString, limit, offset
            }
        });
    }

    public getDatasource(key) {
        return this.http.get(this.config.backendURL + '/datasource/' + key).toPromise();
    }

    public getEvaluatedParameters(evaluatedDatasource) {
        return this.http.get(this.config.backendURL + '/datasource/parameters/' +
            (evaluatedDatasource.key || evaluatedDatasource.datasourceInstanceKey)).toPromise();
    }

    public evaluateDatasource(evaluatedDatasource) {
        return this.http.post(this.config.backendURL + '/datasource/evaluate', evaluatedDatasource).toPromise();
    }

    public exportData(evaluatedDatasource) {
        return this.http.post(this.config.backendURL + '/datasource/parameters/' +
            (evaluatedDatasource.key || evaluatedDatasource.datasourceInstanceKey),
            {transformationInstances: evaluatedDatasource.transformationInstances}).toPromise();
    }

    public createDocumentDatasource(config) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.post(this.config.backendURL + '/datasource/document?projectKey=' + projectKey,
            config)
            .toPromise();
    }

    public updateDatasourceInstance(key, config) {
        return this.http.put(this.config.backendURL + '/datasource/' + key, config).toPromise();
    }

    public uploadDatasourceDocuments(key, uploadedFiles) {
        const HttpUploadOptions = {
            headers: new HttpHeaders({ 'Content-Type': 'file' })
        };
        return this.http.post(this.config.backendURL + '/datasource/document/upload/' + key,
            uploadedFiles, HttpUploadOptions).toPromise();
    }
}
