import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";
import { Api } from "../utils/api.js";

class OBFieldCountry extends OBField {
    static comparisonOperators = {
        eq: "is",
        neq: "is not",
    };

    async _countries() {
        const result = await Api.request({ endpoint: "metadata/countries", cache: true });
        if (!result) return {};

        const countries = {};
        for (const country of result) {
            countries[country.country_id] = country.name;
        }

        return countries;
    }

    async renderEdit() {
        const countries = await this._countries();

        render(html`<ob-field-select data-edit></ob-field-select>`, this.root);
        this.fieldSelect = this.root.querySelector("ob-field-select");
        this.fieldSelect.options = countries;
        this.fieldSelect.value = this.value;

        if (this.initValue) {
            const temp = this.initValue;
            this.initValue = false;
            this.value = temp;
        }
    }

    async renderView() {
        const countries = await this._countries();
        render(html`${countries[this.value]}`, this.root);
    }

    get value() {
        return this.fieldSelect?.value;
    }

    set value(value) {
        if (this.fieldSelect) {
            this.fieldSelect.value = value;
        } else {
            this.initValue = value;
        }

        this.refresh();
    }
}

customElements.define("ob-field-country", OBFieldCountry);
