import { OBField } from '../base/field.js';
import { html, render } from '../vendor.js';

class OBFieldNumber extends OBField {
    #init;

    connectedCallback() {
        if (this.#init) {
            return;
        }

        this.#init = true;
        this.renderComponent().then(() => {
            // do stuff
        });
    }

    renderView() {
        render(html`
            ${this.value}
        `, this.root);
    }

    renderEdit() {
        render(html`
            <input id="input" type="number" />
        `, this.root);
    }

    scss() {
        return `
            :host {
                display: inline-block; 
                
                input {
                    color: #2e3436;
                    font-size: 13px;
                    border-radius: 2px;
                    border: 0;
                    padding: 5px;
                    width: 250px;
                    vertical-align: middle;
                }
            }
        `
    }
}

customElements.define('ob-field-number', OBFieldNumber);
