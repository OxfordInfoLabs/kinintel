import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {TagService} from './tag.service';
import {ProjectService} from './project.service';
import {KinintelModuleConfig} from '../ng-kinintel.module';

@Injectable({
    providedIn: 'root'
})
export class DashboardService {

    constructor(private config: KinintelModuleConfig,
                private http: HttpClient,
                private tagService: TagService,
                private projectService: ProjectService) {
    }

    public getDashboard(id) {
        return this.http.get(`${this.config.backendURL}/account/dashboard/${id}`).toPromise();
    }

    public getDashboards(filterString = '', limit = '10', offset = '0') {
        const tags = this.tagService.activeTag.getValue() ? this.tagService.activeTag.getValue().key : '';
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        return this.http.get(this.config.backendURL + '/account/dashboard', {
            params: {
                filterString, limit, offset, tags, projectKey
            }
        });
    }
}
