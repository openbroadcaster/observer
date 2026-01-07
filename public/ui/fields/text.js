import { OBField } from "../base/field.js";
import { html, render } from "../vendor.js";

class OBFieldText extends OBField {
    _value = "";

    static comparisonOperators = {
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
                    font-size: 13px;
                    color: var(--field-color);
                    background-color: var(--field-background);
                    border-radius: var(--field-radius);
                    border: var(--field-border);
                    padding: 5px;
                    width: 250px;
                    vertical-align: middle;
                }
            }
        `;
    }
}

customElements.define("ob-field-text", OBFieldText);
