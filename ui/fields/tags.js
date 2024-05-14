import { html, render } from '../vendor.js';
import { OBField } from '../base/field.js';

class OBFieldTags extends OBField {
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
            <div>
                Tags edit
            </div>
        `, this.root);
    }

    renderView() {
        render(html`
            <div>
                Tags view (TODO)
            </div>
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

customElements.define('ob-field-tags', OBFieldTags);