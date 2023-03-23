import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import {KinintelModuleConfig} from '../ng-kinintel.module';
import * as lodash from 'lodash';
const _ = lodash.default;

@Injectable({
    providedIn: 'root'
})
export class ExternalService {

    constructor(private http: HttpClient,
                private config: KinintelModuleConfig) {
    }

    public getDashboard(id, params) {
        const url = `${this.config.externalURL}/externalDashboard/${id}`;

        return this.http.get(url, {params}).toPromise();
    }

    public evaluateDataset(dashboardId, datasetInstanceKey, parameterValues: any = {}, offset = '0', limit = '25', queryParams: any) {
        queryParams.limit = limit;
        queryParams.offset = offset;

        return this.http.post(`${this.config.externalURL}/externalDashboard/evaluateDashboardDataset/${dashboardId}/${datasetInstanceKey}`,
            parameterValues, {params: queryParams}).toPromise();
    }
}
