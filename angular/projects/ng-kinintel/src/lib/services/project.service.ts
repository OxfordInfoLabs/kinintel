import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {BehaviorSubject} from 'rxjs';
import {KinintelModuleConfig} from '../ng-kinintel.module';

@Injectable({
    providedIn: 'root'
})
export class ProjectService {

    public activeProject = new BehaviorSubject(null);

    constructor(private config: KinintelModuleConfig,
                private http: HttpClient) {

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
}
