import { OBField } from "../base/field.js";
import { html, render } from "../vendor.js";

class OBFieldText extends OBField {
    static operators = {
        eq: "is",
        neq: "is not",
        contains: "contains",
        ncontains: "does not contain",
    };

    renderEdit() {
        render(
            html`<input
                type="text"
                maxlength=${this.getAttribute("maxlength")}
                onchange=${this.inputChange.bind(this)}
                value="${this._value}"
            />`,
            this.root,
        );
    }

    inputChange(event) {
        this._value = event.target.value;
    }

    scss() {
        return `
            :host {
                display: inline-block; 
                
                input {
                    color: #2e3436;
                    font-size: 13px;
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

customElements.define("ob-field-text", OBFieldText);
