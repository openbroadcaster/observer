import { OBElement } from '../base/element.js';
import { html, render } from '../vendor.js';

class OBElementPreview extends OBElement {
    #init;

    connectedCallback() {
        if (this.#init) {
            return;
        }

        this.#init = true;

        this.addEventListener("dragstart", this.onDragStart.bind(this));
        this.addEventListener("dragend", this.onDragEnd.bind(this));

        this.renderComponent().then(() => {
            // stuff
        });
    }

    async renderComponent() {
        render(html`
            <div id="preview">
                <div id="drag" onmouseup=${this.onMouseUp.bind(this)}>
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
                    border: 2px solid rgba(0,0,0,0);
                    height: 196px;

                    &::after {
                        font-family: "Font Awesome 5 Free";
                        content: "\\f144"
                    }

                    &.dragging {
                        border: 2px dashed #e09529;
                    }
                }
            }
        `;
    }

    onDragStart(event) {
        this.root.querySelector("#drag").classList.add("dragging");
    }

    onDragEnd(event) {
        this.root.querySelector("#drag").classList.remove("dragging");
    }

    onMouseUp(event) {
        if (! window.dragHelperData || ! window.dragHelperData[0].classList.contains("sidebar_search_media_result")) {
            return false;
        }

        console.log(window.dragHelperData[0]);
    }
}

customElements.define('ob-element-preview', OBElementPreview);
