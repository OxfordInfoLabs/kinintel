import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';

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
}
