import { html, render } from '../vendor.js'
import { OBInput } from '../base/field.js';

class OBInputLanguage extends OBInput {

  // languages are common to all instances of this element
  static languages = null;

  // private properties now supported with #
  #root;
  #inputLangId = null;
  #suggestions = [];

  constructor() {
    super();
    this.#root = this.attachShadow({ mode: 'open' });
  }

  connectedCallback() {

    console.log('connected callback languages');

    if (OBInputLanguage.languages === null) {

      // prevent multiple calls if this element appears twice in one form
      OBInputLanguage.languages = [];

      OB.API.post('metadata', 'language_list', {}, function (result) {
        if (!result.status) {
          return false;
        }

        OBInputLanguage.languages = result.data;
      });
    }

    this.renderComponent();
  }

  renderComponent() {
    render(html`
      <style>
        .wrapper {
          display: flex;
          flex-direction: column;
        }
        .suggestions
        {
          max-height: 200px;
          overflow-y: auto;
        }
        .suggestions div {
          cursor: default;
          margin: 5px 0;
        }
      </style>

      <div class="wrapper">
        <input type="text" onInput=${this.onInput.bind(this)} />
        <div class="suggestions">
          ${this.#suggestions.map((lang) => html`<div data-lang=${lang.id} onClick=${() => this.selectSuggestion(lang.id)}>${lang.ref_name}</div>`)}
        </div>
      </div>
    `, this.#root);
  }

  onInput(event) {
    const value = event.target.value;
    if (value.length >= 2) {
      this.#suggestions = OBInputLanguage.languages.filter((lang) => lang.ref_name.toLowerCase().startsWith(value.toLowerCase()));
    } else {
      this.#suggestions = [];
    }
    this.renderComponent();
  }

  selectSuggestion(langId) {
    this.#suggestions = [];
    this.value = langId;
  }

  get value() {
    return this.#inputLangId;
  }

  set value(value) {
    const lang = OBInputLanguage.languages.find((lang) => lang.id === value);

    if (lang) {
      // input field gets language name, but we track language ID to return when value is requested
      this.#root.querySelector('input').value = lang.ref_name;
      this.#inputLangId = value;
    }
    else {
      // set blank / null, language empty or not found
      this.#root.querySelector('input').value = '';
      this.#inputLangId = null;
    }

    this.renderComponent();
  }
}

customElements.define('ob-input-language', OBInputLanguage);
