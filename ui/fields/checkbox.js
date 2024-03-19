import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldCheckbox extends OBField {
    #init;

    connectedCallback() {
        if (this.#init) {
            return;
        }
        this.#init = true;

        this.renderComponent().then(() => {
            if (this.getAttribute('value')) {
                this.root.querySelector('input').value = this.getAttribute('value');
            }

            if (this.hasAttribute('checked')) {
                this.root.querySelector('input').checked = true;
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
            return this.root.querySelector('input').value;
        } 
    }

    get checked() {
        if (this.root.querySelector('input')) {
            return this.root.querySelector('input').checked;
        } else {
            return false;
        }
    }

    set checked(check) {
        this.root.querySelector('input').checked = check;
        this.renderComponent();
    }
}

customElements.define('ob-field-checkbox', OBFieldCheckbox);
