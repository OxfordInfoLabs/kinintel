import {Injectable} from '@angular/core';
import {BehaviorSubject} from 'rxjs';
import {HttpClient} from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class TagService {

    public activeTag = new BehaviorSubject(null);

    constructor(private http: HttpClient) {
        const activeTag = localStorage.getItem('activeTag');
        if (activeTag) {
            this.setActiveTag(JSON.parse(activeTag));
        }
    }

    public getTags() {
        return this.http.get('/account/metadata/tag').toPromise();
    }

    public setActiveTag(tag) {
        localStorage.setItem('activeTag', JSON.stringify(tag));
        this.activeTag.next(tag);
    }

    public resetActiveTag() {
        localStorage.removeItem('activeTag');
        this.activeTag.next(null);
    }
}
