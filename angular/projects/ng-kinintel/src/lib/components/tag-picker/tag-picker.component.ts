import {Component, Inject, Input, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {BehaviorSubject, Subject} from 'rxjs';

@Component({
    selector: 'ki-tag-picker',
    templateUrl: './tag-picker.component.html',
    styleUrls: ['./tag-picker.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class TagPickerComponent implements OnInit {

    public tags: any = [];
    public addNew = false;
    public searchText = new BehaviorSubject<string>('');
    public reload = new Subject();
    public activeTag: any = {};
    public newName;
    public newDescription;
    public environment: any = {};

    private tagService: any;

    constructor(public dialogRef: MatDialogRef<TagPickerComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
        this.tagService = this.data.tagService;
        this.environment = this.data.environment || {};

        this.activeTag = this.tagService.activeTag.getValue() || {};

        this.tagService.getTags().then(tags => {
            this.tags = tags;
        });
    }

    public activateTag(tag) {
        this.tagService.setActiveTag(tag);
        this.dialogRef.close();
    }

    public removeTag(tag) {

    }

    public createTag() {

    }

}
