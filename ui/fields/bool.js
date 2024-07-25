import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldBool extends OBField {
    renderView() {
        const output = this._value ? "Yes" : "No";
        render(html`${output}`, this.root);
    }

    renderEdit() {
        render(html`<input type="checkbox" onchange=${this.inputChange} checked=${this._value} />`, this.root);
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
                    color: #2e3436;
                }
            }
        `;
    }
}

customElements.define("ob-field-bool", OBFieldBool);
