import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldGroup extends OBField {

    // languages are common to all instances of this element
    static groups = null;

    async connectedCallback() {
        if (OBFieldGroup.groups === null) {

            // prevent multiple calls if this element appears twice in one form
            OBFieldGroup.groups = {};

            const result = await OB.API.postPromise('users', 'group_list', {});

            if (!result.status) return false;

            // create an object linking language "id" with language "ref_name"
            for (const group of result.data) {
                OBFieldGroup.groups[group.id] = group.name;
            }
        }

        this.renderComponent();
    }

    get value() {
        return this.fieldSelect?.value;
    }

    set value(value) {
        if (this.fieldSelect) {
            this.fieldSelect.value = value;
        }
        else { this.initValue = value; }
    }

    async renderEdit() {
        render(html`<ob-field-select data-edit data-multiple></ob-field-select>`, this.root);

        // set our field options if we don't have them already
        this.fieldSelect = this.root.querySelector('ob-field-select');
        if (!this.fieldSelect.options.length) {
            this.fieldSelect.options = OBFieldGroup.groups;
            this.fieldSelect.refresh();
        }

        // if we have an init value, set it
        if (this.initValue) {
            this.fieldSelect.value = this.initValue;
            delete this.initValue;
        }
    }
}

customElements.define('ob-field-group', OBFieldGroup);
