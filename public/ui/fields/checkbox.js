import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

// TODO remove, use ob-field-boolean instead.
class OBFieldCheckbox extends OBField {
    #init;

    async connected() {
        if (this.#init) {
            return;
        }
        this.#init = true;

        this.renderComponent().then(() => {
            if (this.hasAttribute("checked")) {
                this.root.querySelector("input").checked = true;
            }
        });
    }

    renderEdit() {
        render(html` <input type="checkbox" /> `, this.root);
    }

    renderView() {
        render(html` <input type="checkbox" disabled /> `, this.root);
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

    get value() {
        if (this.root.querySelector("input")) {
            return this.root.querySelector("input").value;
        }
    }

    set value(value) {
        if (this.root.querySelector("input")) {
            this.root.querySelector("input").value = value;
            this.renderComponent();
        }
    }

    get checked() {
        if (this.root.querySelector("input")) {
            return this.root.querySelector("input").checked;
        } else {
            return false;
        }
    }

    set checked(check) {
        this.root.querySelector("input").checked = check;
        this.renderComponent();
    }
}

customElements.define("ob-field-checkbox", OBFieldCheckbox);
