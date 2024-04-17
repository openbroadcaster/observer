import { OBElement } from '../base/element.js';
import { html, render } from '../vendor.js';

class OBElementPreview extends OBElement {
    #init;

    #itemId;
    #itemType;

    #imageWidth;
    #imageHeight;

    connectedCallback() {
        if (this.#init) {
            return;
        }

        this.#init = true;

        this.addEventListener("dragstart", this.onDragStart.bind(this));
        this.addEventListener("dragend", this.onDragEnd.bind(this));

        this.renderComponent().then(() => {
            this.#imageWidth = this.root.querySelector("#preview").offsetWidth;
            this.#imageHeight = this.root.querySelector("#preview").offsetHeight;
            console.log(this.#imageWidth, this.#imageHeight);
        });
    }

    async renderComponent() {
        render(html`
            <div id="preview">
                <div id="drag" onmouseup=${this.onMouseUp.bind(this)}>
                    ${this.#itemType === 'audio' ? html`
                        <audio preload="auto" autoplay="autoplay" controls="controls">
                            <source src="/preview.php?x='somedata'&id=${this.#itemId}&format=mp3" type="audio/mpeg" />
                            <source src="/preview.php?x='somedata'&id=${this.#itemId}&format=ogg" type="audio/ogg" />
                        </audio>
                    ` : html``}
                    ${this.#itemType === 'video' ? html`
                        <video preload="auto" autoplay="autoplay" controls="controls">
                            <source src="/preview.php?x='somedata'&id=${this.#itemId}&w=${this.#imageWidth}&h=${this.#imageHeight}&format=mp4" type="video/mp4" />
                            <source src="/preview.php?x='somedata'&id=${this.#itemId}&w=${this.#imageWidth}&h=${this.#imageHeight}&format=ogg" type="video/ogg" />
                        </video>
                    ` : html``}
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

                    video, audio {
                        width: 360px;
                        max-height: 196px;
                        display: inline-block;
                        vertical-align: baseline;
                    }
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

                    &:not(:empty)::after {
                        content: "";
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

        this.#itemId = window.dragHelperData[0].dataset.id;
        this.#itemType = window.dragHelperData[0].dataset.type;
        
        this.renderComponent();
    }
}

customElements.define('ob-element-preview', OBElementPreview);
