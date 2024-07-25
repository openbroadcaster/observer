import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldPassword extends OBField {
    renderView() {
        render(html` ********* `, this.root);
    }

    renderEdit() {
        render(
            html`
                <input
                    type="password"
                    onchange=${this.inputChange.bind(this)}
                    placeholder=${this.getAttribute("placeholder")}
                    autocomplete="new-password"
                    value=${this._value}
                />
            `,
            this.root,
        );
    }

    inputChange(event) {
        this._value = event.target.value;
    }

    scss() {
        return `
            :host {
                input {
                    font: inherit;
                    font-size: 13px;
                    display: inline-block;
                    color: #2e3436;
                    border-radius: 2px;
                    border: 0;
                    padding: 5px;
                    width: 250px;
                    vertical-align: middle;
                }
            }
        `;
    }
}

customElements.define("ob-field-password", OBFieldPassword);
