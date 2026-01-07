import { OBField } from "../base/field.js";
import { html, render } from "../vendor.js";

class OBFieldTextarea extends OBField {
    _value = "";

    static comparisonOperators = {
        eq: "is",
        neq: "is not",
        contains: "contains",
        ncontains: "does not contain",
    };

    static comparisonField = "text";

    renderView() {
        render(html`<div id="view">${this._value}</div>`, this.root);
    }

    renderEdit() {
        const wrap = this.getAttribute("wrap");
        render(
            html`<textarea wrap=${wrap} onchange=${this.textareaChange.bind(this)}>${this._value}</textarea>`,
            this.root,
        );
    }

    textareaChange(event) {
        this._value = event.target.value;
    }

    scss() {
        return `
            :host {
                display: inline-block;
                
                #root {
                    height: 100%;
                }
                
                textarea {
                    color: var(--field-color);
                    background-color: var(--field-background);
                    border-radius: var(--field-radius);
                    border: var(--field-border);
                    font-size: 13px;
                    padding: 5px;
                    width: 250px;
                    height: 100%;
                }
            }

            #view {
                white-space: pre-line;
            }
        `;
    }
}

customElements.define("ob-field-textarea", OBFieldTextarea);
