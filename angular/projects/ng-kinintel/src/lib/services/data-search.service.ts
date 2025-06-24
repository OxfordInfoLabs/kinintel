import {Inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {ProjectService} from './project.service';
import {KININTEL_MODULE_CONFIG, KinintelModuleConfig} from '../config/kinintel-module-config';

@Injectable({
    providedIn: 'root'
})
export class DataSearchService {

    constructor(@Inject(KININTEL_MODULE_CONFIG) private config: KinintelModuleConfig,
                private http: HttpClient,
                private projectService: ProjectService) {
    }

    public searchForDataItems(filters: {}, limit = 100, offset = 0) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        return this.http.post(this.config.backendURL + '/datasearch', filters, {
            params: {offset, limit, projectKey}
        });
    }

    public getMatchingDataItemTypesForSearchTerm(searchTerm = '') {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        return this.http.get(this.config.backendURL + '/datasearch/types', {
            params: {searchTerm, projectKey}
        }).toPromise();
    }
}
