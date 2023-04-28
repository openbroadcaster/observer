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
        return false;
      }

      self._langs = result.data;
    });

    const wrapperElem = document.createElement('div');
    const inputElem   = document.createElement('input');
    const langsElem   = document.createElement('div');
    const styleElem   = document.createElement('style');

    wrapperElem.setAttribute('class', 'wrapper');
    inputElem.setAttribute('type', 'text');
    langsElem.setAttribute('id', 'lang-autocomplete');
    // TODO: Figure out why including style in href isn't working so we can include
    // items in /ui/style(/scss).
    styleElem.textContent = `.wrapper {
      display: flex;
      flex-direction: column;
      max-width: 400px;
    }

    .wrapper div#lang-autocomplete p {
      margin: 0;
      padding: 0.5rem 0 0 0;
    }`;

    this._root.appendChild(styleElem);
    this._root.appendChild(wrapperElem);
    wrapperElem.appendChild(inputElem);
    wrapperElem.appendChild(langsElem);

    this.forwardAttributes(['placeholder', 'value'], inputElem);
    this.oninput = this.autocompleteLang;

    this._initialized = true;
  }

  autocompleteLang()
  {
    if (this.value.length >= 2) {
      const langs = this._langs.filter((lang) => lang.ref_name.toLowerCase().startsWith(this.value.toLowerCase()));
      const autocompleteElem = this._root.getElementById('lang-autocomplete');

      autocompleteElem.innerHTML = '';
      langs.forEach(function(elem) {
        const langElem = document.createElement('p');
        langElem.setAttribute('data-lang', elem.id);
        langElem.addEventListener('click', (event) => this.selectLang(event, this));
        langElem.innerHTML = elem.ref_name;

        autocompleteElem.appendChild(langElem);
      }, this);
    }
  }

  selectLang(event, element)
  {
    element.value = event.srcElement.innerText;
    element._root.getElementById('lang-autocomplete').innerHTML = '';
  }

  get value()
  {
    return this._root.querySelector('input').value;
  }

  set value(value)
  {
    this._root.querySelector('input').value = value;
  }
}

customElements.define('ob-input-languages', OBInputLanguages);
