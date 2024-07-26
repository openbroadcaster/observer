import { OBField } from "../base/field.js";
import { html, render } from "../vendor.js";

class OBFieldTextarea extends OBField {
    static comparisonOperators = {
        eq: "is",
        neq: "is not",
        contains: "contains",
        ncontains: "does not contain",
    };

    static comparisonField = "text";

    renderView() {
        render(html`<div id="view">${this.value}</div>`, this.root);
    }

    renderEdit() {
        const wrap = this.getAttribute("wrap");
        render(
            html`<textarea wrap=${wrap} onchange=${this.textareaChange.bind(this)}>${this._value}</textarea>`,
            this.root,
        );
    }

    textareaChange(event) {
        this.value = event.target.value;
    }

    scss() {
        return `
            :host {
                display: inline-block;
                
                #root {
                    height: 100%;
                }
                
                textarea {
                    color: #2e3436;
                    font-size: 13px;
                    border-radius: 2px;
                    border: 0;
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
