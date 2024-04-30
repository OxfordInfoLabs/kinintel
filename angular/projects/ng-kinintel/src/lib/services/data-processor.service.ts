import {Injectable} from '@angular/core';
import {KinintelModuleConfig} from '../../lib/ng-kinintel.module';
import {HttpClient} from '@angular/common/http';
import {ProjectService} from '../services/project.service';
import * as lodash from 'lodash';
const _ = lodash.default;

@Injectable({
    providedIn: 'root'
})
export class DataProcessorService {

    constructor(private config: KinintelModuleConfig,
                private http: HttpClient,
                private projectService: ProjectService) {
    }


    public filterProcessorsByType(type: string = '', searchTerm = '', limit = '10', offset = '0', tags?) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        return this.http.get(this.config.backendURL + '/dataprocessor/type/' + type, {
            params: _.omitBy({searchTerm, projectKey, limit, offset}, _.isNil)
        });
    }

    public getProcessor(key: string) {
        return this.http.get(this.config.backendURL + '/dataprocessor/' + key)
            .toPromise();
    }

    public filterProcessorsByRelatedItem(type: string = '', relatedType = '', itemId: number) {

        return this.http.get(this.config.backendURL + '/dataprocessor/relatedobject/' + type + '/' + relatedType + '/' + itemId)
            .toPromise();
    }

    public saveProcessor(processorSummary, autoStart = false) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        let url = this.config.backendURL + '/dataprocessor?projectKey=' + projectKey;

        if (autoStart) {
            url += '&autoStart=true';
        }

        return this.http.post(url, processorSummary).toPromise();
    }

    public removeProcessor(processorKey) {
        return this.http.delete(this.config.backendURL + '/dataprocessor',
            {body: JSON.stringify(processorKey)})
            .toPromise();
    }

    public triggerProcessor(processorKey) {
        return this.http.patch(this.config.backendURL + '/dataprocessor/' + processorKey, null)
            .toPromise();
    }


}
