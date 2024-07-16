import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldCoordinates extends OBField {
    #init;

    #value;

    async connected() {
        if (this.#init) {
            return;
        }
        this.#init = true;

        this.#value = "";

        this.renderComponent().then(() => {});
    }

    renderEdit() {
        render(
            html` <input id="field" type="text" onchange=${this.#updateValue.bind(this)} value="${this.value}" /> `,
            this.root,
        );
    }

    renderView() {
        render(html` <div id="field">${this.value}</div> `, this.root);
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

    #updateValue(event) {
        const value = event.target.value;
        this.#value = value;
        this.renderComponent();
    }

    get value() {
        return this.#value;
    }

    set value(value) {
        OB.API.postPromise("metadata", "address_coordinates", { address: value }).then((response) => {
            if (response.status) {
                let result = response.data;
                this.#value = result.lat + ", " + result.lng;
                this.renderComponent();
            }
        });
    }
}

customElements.define("ob-field-coordinates", OBFieldCoordinates);
