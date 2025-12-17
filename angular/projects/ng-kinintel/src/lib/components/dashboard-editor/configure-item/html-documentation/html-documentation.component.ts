import {Component, Input, OnDestroy, OnInit} from '@angular/core';
import {Subject} from 'rxjs';

@Component({
    selector: 'ki-html-documentation',
    templateUrl: './html-documentation.component.html',
    styleUrls: ['./html-documentation.component.sass'],
    standalone: false
})
export class HtmlDocumentationComponent implements OnInit, OnDestroy {

    @Input() columns: Subject<any>;

    public availableColumns: any = [];
    public showData = false;
    public Object = Object;
    public documentation = [
        {
            title: 'HTML Classes',
            description: 'Available classes for styling HTML elements.',
            data: [
                {
                    description: 'Change colour of text',
                    classes: {
                        Class: 'text-{colour}-{value}',
                        '{colour}': 'red, blue, green, gray',
                        '{value}': '100, 200, 300, ..., 900'
                    }
                },
                {
                    description: 'Change background colour',
                    classes: {
                        Class: 'bg-{colour}-{value}',
                        '{colour}': 'red, blue, green, gray',
                        '{value}': '100, 200, 300, ..., 900'
                    }
                },
                {
                    description: 'Add padding to an element.',
                    classes: {
                        Class: 'p{t|r|b|l}-{size}, py-{size}, px-{size}',
                        '{size}': '0, 1, 1.5, 2, 2.5, ..., p-10',
                        Value: '1 = .25rem, 2 = .5rem, 4 = 1rem'
                    }
                },
                {
                    description: 'Add margin to an element.',
                    classes: {
                        Class: 'm{t|r|b|l}-{size}, my-{size}, mx-{size}',
                        '{size}': '0, 1, 1.5, 2, 2.5, ..., p-10',
                        Value: '1 = .25rem, 2 = .5rem, 4 = 1rem'
                    }
                },
                {
                    description: 'Change font size',
                    classes: {
                        Class: 'text-{size}',
                        '{size}': 'xs, sm, base, lg, xl, 2xl, ..., 9xl',
                        Value: 'xs = .75rem, sm = .875rem, base = 1rem'
                    }
                },
                {
                    description: 'Change font weight',
                    classes: {
                        Class: 'font-{weight}',
                        '{weight}': 'thin, light, normal, medium, bold',
                        Value: 'thin = 100, light = 300, medium = 500, bold = 700'
                    }
                }
            ]
        },
        {
            title: 'Dynamic Templating',
            description: 'Attribute and template functionality for iterating and conditioning',
            data: [
                {
                    description: 'Iterate over whole dataset and display each item name.',
                    classes: {
                        Template: 'd-each-item="dataSet"',
                        Example: '<div d-each-item="dataSet">[[item.name]]</div>'
                    }
                },
                {
                    description: 'Show/Hide element based on value.',
                    classes: {
                        Template: 'd-if="item.value"',
                        Example: '<section d-if="item.value"></section>'
                    }
                },
                {
                    description: 'Dynamically add a class to an element.',
                    classes: {
                        Template: 'd-class-completed="item.completed"',
                        Example: '<li d-class-completed="item.completed">[[item.name]]</li>'
                    }
                }
            ]
        }
    ];

    constructor() {
    }

    ngOnInit(): void {
        if (this.columns) {
            this.columns.subscribe(columns => {
                this.availableColumns = columns;
            });
        }
    }

    ngOnDestroy(): void {
        if (this.columns) {
            this.columns.unsubscribe();
        }
    }

}
