import { render, html } from './vendor.js';
import { OBInput } from './Input.js';

class OBInputLanguages extends OBInput
{
  constructor()
  {
    super();

    this._langs = null;
    this._root = this.attachShadow({mode: 'open'});
    this._input = null;
    this._initialized = false;
  }

  connectedCallback()
  {
    if (this._initialized) {
      return;
    }

    const self = this;
    OB.API.post('metadata', 'language_list', {}, function(result) {
      if (!result.status) {
        render(html`
          <div>Failed to load languages.</div>
        `, this._root);
        return false;
      }

      self._langs = result.data;
    });

    render(html`
      <input type="text" oninput=${() => this.autocompleteLang()} ref=${(el) => this._input = el}/>
      <div id="lang-autocomplete"></div>
    `, this._root);

    this.forwardAttributes(['placeholder', 'value'], this._input);
    this.emitEvents(['change'], this._input);

    this._initialized = true;
  }

  autocompleteLang()
  {
    if (this.value.length >= 2) {
      //console.log(this.value);
      const langs = this._langs.filter((lang) => lang.ref_name.toLowerCase().startsWith(this.value.toLowerCase()));
      const autocompleteHtml = this._root.getElementById('lang-autocomplete');

      var newHtml = ''; // setting div directly inside shadowroot causes weird behavior(?)
      langs.forEach(function(elem) {
        // TODO: figure out how to get selectLang in those elements somehow? May involve render()
        // again
        newHtml = newHtml + '<p>' + elem.ref_name + '</p>';
        console.log(html`<p onclick=${() => this.selectLang()}>${elem.ref_name}</p>`);
      });

      autocompleteHtml.innerHTML = newHtml;
    }
  }

  selectLang()
  {
    console.log("beep");
  }

  get value()
  {
    return this._input.value;
  }

  set value(value)
  {
    this._input.value = value;
  }
}

customElements.define('ob-input-languages', OBInputLanguages);
