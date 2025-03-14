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

    public getProjects(filterString = '', limit= 10, offset= 0, accountId?) {
        const params: any =  {filterString, limit: limit.toString(), offset: offset.toString()};
        if (accountId) {
            params.accountId = accountId;
        }
        return this.http.get(this.config.backendURL + '/project', {params});
    }

    public getProject(key) {
        return this.http.get(this.config.backendURL + '/project/' + key).toPromise();
    }

    public createProject(name, description, accountId?) {
        let url = `${this.config.backendURL}/project`;
        if (accountId) {
            url = url + '?accountId=' + accountId;
        }
        return this.http.post(url, {name, description}).toPromise();
    }

    public updateProject(name: string, description: string, key: string) {
        return this.http.put(this.config.backendURL + '/project/' + key, {
            name, description
        }).toPromise();
    }

    public removeProject(key, accountId?) {
        let url = this.config.backendURL + '/project/' + key;
        if (accountId) {
            url = url + '?accountId=' + accountId;
        }
        return this.http.delete(url).toPromise();
    }

    public async updateProjectSettings(projectKey, settings) {
        await this.http.put(this.config.backendURL + '/project/' + projectKey + '/settings', settings)
            .toPromise();
        const project = await this.getProject(projectKey);
        this.setActiveProject(project);
        return project;
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

    public doesActiveProjectHavePrivilege(privilegeKey: string) {
        const session: any = this.authService.sessionData.getValue();
        if (session && session.privileges) {
            const projectKey = this.activeProject.getValue() ? this.activeProject.getValue().projectKey : null;
            const privileges = session.privileges.PROJECT;

            const scope = (privileges['*'] || privileges[projectKey]) || [];

            return scope.indexOf('*') > -1 || scope.indexOf(privilegeKey) > -1;
        }
        return false;
    }

    public canAccountManageProjects() {
        const session: any = this.authService.sessionData.getValue();
        if (session && session.privileges) {
            const privileges = session.privileges.ACCOUNT;

            const scope = (privileges['*'] || privileges[session.account.accountId]) || [];

            return scope.indexOf('*') > -1 || scope.indexOf('projectmanager') > -1;
        }
        return false;
    }

    public setDataItemPagingValue(limit, offset, page, itemName?) {
        const projectKey = this.activeProject.getValue() ? this.activeProject.getValue().projectKey : '';
        itemName = itemName || window.location.pathname + projectKey;
        sessionStorage.setItem(itemName + 'Limit', String(limit));
        sessionStorage.setItem(itemName + 'Offset', String(offset));
        sessionStorage.setItem(itemName + 'Page', String(page));
    }

    public getDataItemPagingValues(itemName?) {
        const projectKey = this.activeProject.getValue() ? this.activeProject.getValue().projectKey : '';
        itemName = itemName || window.location.pathname + projectKey;
        const values: any = {};

        const limitValue = sessionStorage.getItem(itemName + 'Limit');
        if (limitValue) {
            values.limit = Number(limitValue);
        }

        const offsetValue = sessionStorage.getItem(itemName + 'Offset');
        if (offsetValue) {
            values.offset = Number(offsetValue);
        }

        const pageValue = sessionStorage.getItem(itemName + 'Page');
        if (pageValue) {
            values.page = Number(pageValue);
        }

        return values;
    }
}
