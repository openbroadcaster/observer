import { OBElement } from '../base/element.js';
import { html, render } from '../vendor.js';

class OBElementPreview extends OBElement {
    #init;

    #itemId;
    #itemType;

    #imageWidth;
    #imageHeight;

    #videojsPlayer;

    connectedCallback() {
        if (this.#init) {
            return;
        }

        this.loadDeps().then(() => {
            this.#init = true;

            this.addEventListener("dragstart", this.onDragStart.bind(this));
            this.addEventListener("dragend", this.onDragEnd.bind(this));
    
            this.renderComponent().then(() => {
                this.#imageWidth = this.root.querySelector("#preview").offsetWidth;
                this.#imageHeight = this.root.querySelector("#preview").offsetHeight;
    
                this.root.querySelector("#drag").addEventListener("mouseup", this.onMouseUp.bind(this));
            });
        });
    }

    async loadDeps() {
        let style = document.createElement("link");
        style.rel = "stylesheet";
        style.href = "../../node_modules/video.js/dist/video-js.min.css";
        style.type = "text/css";
        this.root.appendChild(style);

        await new Promise((resolve, reject) => {
            style.onload = resolve();
            style.onerror = reject(new Error('Error loading video.js stylesheet'));
        });
    }

    async renderComponent() {
        if (this.root.querySelector("#drag audio, #drag video")) {
            let mediaElem = this.root.querySelector("#drag audio, #drag video");

            mediaElem.pause();
            mediaElem.setAttribute('src', '');
            mediaElem.removeAttribute('src');
        }

        if (this.#videojsPlayer) {
            this.#videojsPlayer.dispose();
            this.#videojsPlayer = null;
        }

        render(html`
            <div id="preview">
                <div id="drag">
                    ${this.#itemType === 'audio' ? html`
                        <video-js>
                            <source src="/preview.php?x=${new Date().getTime()}&id=${this.#itemId}&format=mp3" type="audio/mpeg" />
                            <source src="/preview.php?x=${new Date().getTime()}&id=${this.#itemId}&format=ogg" type="audio/ogg" />
                        </video-js>
                    ` : html``}
                    ${this.#itemType === 'video' ? html`
                        <video-js>
                            <source src="/preview.php?x=${new Date().getTime()}&id=${this.#itemId}&w=${this.#imageWidth}&h=${this.#imageHeight}&format=mp4" type="video/mp4" />
                            <source src="/preview.php?x=${new Date().getTime()}&id=${this.#itemId}&w=${this.#imageWidth}&h=${this.#imageHeight}&format=ogg" type="video/ogg" />
                        </video-js>
                    ` : html``}
                    ${this.#itemType === 'image' ? html`
                        <img src="/preview.php?x=${new Date().getTime()}&id=${this.#itemId}&w=${this.#imageWidth}&h=${this.#imageHeight}" />
                    ` : html``}
                </div>
            </div>
        `, this.root);

        const videoElem = this.root.querySelector("video-js");
        if (videoElem) {
            switch (this.#itemType) {
                case 'audio':
                    this.#videojsPlayer = videojs(videoElem, {
                        controls: true,
                        preload: "auto",
                        poster: "/preview.php?x=1714772011171&id=177&w=370&h=200",
                        audioPosterMode: true,
                    });
                    break;
                case 'video':
                    this.#videojsPlayer = videojs(videoElem, {
                        controls: true,
                        preload: "auto",
                    });
                    break;
            }
        }
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

                    img {
                        max-width: 100%;
                        max-height: 100%;
                    }
                }

                #drag {
                    color: #7f7d8b;
                    font-size: 6em;
                    line-height: 200px;
                    border-radius: 5px;
                    border: 2px solid rgba(0,0,0,0);
                    height: 200px;

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

                video-js {
                    width: 100%;
                    height: 100%;
                    border-radius: 5px;
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
