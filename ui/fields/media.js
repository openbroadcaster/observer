import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldMedia extends OBField {
    connectedCallback() {
        this.renderComponent();
    }

    renderEdit() {
        render(html`
            <div>Edit test</div>
        `, this.root);
    }

    renderView() {
        render(html`
            <div>View test</div>
        `, this.root);
    }

    get value() {
        // TODO
        return null;
    }

    set value(value) {
        // TODO
        this.refresh();
    }
}

customElements.define('ob-field-media', OBFieldMedia);