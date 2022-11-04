import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {TagService} from './tag.service';
import {ProjectService} from './project.service';
import {KinintelModuleConfig} from '../ng-kinintel.module';
import * as lodash from 'lodash';
const _ = lodash.default;
import {map, switchMap} from 'rxjs/operators';
import {interval} from 'rxjs';

@Injectable({
    providedIn: 'root'
})
export class DatasetService {

    constructor(private config: KinintelModuleConfig,
                private http: HttpClient,
                private tagService: TagService,
                private projectService: ProjectService) {
    }

    public getDatasets(filterString = '', limit = '10', offset = '0', accountId = '', type = '', categories = [], tags?) {
        tags = tags || (this.tagService.activeTag.getValue() ? this.tagService.activeTag.getValue().key : 'NONE');
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        const suffix = this.config.backendURL.indexOf('/account') && accountId === null ? '/shared/all' : '';
        return this.http.get(this.config.backendURL + '/dataset' + suffix, {
            params: {
                filterString, limit, offset, tags, projectKey, accountId, type, categories: categories.join(',')
            }
        });
    }

    public getDataset(id) {
        return this.http.get(`${this.config.backendURL}/dataset/${id}`).toPromise()
            .then((dataset: any) => {
                if (dataset.readOnly) {
                    dataset.id = dataset.title = null;
                    return dataset;
                }
                return dataset;
            });
    }

    public getExtendedDataset(id) {
        return this.http.get(`${this.config.backendURL}/dataset/extended/${id}`).toPromise();
    }

    public saveDataset(datasetInstanceSummary, accountId = null) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        const activeTag = this.tagService.activeTag.getValue() || null;
        if (activeTag) {
            datasetInstanceSummary.tags = [activeTag];
        }
        return this.http.post(this.config.backendURL + '/dataset/?projectKey=' + projectKey + '&accountId=' + accountId,
            datasetInstanceSummary).toPromise();
    }

    public removeDataset(id) {
        return this.http.delete(this.config.backendURL + '/dataset/' + id).toPromise();
    }

    public getEvaluatedParameters(datasetInstanceSummary) {
        return this.http.post(this.config.backendURL + '/dataset/parameters', datasetInstanceSummary)
            .toPromise().catch(err => []);
    }

    public evaluateDataset(datasetInstanceSummary, offset = '0', limit = '25') {
        return this.http.post(this.config.backendURL + `/dataset/evaluate?limit=${limit}&offset=${offset}`,
            datasetInstanceSummary).toPromise();
    }

    public evaluateDatasetWithTracking(datasetInstanceSummary, offset = '0', limit = '25', trackingKey = '') {
        return this.http.post(this.config.backendURL + `/dataset/evaluate?limit=${limit}&offset=${offset}&trackingKey=${trackingKey}`,
            datasetInstanceSummary);
    }

    public getDataTrackingResults(trackingKey) {
        return interval(2000)
            .pipe(
                switchMap(() =>
                    this.http.get(this.config.backendURL + `/dataset/results/${trackingKey}`).pipe(
                        map(result => {
                            return result;
                        }))
                )
            );
    }

    public exportDataset(exportDataset) {
        return this.http.post(this.config.backendURL + `/dataset/export`, exportDataset,
            {responseType: 'blob'})
            .toPromise();
    }

    public listSnapshotProfiles(filterString = '', limit = '10', offset = '0', tags?) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        const activeTag = tags || (this.tagService.activeTag.getValue() ? this.tagService.activeTag.getValue().key : '');

        return this.http.get(this.config.backendURL + '/dataset/snapshotprofile', {
            params: _.omitBy({filterString, limit, offset, tags: activeTag, projectKey}, _.isNil)
        });
    }

    public getSnapshotProfilesForDataset(datasetInstanceId) {
        return this.http.get(this.config.backendURL + '/dataset/snapshotprofile/' + datasetInstanceId)
            .toPromise();
    }

    public saveSnapshotProfile(snapshotProfileSummary, datasetInstanceId) {
        return this.http.post(this.config.backendURL + '/dataset/snapshotprofile/' + datasetInstanceId,
            snapshotProfileSummary)
            .toPromise();
    }

    public removeSnapshotProfile(snapshotProfileId, datasetInstanceId) {
        return this.http.delete(this.config.backendURL + '/dataset/snapshotprofile/' + datasetInstanceId,
            {params: {snapshotProfileId}})
            .toPromise();
    }

    public triggerSnapshot(snapshotProfileId, datasetInstanceId) {
        return this.http.patch(this.config.backendURL + '/dataset/snapshotprofile/' + datasetInstanceId,
            snapshotProfileId)
            .toPromise();
    }

    public updateMetadata(dashboardSearchResult) {
        return this.http.patch(this.config.backendURL + '/dataset', dashboardSearchResult).toPromise();
    }

    public getDatasetCategories(shared?) {
        const tag = this.tagService.activeTag.getValue() || null;
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        let tags = '';

        if (tag) {
            tags = tag.key;
        }
        const url = shared ? '/dataset/shared/inUseCategories' : '/dataset/inUseCategories';
        return this.http.get(this.config.backendURL + url, {
            params: {projectKey, tags}
        }).toPromise();
    }

    public getWhiteListedSQLFunctions() {
        return this.http.get(this.config.backendURL + '/dataset/whitelistedsqlfunctions')
            .toPromise();
    }
}
