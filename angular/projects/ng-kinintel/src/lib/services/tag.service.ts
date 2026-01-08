import {Inject, Injectable} from '@angular/core';
import {BehaviorSubject} from 'rxjs';
import { HttpClient } from '@angular/common/http';
import {ProjectService} from './project.service';
import {KININTEL_CONFIG, KinintelModuleConfig} from '../kinintel-config';

@Injectable({
    providedIn: 'root'
})
export class TagService {

    public activeTag = new BehaviorSubject(null);

    constructor(@Inject(KININTEL_CONFIG) private config: KinintelModuleConfig,
                private http: HttpClient,
                private projectService: ProjectService) {

        const activeTag = localStorage.getItem('activeTag');
        if (activeTag) {
            this.setActiveTag(JSON.parse(activeTag));
        }
    }

    public getTags(filterString = '') {
        const activeProject = this.projectService.activeProject.getValue();
        return this.http.get(this.config.backendURL + '/metadata/tag', {
            params: {
                filterString,
                projectKey: activeProject ? activeProject.projectKey : ''
            }
        });
    }

    public saveTag(tag, description) {
        const activeProject = this.projectService.activeProject.getValue();
        const key = activeProject ? activeProject.projectKey : '';
        return this.http.post(this.config.backendURL + '/metadata/tag?projectKey=' + key, {
            tag, description
        }).toPromise();
    }

    public removeTag(key) {
        const activeProject = this.projectService.activeProject.getValue();
        const projectKey = activeProject ? activeProject.projectKey : '';
        return this.http.delete(this.config.backendURL + '/metadata/tag?projectKey=' + key, {
            params: {
                key, projectKey
            }
        }).toPromise();
    }

    public setActiveTag(tag) {
        localStorage.setItem('activeTag', JSON.stringify(tag));
        this.activeTag.next(tag);
    }

    public resetActiveTag() {
        localStorage.removeItem('activeTag');
        this.activeTag.next(null);
    }
}
