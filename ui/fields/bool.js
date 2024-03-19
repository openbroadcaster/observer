import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldBool extends OBField {
    #init;

    connectedCallback() {
        if (this.#init) {
            return;
        }
        this.#init = true;

        this.renderComponent().then(() => {
            if (this.hasAttribute('value')) {
                const value = this.getAttribute('value');
                if (value.toLowerCase() === "true" || value === "1") {
                    this.root.querySelector('input').checked = true;
                }
            }
        });;
    }

    renderEdit() {
        render(html`
            <input type="checkbox" />
        `, this.root);
    }

    renderView() {
        render(html`
            <input type="checkbox" disabled />
        `, this.root);
    }

    scss() {
        return `
            :host {
                input {
                    font: inherit;
                    font-size: 13px;
                    color: #2e3436;
                }
            }
        `;
    }

    get value() {
        if (this.root.querySelector('input')) {
            return this.root.querySelector('input').checked;
        } else {
            return false;
        }
    }

    set value(value) {
        this.root.querySelector('input').checked = value;
        this.renderComponent();
    }
}

customElements.define('ob-field-bool', OBFieldBool);
