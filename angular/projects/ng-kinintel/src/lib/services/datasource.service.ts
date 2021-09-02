import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import * as _ from 'lodash';
import {KinintelModuleConfig} from '../ng-kinintel.module';

@Injectable({
    providedIn: 'root'
})
export class DatasourceService {

    constructor(private config: KinintelModuleConfig,
                private http: HttpClient) {
    }

    public getDatasources(filterString = '', limit = '10', offset = '0') {
        return this.http.get(this.config.backendURL + '/datasource', {
            params: {
                filterString, limit, offset
            }
        });
    }

    public getDatasource(key) {
        return this.http.get(this.config.backendURL + '/datasource/' + key).toPromise();
    }

    public getEvaluatedParameters(evaluatedDatasource) {
        return this.http.get(this.config.backendURL + '/datasource/parameters/' +
            (evaluatedDatasource.key || evaluatedDatasource.datasourceInstanceKey)).toPromise();
    }

    public evaluateDatasource(key, transformationInstances, parameterValues?, additionalTransformations?) {
        if (additionalTransformations) {
            additionalTransformations.forEach(transformation => {
                _.remove(transformationInstances, {type: transformation.type});
                transformationInstances.push(transformation);
            });
        }
        return this.http.post(this.config.backendURL + '/datasource/evaluate', _.omit({
            key, transformationInstances, parameterValues
        }, _.isNil)).toPromise();
    }

    public exportData(evaluatedDatasource) {
        return this.http.post(this.config.backendURL + '/datasource/parameters/' +
            (evaluatedDatasource.key || evaluatedDatasource.datasourceInstanceKey),
            {transformationInstances: evaluatedDatasource.transformationInstances}).toPromise();
    }
}
