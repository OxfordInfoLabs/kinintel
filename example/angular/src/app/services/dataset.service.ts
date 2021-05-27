import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';
import * as _ from 'lodash';

@Injectable({
    providedIn: 'root'
})
export class DatasetService {

    constructor(private http: HttpClient) {
    }

    public getDatasets(filterString = '', limit = '10', offset = '0') {
        return this.http.get('/account/dataset', {
            params: {
                filterString, limit, offset
            }
        });
    }

    public getDataset(id) {
        return this.http.get(`/account/dataset/${id}`).toPromise();
    }

    public evaluateDataset(datasetInstanceSummary, additionalTransformations?) {
        return this.http.post('/account/dataset/evaluate', _.omit({
            datasetInstanceSummary, additionalTransformations
        }, _.isNil)).toPromise();
    }
}
