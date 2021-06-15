import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import * as _ from 'lodash';

@Injectable({
    providedIn: 'root'
})
export class DatasourceService {

    constructor(private http: HttpClient) {
    }

    public getDatasources(filterString = '', limit = '10', offset = '0') {
        return this.http.get('/account/datasource', {
            params: {
                filterString, limit, offset
            }
        });
    }

    public getDatasource(key) {
        return this.http.get('/account/datasource/' + key).toPromise();
    }

    public getEvaluatedParameters(evaluatedDatasource) {
        return this.http.post('/account/datasource/parameters/' + evaluatedDatasource.key,
            {transformationInstances: evaluatedDatasource.transformationInstances}).toPromise();
    }

    public evaluateDatasource(evaluatedDatasource, additionalTransformations?) {
        const transformationInstances = _.merge(evaluatedDatasource.transformationInstances, additionalTransformations);

        return this.http.post('/account/datasource/evaluate', _.omit({
            key: evaluatedDatasource.key, transformationInstances
        }, _.isNil)).toPromise();
    }
}
