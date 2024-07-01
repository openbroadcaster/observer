import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldFormatted extends OBField {
    #init;

    connectedCallback() {
        if (this.#init) {
            return;
        }
        this.#init = true;

        this.renderComponent().then(() => {
        });
    }

    renderEdit() {
        render(html`
            <div>TODO</div>
        `, this.root);
    }

    renderView() {
        render(html`
            <div>TODO</div>
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
        return undefined;
    }

    set value(value) {
        // TODO
        return undefined;
    }
}

customElements.define('ob-field-formatted', OBFieldFormatted);
