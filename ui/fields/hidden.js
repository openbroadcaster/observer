import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldHidden extends OBField {
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
        });;
    }

    renderEdit() {
        render(html`
            <input type="hidden" />
        `, this.root);
    }

    renderView() {
        render(html`
            <input type="hidden" disabled />
        `, this.root);
    }

    scss() {
        return `
            :host {
            }
        `;
    }

    get value() {
        if (this.root.querySelector('input')) {
            return this.root.querySelector('input').value;
        } 
    }

    set value(value) {
        this.root.querySelector('input').value = value;
        this.renderComponent();
    }
}

customElements.define('ob-field-hidden', OBFieldHidden);
