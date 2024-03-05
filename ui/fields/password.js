import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldPassword extends OBField {
    #init;

    connectedCallback() {
        if (!this.#init) {
            this.#init = true
        }

        this.renderComponent().then(() => {
            if (this.getAttribute('value')) {
                this.value = this.getAttribute('value');
            }

            if (this.getAttribute('placeholder')) {
                this.root.querySelector('input').placeholder = this.getAttribute('placeholder');
            }
        });;
    }

    renderEdit() {
        render(html`
            <input type="password" autocomplete="off" value=${this.value} />
        `, this.root);
    }

    renderView() {
        render(html`
            <input type="password" autocomplete="off" value=${this.value} disabled />
        `, this.root);
    }
    
    scss() {
        return `
            :host {
                input {
                    font: inherit;
                    font-size: 13px;
                    display: inline-block;
                    color: #2e3436;
                    border-radius: 2px;
                    border: 0;
                    padding: 5px;
                    width: 250px;
                    vertical-align: middle;
                }
            }
        `;
    }

    get value() {
        if (this.root.querySelector('input')) {
            return this.root.querySelector('input').value;
        } else {
            return '';
        }
    }

    set value(value) {
        this.root.querySelector('input').value = value;
        this.renderComponent();
    }
}

customElements.define('ob-field-password', OBFieldPassword);
