import {Component, Inject, Input, OnInit} from '@angular/core';
import {MAT_LEGACY_DIALOG_DATA as MAT_DIALOG_DATA, MatLegacyDialogRef as MatDialogRef} from '@angular/material/legacy-dialog';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {TagService} from '../../services/tag.service';
import {KININTEL_CONFIG, KinintelModuleConfig} from '../../kinintel-config';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';

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

    constructor(public dialogRef: MatDialogRef<TagPickerComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private tagService: TagService,
                @Inject(KININTEL_CONFIG) public config: KinintelModuleConfig) {
    }

    ngOnInit(): void {

        this.activeTag = this.tagService.activeTag.getValue() || {};

        merge(this.searchText, this.reload).pipe(
            debounceTime(300),
            distinctUntilChanged(),
            switchMap(() =>
                this.getTags()
            )
        ).subscribe((tags: any) => {
            this.tags = tags;
        });

    }

    public activateTag(tag) {
        this.tagService.setActiveTag(tag);
        this.dialogRef.close();
    }

    public removeTag(tagKey) {
        const message = `Are you sure you would like to remove this ${this.config.tagLabel}?`;
        if (window.confirm(message)) {
            this.tagService.removeTag(tagKey).then(() => {
                if (this.tagService.activeTag.getValue() &&
                    this.tagService.activeTag.getValue().key === tagKey) {
                    this.tagService.resetActiveTag();
                }
                this.reload.next(Date.now());
            });
        }
    }

    public createTag() {
        this.tagService.saveTag(this.newName, this.newDescription).then(() => {
            this.newName = '';
            this.newDescription = '';
            this.addNew = false;
            this.reload.next(Date.now());
        });
    }

    private getTags() {
        return this.tagService.getTags(
            this.searchText.getValue()
        ).pipe(map((tags: any) => {
                return tags;
            })
        );
    }

}
