import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {TagService} from './tag.service';
import {ProjectService} from './project.service';
import {KinintelModuleConfig} from '../ng-kinintel.module';
import {BehaviorSubject} from 'rxjs';

@Injectable({
    providedIn: 'root'
})
export class DashboardService {

    public dashboardItems = new BehaviorSubject({});

    constructor(private config: KinintelModuleConfig,
                private http: HttpClient,
                private tagService: TagService,
                private projectService: ProjectService) {
    }

    public getDashboard(id) {
        return this.http.get(`${this.config.backendURL}/dashboard/${id}`).toPromise();
    }

    public getDashboards(filterString = '', limit = '10', offset = '0', accountId = '', categories = [], tags?) {
        tags = tags || (this.tagService.activeTag.getValue() ? this.tagService.activeTag.getValue().key : '');
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        const suffix = this.config.backendURL.indexOf('/account') && accountId === null ? '/shared/all' : '';
        return this.http.get(this.config.backendURL + '/dashboard' + suffix, {
            params: {
                filterString, limit, offset, tags, projectKey, accountId, categories: categories.join(',')
            }
        });
    }

    public saveDashboard(dashboardSummary, accountId = null) {
        const tags = this.tagService.activeTag.getValue() || null;
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        if (tags) {
            dashboardSummary.tags = [tags];
        }

        const url = this.config.backendURL + '/dashboard?projectKey=' + projectKey + '&accountId=' + accountId;

        return this.http.post(url, dashboardSummary).toPromise();
    }

    public updateMetadata(dashboardSearchResult) {
        return this.http.patch(this.config.backendURL + '/dashboard', dashboardSearchResult).toPromise();
    }

    public getDashboardCategories(shared?) {
        const tag = this.tagService.activeTag.getValue() || null;
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        let tags = '';

        if (tag) {
            tags = tag.key;
        }
        const url = shared ? '/dashboard/shared/inUseCategories' : '/dashboard/inUseCategories';
        return this.http.get(this.config.backendURL + url, {
            params: {projectKey, tags}
        }).toPromise();
    }

    public removeDashboard(id) {
        return this.http.delete(this.config.backendURL + '/dashboard/' + id).toPromise();
    }
}
