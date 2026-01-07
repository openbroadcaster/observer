import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";
import { Api } from "../utils/api.js";

class OBFieldCountry extends OBField {
    // Countries are common to all instances of this element.
    static countries = null;

    static comparisonOperators = {
        eq: "is",
        neq: "is not",
    };

    async connected() {
        if (OBFieldCountry.countries === null) {
            OBFieldCountry.countries = {};

            const result = await Api.request({ endpoint: "metadata/countries", cache: true });
            if (!result) return {};

            for (const country of result) {
                OBFieldCountry.countries[country.country_id] = country.name;
            }
        }
    }

    async renderEdit() {
        render(html`<ob-field-select data-edit></ob-field-select>`, this.root);
        this.fieldSelect = this.root.querySelector("ob-field-select");
        this.fieldSelect.options = OBFieldCountry.countries;
        this.fieldSelect.value = this.value;

        if (this.initValue) {
            const temp = this.initValue;
            this.initValue = false;
            this.value = temp;
        }
    }

    async renderView() {
        render(html`${OBFieldCountry.countries[this.value]}`, this.root);
    }

    currentCountryName() {
        return OBFieldCountry.countries[this.value];
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
