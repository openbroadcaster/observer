import { OBElement } from '../base/element.js';
import { html, render } from '../vendor.js';

class OBElementPreview extends OBElement {
    #init;

    connectedCallback() {
        if (this.#init) {
            return;
        }

        this.#init = true;
        this.renderComponent().then(() => {
            // stuff
        });
    }

    async renderComponent() {
        render(html`
            <div id="preview">
                <div id="drag">
                </div>
            </div>
        `, this.root);
    }
    
    scss() {
        return `
            :host {
                display: inline-block;

                #preview {
                    text-align: center;
                    height: 200px;
                    background-color: rgba(0, 0, 0, 0.3);
                    border-radius: 5px;
                    scrollbar-color: rgba(0,0,0,0) rgba(0,0,0,0);
                    width: 370px;
                }

                #drag {
                    color: #7f7d8b;
                    font-size: 6em;
                    line-height: 196px;
                    border-radius: 5px;
                    border-width: 2px;
                    height: 196px;

                    &::after {
                        font-family: "Font Awesome 5 Free";
                        content: "\\f144"
                    }
                }
            }
        `;
    }
}

customElements.define('ob-element-preview', OBElementPreview);
