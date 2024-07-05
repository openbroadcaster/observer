import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldUser extends OBField {
    // languages are common to all instances of this element
    static users = null;

    async connectedCallback() {
        if (OBFieldUser.users === null) {
            // prevent multiple calls if this element appears twice in one form
            OBFieldUser.users = {};

            const result = await OB.API.postPromise("users", "user_list", {});

            if (!result.status) return false;

            // create an object linking language "id" with language "ref_name"
            for (const user of result.data) {
                OBFieldUser.users[user.id] = user.display_name + " (" + user.email + ")";
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
        } else {
            this.initValue = value;
        }
    }

    async renderEdit() {
        render(html`<ob-field-select data-edit data-multiple></ob-field-select>`, this.root);

        // set our field options if we don't have them already
        this.fieldSelect = this.root.querySelector("ob-field-select");
        if (!this.fieldSelect.options || !this.fieldSelect.options.length) {
            this.fieldSelect.options = OBFieldUser.users;
            this.fieldSelect.refresh();
        }

        // if we have an init value, set it
        if (this.initValue) {
            this.fieldSelect.value = this.initValue;
            delete this.initValue;
        }
    }
}

customElements.define("ob-field-user", OBFieldUser);
