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
            if (this.getAttribute('value')) {
                this.value = this.getAttribute('value');
            }
        });;
    }

    renderEdit() {
        render(html`
            <input type="checkbox" ${this.value === true ? 'checked="checked"' : ''} />
        `, this.root);
    }

    renderView() {
        render(html`
            <input type="checkbox" ${this.value === true ? 'checked="checked"' : ''} disabled />
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
            return this.root.querySelector('input').getAttribute('checked') === 'checked';
        } else {
            return null;
        }
    }

    set value(value) {
        if (value == true) {
            this.root.querySelector('input').setAttribute('checked', 'checked');
        } else {
            this.root.querySelector('input').removeAttribute('checked');
        }
        this.renderComponent();
    }
}

customElements.define('ob-field-bool', OBFieldBool);
