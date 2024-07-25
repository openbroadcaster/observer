import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldLanguage extends OBField {
    // languages are common to all instances of this element
    static languages = null;
    static popularLanguages = null;

    static operators = {
        eq: "is",
        neq: "is not",
    };

    async connected() {
        if (OBFieldLanguage.languages === null) {
            // prevent multiple calls if this element appears twice in one form
            OBFieldLanguage.languages = {};
            OBFieldLanguage.popularLanguages = [];
            const popularLanguages = {};

            const result = await OB.API.postPromise("metadata", "language_list", {});

            if (!result.status) return false;

            // create an object linking language "id" with language "ref_name"
            // find popular languages
            for (const lang of result.data) {
                OBFieldLanguage.languages[lang.language_id] = lang.ref_name;
                if (lang.popularity !== null && lang.popularity < 5) {
                    popularLanguages[lang.popularity] = lang.language_id;
                }
            }
            // convert to array
            OBFieldLanguage.popularLanguages = Object.values(popularLanguages);
        }
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
    }

    currentLanguageName() {
        return OBFieldLanguage.languages[this.value];
    }

    async renderEdit() {
        render(html`<ob-field-select data-edit></ob-field-select>`, this.root);

        // set our field options if we don't have them already
        this.fieldSelect = this.root.querySelector("ob-field-select");
        if (!this.fieldSelect.options || !this.fieldSelect.options.length) {
            this.fieldSelect.options = OBFieldLanguage.languages;
            this.fieldSelect.popular = OBFieldLanguage.popularLanguages;
            this.fieldSelect.refresh();
        }

        if (this.initValue) {
            this.value = this.initValue;
            this.initValue = false;
        }
    }
}

customElements.define("ob-field-language", OBFieldLanguage);
