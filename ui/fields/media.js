import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldMedia extends OBField {
    connectedCallback() {
        this.renderComponent();
    }

    renderEdit() {
        render(html`
            <div id="media" class="media-editable"></div>
        `, this.root);
    }

    renderView() {
        render(html`
            <div id="media" class="media-viewable"></div>
        `, this.root);
    }

    scss() {
        return `
            :host {
                #media:empty {
                    border: 2px dashed #eeeeec;
                }

                .media-editable:empty::after {
                    content: "Drop Media Here";
                    display: block;
                    text-align: center;
                    line-height: 96px;
                }

                .media-viewable:empty::after {
                    content: "No Media";
                    display: block;
                    text-align: center;
                    line-height: 96px;
                }

                #media {
                    box-sizing: border-box;
                    width: 350px;
                    max-width: 350px;
                    min-height: 100px;
                    display: inline-block;
                }
            }
        `;
    }

    get value() {
        // TODO
        return null;
    }

    set value(value) {
        // TODO
        this.refresh();
    }
}

customElements.define('ob-field-media', OBFieldMedia);