import {
    AfterViewInit, ApplicationRef,
    ChangeDetectorRef,
    Component, ComponentFactoryResolver, EmbeddedViewRef, Injector,
    NgZone,
    OnInit,
    ViewChild,
    ViewContainerRef,
    ViewEncapsulation
} from '@angular/core';
import 'gridstack/dist/gridstack.min.css';
import {GridStack, GridStackNode} from 'gridstack';
// THEN to get HTML5 drag&drop
import 'gridstack/dist/h5/gridstack-dd-native';
import * as _ from 'lodash';
import {Subject} from 'rxjs';
import {ItemComponentComponent} from './item-component/item-component.component';

@Component({
    selector: 'ki-dashboard-editor',
    templateUrl: './dashboard-editor.component.html',
    styleUrls: ['./dashboard-editor.component.sass'],
    encapsulation: ViewEncapsulation.None
})
export class DashboardEditorComponent implements OnInit, AfterViewInit {

    @ViewChild('viewContainer', {read: ViewContainerRef}) viewContainer: ViewContainerRef;

    public itemTypes: any = [
        {
            type: 'lineChart',
            label: 'Line Chart',
            icon: 'show_chart',
            width: 6,
            height: 4
        },
        {
            type: 'barChart',
            label: 'Bar Chart',
            icon: 'stacked_bar_chart',
            width: 4,
            height: 3
        },
        {
            type: 'pieChart',
            label: 'Pie Chart',
            icon: 'pie_chart',
            width: 6,
            height: 5
        },
        {
            type: 'table',
            label: 'Table',
            icon: 'table_chart',
            width: 5,
            height: 7
        },
        {
            type: 'donut',
            label: 'Donut',
            icon: 'donut_large',
            width: 6,
            height: 5
        }
    ];

    private grid: GridStack;

    constructor(private componentFactoryResolver: ComponentFactoryResolver,
                private applicationRef: ApplicationRef,
                private injector: Injector) {
    }

    ngOnInit(): void {
    }

    ngAfterViewInit() {
        let items = [];
        if (sessionStorage.getItem('grid')) {
            items = JSON.parse(sessionStorage.getItem('grid'));
        }

        const options = {
            minRow: 1, // don't collapse when empty
            float: false,
            dragIn: '.draggable-toolbar .grid-stack-item', // add draggable to class
            dragInOptions: {
                revert: 'invalid',
                scroll: false,
                appendTo: 'body',
                helper: DashboardEditorComponent.myClone
            },
            acceptWidgets: (el) => {
                el.className += ' grid-stack-item';
                return true;
            }
        };
        this.grid = GridStack.init(options);

        // If we are loading items from memory we need to add them manually
        // so the components get reloaded as part of the on 'added' functionality
        setTimeout(() => {
            items.forEach(item => {
                this.grid.addWidget(item);
            });
        });

        this.grid.on('added', (event: Event, newItems: GridStackNode[]) => {
            newItems.forEach((item) => {
                let data = null;
                if (item.content) {
                    const itemElement: any = document.createRange().createContextualFragment(item.content);
                    data = itemElement.firstChild.dataset.itemData ? JSON.parse(itemElement.firstChild.dataset.itemData) : null;
                }

                while (item.el.firstChild.firstChild) {
                    item.el.firstChild.firstChild.remove();
                }

                this.addComponentToGridItem(item.el.firstChild, data || this.itemTypes[item.el.dataset.index], !!data);
            });
        });

    }

    public save() {
        const grid = this.grid.save();
        sessionStorage.setItem('grid', JSON.stringify(grid));
    }

    private static myClone(event) {
        return event.target.cloneNode(true);
    }

    private addComponentToGridItem(element, itemData?, load?) {
        //create a component reference
        const componentRef = this.componentFactoryResolver.resolveComponentFactory(ItemComponentComponent)
            .create(this.injector);

        componentRef.instance.grid = this.grid;
        componentRef.instance.item = itemData || {};
        if (load) {
            componentRef.instance.load();
        }

        // attach component to the appRef so that so that it will be dirty checked.
        this.applicationRef.attachView(componentRef.hostView);

        // get DOM element from component
        const domElem = (componentRef.hostView as EmbeddedViewRef < any > )
            .rootNodes[0] as HTMLElement;

        domElem.dataset.itemData = JSON.stringify(itemData || {});

        element.appendChild(domElem);

        return componentRef;
    }

}

