import { html, render } from '../vendor.js';
import { OBElement } from '../base/element.js';

export class OBField extends OBElement {
  async connectedCallback(renderComponent) {
    if (renderComponent !== false) await this.renderComponent();
  }

  renderView() {
    render(html`
            ${this.value}
        `, this.root);
  }

  renderEdit() {
    render(html`<input onChange=${this.handleChange} type="text" value="${this.value}" />`, this.root);
  }

  handleChange = (event) => {
    this.value = event.target.value;
  }

  async renderComponent() {
    const edit = this.hasAttribute('data-edit');
    if (edit) this.renderEdit();
    else this.renderView();
  }
}