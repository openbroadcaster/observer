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
            tinymce.init({
                target: this,
                theme: 'silver',
                plugins: 'link',
                menubar: false,
                toolbar: 'undo redo | bold italic | link',
                branding: false
            });
        });
    }

    renderEdit() {
        render(html`
            <textarea id="edit">TODO edit</textarea>
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
