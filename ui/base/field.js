import { html, render } from "../vendor.js";
import { OBElement } from "../base/element.js";

export class OBField extends OBElement {
    _value;
    _editable;
    _settings;

    /*
    what operators are available for this field? available as follows:
    {
        eq: 'is',
        neq: 'is not',
        contains: 'contains',
        ncontains: 'does not contain',
        gt: 'greater than',
        gte: 'greater than or equal to',
        lt: 'less than',
        lte: 'less than or equal to',
        has: 'has',
        nhas: 'does not have'
    };
    */
    static comparisonOperators;

    // which field element should we use to provide the comparison value?
    static comparisonField;

    async connectedCallback() {
        if (this.connected) {
            await this.connected();
        }

        if (this.getAttribute("value") && this._value === undefined) {
            this._value = this.getAttribute("value");
        }

        if (this.hasAttribute("data-edit") && this._editable === undefined) {
            this._editable = true;
        }

        if (this.constructor.name == "OBFieldRange" && this.hasAttribute("data-edit")) {
            this._editable = true; // TODO (remove temporary fix for range field)
        }

        this.resolveInitialized();

        this.renderComponent();
    }

    // Bind this to events inside the shadowroot to propagate them to outside
    // the custom element for other event handlers to catch.
    propagateEvent(event) {
        this.dispatchEvent(new Event(event.type));
    }

    scss() {
        return `
        :host {
            display: inline-block;
        }
    `;
    }

    get value() {
        return this._value;
    }

    set value(value) {
        this._value = value;
        this.refresh();
    }

    get editable() {
        return this._editable;
    }

    set editable(editable) {
        this._editable = !!editable;
        this.refresh();
    }

    get settings() {
        return this._settings;
    }

    set settings(settings) {
        this._settings = settings;
        this.refresh();
    }

    async renderComponent() {
        if (this._editable) await this.renderEdit();
        else await this.renderView();
    }

    async renderView() {
        render(html`<div>${this._value}</div>`, this.root);
    }

    async renderEdit() {
        render(html`<input id="input" type="text" value="${this._value}" />`, this.root);
    }
}
