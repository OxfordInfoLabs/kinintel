import {Injectable} from '@angular/core';
import {HttpClient} from '@angular/common/http';


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


}
