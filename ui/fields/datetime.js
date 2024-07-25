import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldDatetime extends OBField {
    valueFormat = "YYYY-MM-DD HH:mm:ss";
    valueStringFormat = "MMM D, YYYY h:mm A";

    renderView() {
        render(html`<div id="field">${this._valueString}</div> `, this.root);
    }

    renderEdit() {
        render(
            html`
                <input id="field" type="text" value="${this._valueString}" onchange=${this.inputChange.bind(this)} />
            `,
            this.root,
        );
    }

    inputChange(event) {
        this.value = event.target.value;
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

    set value(value) {
        const datetime = chrono.casual.parseDate(value);
        this._value = datetime ? dayjs(datetime).format(this.valueFormat) : "";
        this._valueString = datetime ? dayjs(datetime).format(this.valueStringFormat) : "";
        this.refresh();
    }

    get value() {
        return this._value;
    }
}

export default OBFieldDatetime;

if (customElements.get("ob-field-datetime") === undefined) {
    customElements.define("ob-field-datetime", OBFieldDatetime);
}
