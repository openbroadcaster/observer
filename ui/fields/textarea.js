import { OBField } from '../base/field.js';
import { html, render } from '../vendor.js';

class OBFieldTextarea extends OBField {
    #init;

    async connectedCallback() {
        if (this.#init) {
            return;
        }

        this.#init = true;
        this.renderComponent().then(() => {
            if (this.hasAttribute('wrap')) {
                this.root.querySelector('textarea').setAttribute('wrap', this.getAttribute('wrap'));
            }

            this.root.querySelector('textarea').addEventListener('change', this.propagateEvent.bind(this));
        });
    }

    renderView() {
        render(html`
            ${this.value}
        `, this.root);
    }

    renderEdit() {
        render(html`
            <textarea></textarea>
        `, this.root);
    }

    get value() {
        if (this.root.querySelector('textarea')) {
            return this.root.querySelector('textarea').value;
        }
    }

    set value(value) {
        if (! this.root.querySelector('textarea')) {
            return;
        }
        
        this.root.querySelector('textarea').value = value;
        this.renderComponent().then(() => {
            this.propagateEvent('change');
        });
    }

    scss() {
        return `
            :host {
                display: inline-block;
                
                #root {
                    height: 100%;
                }
                
                textarea {
                    color: #2e3436;
                    font-size: 13px;
                    border-radius: 2px;
                    border: 0;
                    padding: 5px;
                    width: 250px;
                    height: 100%;
                }
            }
        `
    }
}

customElements.define('ob-field-textarea', OBFieldTextarea);
