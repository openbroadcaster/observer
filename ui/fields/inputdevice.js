import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldInputDevice extends OBField {

    #init;

    connectedCallback() {
        if (this.#init) {
            return;
        }

        this.#init = true;

        this.renderComponent();
    }

    renderEdit() {
        render(html`
            <div>todo</div>
        `, this.root);
    }

    renderView() {
        render(html`
            <div>todo</div>
        `, this.root);
    }

    scss() {
        return `
            :host {
            }
        `;
    }

    get value() {
        // todo
        return undefined;
    }

    set value(value) {
        // todo
        return undefined;
    }

}

customElements.define('ob-field-input-device', OBFieldInputDevice);