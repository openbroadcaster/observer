import { OBField } from "../base/field.js";

class OBFieldText extends OBField {
    #init;

    async connectedCallback() {
        if (this.#init) {
            return;
        }

        this.#init = true;
        this.renderComponent().then(() => {
            if (this.hasAttribute("maxlength")) {
                this.root.querySelector("input").setAttribute("maxlength", this.getAttribute("maxlength"));
            }
        });
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
