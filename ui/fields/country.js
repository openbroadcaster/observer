import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldCountry extends OBField {
    #init;

    static countries = null;

    async connectedCallback() {
        if (! OBFieldCountry.countries) {
            const result = await OB.API.postPromise('metadata', 'country_list', {});

            if (! result.status) return false;

            OBFieldCountry.countries = {};
            for (const country of result.data) {
                OBFieldCountry.countries[country.country_id] = country.name;
            }
        }

        this.renderComponent().then(() => {
            if (this.getAttribute('value')) {
                this.root.querySelector('ob-field-select').value = this.getAttribute('value');
            }
        });
    }

    renderEdit() {
        render(html`
            <ob-field-select data-edit></ob-field-select>
        `, this.root);

        const fieldSelect = this.root.querySelector('ob-field-select');
        fieldSelect.options = OBFieldCountry.countries;
        fieldSelect.refresh()
    }

    renderView() {
        render(html`
            <ob-field-select></ob-field-select>
        `, this.root);

        const fieldSelect = this.root.querySelector('ob-field-select');
        fieldSelect.options = OBFieldCountry.countries;
        fieldSelect.refresh()
    }

    currentCountryName() {
        return OBFieldCountry.countries[this.value];
    }

    get value() {
        const fieldSelect = this.root.querySelector('ob-field-select');
        if (fieldSelect) {
            return fieldSelect.value;
        }
    }

    set value(value) {
        const fieldSelect = this.root.querySelector('ob-field-select');
        if (fieldSelect) {
            fieldSelect.value = value;
        }
    }
}

customElements.define('ob-field-country', OBFieldCountry);
