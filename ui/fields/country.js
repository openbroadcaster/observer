import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldCountry extends OBField {
    static countries = null;

    static operators = {
        eq: "is",
        neq: "is not",
    };

    async connected() {
        if (!OBFieldCountry.countries) {
            const result = await OB.API.postPromise("metadata", "country_list", {});

            if (!result.status) return false;

            OBFieldCountry.countries = {};
            for (const country of result.data) {
                OBFieldCountry.countries[country.country_id] = country.name;
            }
        }
    }

    renderEdit() {
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

    renderView() {
        render(html`${this.currentCountryName()}`, this.root);
    }

    async currentCountryName() {
        await this.initialized;
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
