import { OBElement } from '../base/element.js';
import { html, render } from '../vendor.js';

class OBElementPreview extends OBElement {
    #init;

    #itemId;
    #itemType;
    #queue;

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
                            <source src="/preview.php?x=${new Date().getTime()}&id=${this.#queue[this.#itemId].id}&format=mp3" type="audio/mpeg" />
                            <source src="/preview.php?x=${new Date().getTime()}&id=${this.#queue[this.#itemId].id}&format=ogg" type="audio/ogg" />
                        </video-js>
                    ` : html``}
                    ${this.#itemType === 'video' ? html`
                        <video-js>
                            <source src="/preview.php?x=${new Date().getTime()}&id=${this.#queue[this.#itemId].id}&w=${this.#imageWidth}&h=${this.#imageHeight}&format=mp4" type="video/mp4" />
                            <source src="/preview.php?x=${new Date().getTime()}&id=${this.#queue[this.#itemId].id}&w=${this.#imageWidth}&h=${this.#imageHeight}&format=ogg" type="video/ogg" />
                        </video-js>
                    ` : html``}
                    ${this.#itemType === 'image' ? html`
                        <img src="/preview.php?x=${new Date().getTime()}&id=${this.#queue[this.#itemId].id}&w=${this.#imageWidth}&h=${this.#imageHeight}" />
                    ` : html``}
                </div>
                <div id="current">
                    ${this.#queue && this.#queue[this.#itemId] && html`
                        <span>${this.#queue[this.#itemId].artist} - ${this.#queue[this.#itemId].title}</span>
                    `}
                </div>
                <div id="queue">
                    ${this.#queue && this.#queue.map((queueItem, index) => html`
                        <span data-id=${index}>${queueItem.artist} - ${queueItem.title}</span>
                    `)}
                </div>
            </div>
        `, this.root);

        const videoElem = this.root.querySelector("video-js");
        if (videoElem) {
            switch (this.#itemType) {
                case 'audio':
                    let thumbnailId = this.#queue[this.#itemId].id;
                    let thumbnailLink = "/preview.php?x=" + (new Date().getTime()) + "&id=" + thumbnailId + "&thumbnail=1";
                    let validThumbnail = (await fetch(thumbnailLink)).ok
                    if (!validThumbnail) {
                        thumbnailLink = "/images/circle.svg";
                    }

                    this.#videojsPlayer = videojs(videoElem, {
                        controls: true,
                        preload: "auto",
                        poster: thumbnailLink,
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
        if (! window.dragHelperData) {
            return false;
        }

        this.#queue = [];
        this.#itemId = null;
        this.#itemType = null;

        if (window.dragHelperData[0].classList.contains("sidebar_search_playlist_result")) {
            let data = {
                "id": window.dragHelperData[0].dataset.id
            };
            let elem = this;
            OB.API.post("playlists", "resolve", data, function (response) {
                if (response.data) {
                    response.data.forEach((item) => {
                        let queueItem = {
                            "id": item.id,
                            "type": item.media_type,
                            "title": item.title,
                            "artist": item.artist,
                        };
                        elem.#queue.push(queueItem);
                    });

                    if (elem.#queue.length > 0) {
                        elem.#itemId = 0;
                        elem.#itemType = elem.#queue[0].type;
                    }

                    elem.renderComponent();
                }
            });
        } else if (window.dragHelperData[0].classList.contains("sidebar_search_media_result")) {
            Object.values(window.dragHelperData).forEach((item) => {
                if (! item.dataset) {
                    return;
                }

                let queueItem = {
                    "id": item.dataset.id,
                    "type": item.dataset.type,
                    "title": item.dataset.title,
                    "artist": item.dataset.artist,
                };
                this.#queue.push(queueItem);
            });

            if (this.#queue.length > 0) {
                this.#itemId = 0
                this.#itemType = this.#queue[0].type;
            }
            
            this.renderComponent();
        }
    }
}

customElements.define('ob-element-preview', OBElementPreview);
