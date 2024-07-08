import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldDatetime extends OBField {
    #init;

    #valueObject;
    #valueString;
    #value;
    #setValue;

    valueFormat = "YYYY-MM-DD HH:mm:ss";
    valueStringFormat = "MMM D, YYYY h:mm A";

    async connected() {
        if (this.#init) {
            return;
        }
        this.#init = true;

        this.#valueObject = null;
        this.#valueString = "";
        this.#value = null;

        this.renderComponent().then(() => {
        });
    }

    renderEdit() {
        render(
            html` <input id="field" onchange=${this.#updateValue.bind(this)} type="text" value="${this.#valueString}" /> `,
            this.root,
        );
    }

    renderView() {
        render(html` <div id="field" onchange=${this.#updateValue.bind(this)}>${this.#valueString}</div> `, this.root);
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

    get value() {
        return this.#value;
    }

    set value(value) {
        const inputElem = this.root.querySelector("#field");
        console.log(value);
        inputElem.value = value;
        inputElem.dispatchEvent(new Event("change"));

    }

    #updateValue(event) {
        const value = event.target.value;
        const datetime = chrono.casual.parseDate(value);
        if (datetime) {
            this.#valueObject = datetime;
            this.#valueString = dayjs(datetime).format(this.valueStringFormat);
            this.#value = dayjs(datetime).format(this.valueFormat);
        } else {
            this.#valueObject = null;
            this.#valueString = "";
            this.#value = null;
        }

        this.renderComponent();
    }
}

export default OBFieldDatetime;

if (customElements.get("ob-field-datetime") === undefined) {
    customElements.define("ob-field-datetime", OBFieldDatetime);
}
