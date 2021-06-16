import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {ProjectService} from './project.service';


@Injectable({
    providedIn: 'root'
})
export class DatasetService {

    constructor(private http: HttpClient,
                private projectService: ProjectService) {
    }

    public getDatasets(filterString = '', limit = '10', offset = '0') {
        return this.http.get('/account/dataset', {
            params: {
                filterString, limit, offset
            }
        });
    }

    public getDataset(id) {
        return this.http.get(`/account/dataset/${id}`).toPromise();
    }

    public saveDataset(datasetInstanceSummary) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.post('/account/dataset', {
            datasetInstanceSummary,
            projectKey
        }).toPromise();
    }

}
