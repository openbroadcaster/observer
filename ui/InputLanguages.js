import { html, render } from './vendor.js'
import { OBInput } from './Input.js';

class OBInputLanguages extends OBInput {

  // languages are common to all instances of this element
  static languages = null;

  constructor() {
    super();
    this._root = this.attachShadow({ mode: 'open' });
    this._input = null;
    this._inputLangId = null;
    this._suggestions = []
  }

  connectedCallback() {
    if (OBInputLanguages.languages === null) {

      // prevent multiple calls if this element appears twice in one form
      OBInputLanguages.languages = [];

      OB.API.post('metadata', 'language_list', {}, function (result) {
        if (!result.status) {
          return false;
        }

        OBInputLanguages.languages = result.data;
      });
    }
    else {
      this.renderComponent();
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
          ${this._suggestions.map((lang) => html`<div data-lang=${lang.id} onClick=${() => this.selectSuggestion(lang.id)}>${lang.ref_name}</div>`)}
        </div>
      </div>
    `, this._root);
  }

  onInput(event) {
    const value = event.target.value;
    if (value.length >= 2) {
      this._suggestions = OBInputLanguages.languages.filter((lang) => lang.ref_name.toLowerCase().startsWith(value.toLowerCase()));
    } else {
      this._suggestions = [];
    }
    this.renderComponent();
  }

  selectSuggestion(langId) {
    this._suggestions = [];
    this.value = langId;
  }

  get value() {
    return this._inputLangId;
  }

  set value(value) {
    const lang = OBInputLanguages.languages.find((lang) => lang.id === value);

    if (lang) {
      // input field gets language name, but we track language ID to return when value is requested
      this._root.querySelector('input').value = lang.ref_name;
      this._inputLangId = value;
    }
    else {
      // set blank / null, language empty or not found
      this._root.querySelector('input').value = '';
      this._inputLangId = null;
    }

    this.renderComponent();
  }
}

customElements.define('ob-input-languages', OBInputLanguages);
