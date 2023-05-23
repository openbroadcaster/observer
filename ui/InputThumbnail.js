import { html, render } from './vendor.js'
import { OBInput } from './Input.js';

class OBInputThumbnail extends OBInput {

    #root;

    constructor() {
        super();
        this.#root = this.attachShadow({ mode: 'open'});
    }

    connectedCallback() {
        this.renderComponent();
    }

    renderComponent() {
        render(html`
            <div class="wrapper">
                TODOOOOO
            </div>
        `, this.#root);
    }

    /*get value() {
        // TODO
        return this.#value;
    }

    set value(value) {
        // TODO
        this.#value = value;
    }*/
}

customElements.define('ob-input-thumbnail', OBInputThumbnail);