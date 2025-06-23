import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldDatetime extends OBField {
    valueFormat = "YYYY-MM-DD HH:mm:ss";
    valueStringFormat = "MMM D, YYYY h:mm A";

    static comparisonOperators = {
        eq: "is",
        neq: "is not",
        gt: "greater than",
        gte: "greater than or equal to",
        lt: "less than",
        lte: "less than or equal to",
    };

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
                    color: var(--field-color);
                    background-color: var(--field-background);
                    border-radius: var(--field-radius);
                    border: var(--field-border);
                    font-size: 13px;
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
