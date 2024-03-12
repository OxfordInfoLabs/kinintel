import {Injectable} from '@angular/core';
import {KinintelModuleConfig} from "ng-kinintel/src/lib/ng-kinintel.module";
import {HttpClient} from "@angular/common/http";
import {ProjectService} from "ng-kinintel/src/lib/services/project.service";

@Injectable({
    providedIn: 'root'
})
export class DataProcessorService {

    constructor(private config: KinintelModuleConfig,
                private http: HttpClient,
                private projectService: ProjectService) {
    }


    public filterProcessorsByType(type: string = '', searchTerm = '', limit = '10', offset = '0', tags?) {
        const projectKey = this.projectService.activeProject.getValue() ? this.projectService.activeProject.getValue().projectKey : '';

        return this.http.get(this.config.backendURL + '/dataprocessor/type/snapshot', {
            params: _.omitBy({type, searchTerm, projectKey, limit, offset}, _.isNil)
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


}
