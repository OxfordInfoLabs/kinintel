import {Inject, Injectable} from '@angular/core';
import { HttpClient } from '@angular/common/http';
import {KININTEL_CONFIG, KinintelModuleConfig} from '../kinintel-config';
import * as lodash from 'lodash';
const _ = lodash.default;

@Injectable({
    providedIn: 'root'
})
export class ExternalService {

    constructor(private http: HttpClient,
                @Inject(KININTEL_CONFIG) private config: KinintelModuleConfig) {
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
