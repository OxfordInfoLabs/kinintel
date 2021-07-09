import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {TagService} from './tag.service';
import {ProjectService} from './project.service';
import {KinintelModuleConfig} from '../ng-kinintel.module';


@Injectable({
    providedIn: 'root'
})
export class DatasetService {

    constructor(private config: KinintelModuleConfig,
                private http: HttpClient,
                private tagService: TagService,
                private projectService: ProjectService) {
    }

    public getDatasets(filterString = '', limit = '10', offset = '0') {
        const tags = this.tagService.activeTag.getValue() ? this.tagService.activeTag.getValue().key : '';
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.get(this.config.backendURL + '/account/dataset', {
            params: {
                filterString, limit, offset, tags, projectKey
            }
        });
    }

    public getDataset(id) {
        return this.http.get(`${this.config.backendURL}/account/dataset/${id}`).toPromise();
    }

    public saveDataset(datasetInstanceSummary) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        const activeTag = this.tagService.activeTag.getValue() || null;
        if (activeTag) {
            datasetInstanceSummary.tags = [activeTag];
        }
        return this.http.post(this.config.backendURL + '/account/dataset/?projectKey=' + projectKey, datasetInstanceSummary).toPromise();
    }

    public removeDataset(id) {
        return this.http.delete(this.config.backendURL + '/account/dataset/' + id).toPromise();
    }

    public getEvaluatedParameters(datasetId) {
        return this.http.get(this.config.backendURL + '/account/dataset/parameters/' + datasetId)
            .toPromise();
    }
}
