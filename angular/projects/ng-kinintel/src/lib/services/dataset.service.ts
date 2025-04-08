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


    /**
     * Get account shared datasets
     *
     * @param filterString
     * @param limit
     * @param offset
     */
    public getAccountSharedDatasets(filterString = '', limit = 10, offset = 0) {

        return this.http.get(this.config.backendURL + '/dataset/shared', {
            params: {
                filterString, limit, offset
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

    public saveDataset(datasetInstanceSummary, accountId = null): Promise<number> {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : null;
        const activeTag = this.tagService.activeTag.getValue() || null;
        if (activeTag) {
            datasetInstanceSummary.tags = [activeTag];
        }
        return this.http.post(this.config.backendURL + '/dataset/' + (projectKey ? 'projectKey=' + projectKey + '&' : '?') + 'accountId=' + accountId,
            datasetInstanceSummary).toPromise().then((id: number) => {
            return id;
        });
    }

    public removeDataset(id) {
        return this.http.delete(this.config.backendURL + '/dataset/' + id).toPromise();
    }

    public getEvaluatedParameters(datasetInstanceSummary) {
        return this.http.post(this.config.backendURL + '/dataset/parameters', datasetInstanceSummary)
            .toPromise().catch(err => []);
    }

    public evaluateDataset(datasetInstanceSummary, offset = '0', limit = '25') {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.post(this.config.backendURL + `/dataset/evaluate?limit=${limit}&offset=${offset}&projectKey=${projectKey}`,
            datasetInstanceSummary).toPromise();
    }

    public evaluateDatasetWithTracking(datasetInstanceSummary, offset = '0', limit = '25', trackingKey = '') {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        return this.http.post(this.config.backendURL + `/dataset/evaluate?limit=${limit}&offset=${offset}&trackingKey=${trackingKey}&projectKey=${projectKey}`,
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


    /**
     * Set shared access for a dataset instance for the logged in account
     *
     * @param datasetInstanceId
     * @param shared
     */
    public async setSharedAccessForDatasetInstanceForLoggedInAccount(datasetInstanceId, shared) {
        return this.http.post(this.config.backendURL + `/dataset/shareWithCurrentAccount/${datasetInstanceId}/${shared}`, {}).toPromise();
    }


    public async getSharedAccessGroupsForDatasetInstance(datasetInstanceId) {
        return this.http.get(this.config.backendURL + `/dataset/sharedAccessGroups/${datasetInstanceId}`).toPromise();
    }

    public async revokeAccessToGroupForDatasetInstance(datasetInstanceId, accessGroup) {
        return this.http.delete(this.config.backendURL + `/dataset/sharedAccessGroups/${datasetInstanceId}`,
            {body: JSON.stringify(accessGroup)}).toPromise();
    }


    public async getInvitedAccessGroupsForDatasetInstance(datasetInstanceId) {
        return this.http.get(this.config.backendURL + `/dataset/invitedAccessGroups/${datasetInstanceId}`).toPromise();
    }


    public async inviteAccountToShareDatasetInstance(datasetInstanceId, accountExternalIdentifier, expiryDate = null) {
        let path = `/dataset/invitedAccessGroups/${datasetInstanceId}/${accountExternalIdentifier}`;
        if (expiryDate) path += '?expiryDate=' + expiryDate;
        return this.http.get(this.config.backendURL + path).toPromise();
    }


    public async cancelInvitationForAccessGroupForDatasetInstance(datasetInstanceId, accessGroup) {
        return this.http.delete(this.config.backendURL + `/dataset/invitedAccessGroups/${datasetInstanceId}`,
            {body: JSON.stringify(accessGroup)}).toPromise();
    }


    // Get a sharable item for an invitation
    public async getSharableItemForInvitation(invitationCode) {
        return this.http.get(this.config.guestURL + `/sharing/${invitationCode}`).toPromise();
    }


    // Accept a sharing invitation using an invitation code.
    public async acceptSharingInvitation(invitationCode) {
        this.http.post(this.config.guestURL + `/sharing/${invitationCode}`, {}).toPromise();
    }

    // Accept a sharing invitation using an invitation code.
    public async cancelSharingInvitation(invitationCode) {
        this.http.delete(this.config.guestURL + `/sharing/${invitationCode}`).toPromise();
    }


    public listSnapshotProfiles(filterString = '', limit = '10', offset = '0', tags?) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';
        const activeTag = tags || (this.tagService.activeTag.getValue() ? this.tagService.activeTag.getValue().key : '');

        return this.http.get(this.config.backendURL + '/dataset/snapshotprofile', {
            params: _.omitBy({filterString, limit, offset, tags: activeTag, projectKey}, _.isNil)
        });
    }

    public getSnapshotProfile(id: number) {
        return this.http.get(this.config.backendURL + '/dataset/snapshotprofile/' + id)
            .toPromise();
    }

    public getSnapshotProfilesForDataset(datasetInstanceId: number) {
        return this.http.get(this.config.backendURL + '/dataset/snapshotprofile/dataset/' + datasetInstanceId)
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
