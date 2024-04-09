import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldCountry extends OBField {
    #init;

    connectedCallback() {
        if (! this.#init) {
            // do stuff
            
            this.#init = true;
        }

        this.renderComponent().then(() => {
            // do stuff that requires component to have been rendered
        });
    }

    renderEdit() {
        render(html`
            <div>Edit test</div>
        `, this.root);
    }

    renderView() {
        render(html`
            <div>View test</div>
        `, this.root);
    }

    scss() {
        return `
            :host {
            }
        `;
    }

    get value() {
        // TODO
        return null;
    }

    set value(value) {
        // TODO
        return null;
    }
}

customElements.define('ob-field-country', OBFieldCountry);
