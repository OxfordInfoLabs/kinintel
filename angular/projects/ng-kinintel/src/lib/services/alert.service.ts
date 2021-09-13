import {Injectable} from '@angular/core';
import {KinintelModuleConfig} from '../ng-kinintel.module';
import {HttpClient} from '@angular/common/http';
import {ProjectService} from './project.service';

@Injectable({
    providedIn: 'root'
})
export class AlertService {

    constructor(private config: KinintelModuleConfig,
                private http: HttpClient,
                private projectService: ProjectService) {
    }

    public getAlertGroup(id) {
        return this.http.get(`${this.config.backendURL}/alert/group/${id}`)
            .toPromise();
    }

    public getAlertGroups(filterString = '', limit = '10', offset = '0', accountId = '') {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        return this.http.get(this.config.backendURL + '/alert/group', {
            params: {
                filterString, limit, offset, projectKey, accountId
            }
        });
    }

    public saveAlertGroup(alertGroup) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.post(`${this.config.backendURL}/alert/group?projectKey=${projectKey}`, alertGroup)
            .toPromise();
    }

    public deleteAlertGroup(id) {
        return this.http.delete(`${this.config.backendURL}/alert/group/${id}`)
            .toPromise();
    }
}
