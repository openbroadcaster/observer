import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldLicense extends OBField {
    _value;

    static licenses = {
        "CC BY": "CC BY",
        "CC BY-SA": "CC BY-SA",
        "CC BY-NC": "CC BY-NC",
        "CC BY-NC-SA": "CC BY-NC-SA",
        "CC BY-ND": "CC BY-ND",
        "CC BY-NC-ND": "CC BY-NC-ND",
        CC0: "CC0 (Public Domain)",
    };

    static comparisonOperators = {
        eq: "is",
        neq: "is not",
    };

    renderEdit() {
        render(html`<ob-field-select data-edit></ob-field-select>`, this.root);
        this.fieldSelect = this.root.querySelector("ob-field-select");
        this.fieldSelect.options = OBFieldLicense.licenses;
        this.fieldSelect.value = this._value;
    }

    renderView() {
        render(html`<ob-field-select></ob-field-select>`, this.root);
        this.fieldSelect = this.root.querySelector("ob-field-select");
        this.fieldSelect.options = OBFieldLicense.licenses;
        this.fieldSelect.value = this._value;
    }

    get value() {
        return this.fieldSelect?.value;
    }

    set value(value) {
        this._value = value;
        this.refresh();
    }
}

customElements.define("ob-field-license", OBFieldLicense);
