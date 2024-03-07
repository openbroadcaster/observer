import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldLanguage extends OBField {

  // languages are common to all instances of this element
  static languages = null;

  async connectedCallback() {
    if (OBFieldLanguage.languages === null) {

      // prevent multiple calls if this element appears twice in one form
      OBFieldLanguage.languages = {};

      const result = await OB.API.postPromise('metadata', 'language_list', {});

      if (!result.status) return false;

      // create an object linking language "id" with language "ref_name"
      for (const lang of result.data) {
        OBFieldLanguage.languages[lang.language_id] = lang.ref_name;
      }
    }

    this.renderComponent();
  }

  get value() {
    return this.fieldSelect?.value;
  }

  set value(value) {
    if (this.fieldSelect) {
      this.fieldSelect.value = value;
    }
    else { console.log('language field not ready'); }
  }

  currentLanguageName() {
    return OBFieldLanguage.languages[this.value];
  }

  async renderEdit() {
    render(html`<ob-field-select data-edit></ob-field-select>`, this.root);

    // set our field options if we don't have them already
    this.fieldSelect = this.root.querySelector('ob-field-select');
    if (!this.fieldSelect.options.length) {
      this.fieldSelect.options = OBFieldLanguage.languages;
      this.fieldSelect.refresh();
    }
  }
}

customElements.define('ob-field-language', OBFieldLanguage);
