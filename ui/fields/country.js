import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldCountry extends OBField {
    #init;

    static countries = null;

    async connectedCallback() {
        if (! OBFieldCountry.countries) {
            const result = await OB.API.postPromise('metadata', 'country_list', {});

            if (! result.status) return false;

            for (const country of result.data) {
                console.log(country);
            }
        }

        this.renderComponent().then(() => {
            // do stuff that requires component to have been rendered
        });
    }

    renderEdit() {
        render(html`
            <div>Edit test</div>
        `, this.root);
    }

    renderView() {
        render(html`
            <div>View test</div>
        `, this.root);
    }

    scss() {
        return `
            :host {
            }
        `;
    }

    get value() {
        // TODO
        return null;
    }

    set value(value) {
        // TODO
        return null;
    }
}

customElements.define('ob-field-country', OBFieldCountry);
