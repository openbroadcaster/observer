import { html, render } from '../vendor.js'
import { OBInput } from '../base/field.js';

class OBInputText extends OBInput {
  constructor() {
    super();
    this._root = this.attachShadow({ mode: 'open' });
    this._input = null;
    this._initialized = false;
  }

  connectedCallback() {

    if (this._initialized) {
      return;
    }

    // see how this works: https://github.com/developit/htm
    render(html`
      <input
        type="text"
        ref=${(el) => this._input = el}
      />
    `, this._root);

    // forward attributes (see Input.js) from the custom element to the input
    this.forwardAttributes(['placeholder', 'value'], this._input);

    // emit change (see Input.js) from the input, back out from the custom element
    this.emitEvents(['change'], this._input);

    // set to initialized so we don't re-render
    this._initialized = true;
  }

  get value() {
    return this._input.value;
  }

  set value(newValue) {
    // change the input value
    this._input.value = newValue;
  }
}

customElements.define('ob-input-text', OBInputText);