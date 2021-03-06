import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {BehaviorSubject} from 'rxjs';
import {KinintelModuleConfig} from '../ng-kinintel.module';
import {AuthenticationService} from 'ng-kiniauth';

@Injectable({
    providedIn: 'root'
})
export class ProjectService {

    public activeProject = new BehaviorSubject(null);

    constructor(private config: KinintelModuleConfig,
                private http: HttpClient,
                private authService: AuthenticationService) {

        const activeProject = localStorage.getItem('activeProject');
        if (activeProject) {
            this.setActiveProject(JSON.parse(activeProject));
        }
    }

    public getProjects(filterString = '') {
        return this.http.get(this.config.backendURL + '/project', {
            params: {filterString}
        });
    }

    public getProject(key) {
        return this.http.get(this.config.backendURL + '/project/' + key).toPromise();
    }

    public createProject(name, description) {
        return this.http.post(this.config.backendURL + '/project', {
            name, description
        }).toPromise();
    }

    public removeProject(key) {
        return this.http.delete(this.config.backendURL + '/project/' + key).toPromise();
    }

    public setActiveProject(project) {
        this.activeProject.next(project);
        localStorage.setItem('activeProject', JSON.stringify(project));
    }

    public resetActiveProject() {
        this.activeProject.next(null);
        localStorage.removeItem('activeProject');
    }

    public isActiveProjectAdmin() {
        const session = this.authService.sessionData.getValue();
        if (session && session.privileges) {
            const projectKey = this.activeProject.getValue() ? this.activeProject.getValue().projectKey : null;
            const privileges = session.privileges.PROJECT;

            if (privileges['*']) {
                return true;
            }

            return privileges[projectKey] ? privileges[projectKey].indexOf('*') > -1 : false;
        }
        return false;
    }
}
