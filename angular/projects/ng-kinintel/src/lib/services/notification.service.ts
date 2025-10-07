import {Inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {KININTEL_CONFIG, KinintelModuleConfig} from '../kinintel-config';
import {ProjectService} from './project.service';

@Injectable({
    providedIn: 'root'
})
export class NotificationService {

    constructor(private http: HttpClient,
                @Inject(KININTEL_CONFIG) private config: KinintelModuleConfig,
                private projectService: ProjectService) {
    }

    public getNotificationGroups(limit = '10', offset = '0') {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        return this.http.get(`${this.config.backendURL}/notification/group`, {
            params: {projectKey, limit, offset}
        });
    }

    public getNotificationGroup(id) {
        return this.http.get(`${this.config.backendURL}/notification/group/${id}`).toPromise();
    }

    public removeNotificationGroup(id) {
        return this.http.delete(`${this.config.backendURL}/notification/group/${id}`).toPromise();
    }

    public saveNotificationGroup(notification) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        return this.http.post(`${this.config.backendURL}/notification/group?projectKey=${projectKey}`, notification)
            .toPromise();
    }
}
