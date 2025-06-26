import {Inject, Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {TagService} from './tag.service';
import {ProjectService} from './project.service';
import * as lodash from 'lodash';
import {KININTEL_MODULE_CONFIG, KinintelModuleConfig} from '../config/kinintel-module-config';
const _ = lodash.default;

@Injectable({
    providedIn: 'root'
})
export class FeedService {

    constructor(@Inject(KININTEL_MODULE_CONFIG) private config: KinintelModuleConfig,
                private http: HttpClient,
                private tagService: TagService,
                private projectService: ProjectService) {
    }

    public getFeed(id) {
        return this.http.get(this.config.backendURL + '/feed/' + id).toPromise();
    }

    public listFeeds(filterString = '', limit = '10', offset = '0') {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : null;

        return this.http.get(this.config.backendURL + '/feed', {
            params: _.omitBy({filterString, limit, offset, projectKey}, _.isNil)
        });
    }

    public isFeedURLAvailable(feedUrl, currentItemId = null) {
        return this.http.get(this.config.backendURL + '/feed/available', {
            params: _.omitBy({feedUrl, currentItemId}, _.isNil)
        }).toPromise();
    }

    public saveFeed(feed) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        return this.http.post(this.config.backendURL + '/feed?projectKey=' + projectKey, feed)
            .toPromise();
    }

    public deleteFeed(id) {
        return this.http.delete(this.config.backendURL + '/feed/' + id).toPromise();
    }
}
