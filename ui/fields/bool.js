import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldBool extends OBField {
    _value = false;

    static comparisonOperators = {
        eq: "is",
        neq: "is not",
    };

    renderView() {
        const output = this._value ? "Yes" : "No";
        render(html`${output}`, this.root);
    }

    renderEdit() {
        const checked = this._value == "0" ? false : Boolean(this._value);

        render(html`<input type="checkbox" onchange=${this.inputChange.bind(this)} checked=${checked} />`, this.root);
    }

    inputChange(event) {
        this._value = event.target.checked;
    }

    scss() {
        return `
            :host {
                input {
                    font: inherit;
                    font-size: 13px;
                }
            }
        `;
    }
}

customElements.define("ob-field-bool", OBFieldBool);
