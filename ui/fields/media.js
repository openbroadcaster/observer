import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldMedia extends OBField {

    #mediaItems;
    #mediaContent;
    #init;

    connectedCallback() {
        this.#mediaItems = [];
        this.#mediaContent = {};

        this.renderComponent();

        if (!this.#init) {
            this.#init = true;

            this.addEventListener("dragstart", this.onDragStart.bind(this));
            this.addEventListener("dragend", this.onDragEnd.bind(this));
        }
    }

    renderEdit() {
        render(html`
            <div id="media" class="media-editable" 
            data-single="${this.dataset.hasOwnProperty('single')}"
            data-record="${this.dataset.hasOwnProperty('single') && this.dataset.hasOwnProperty('record')}"
            onmouseup=${this.onMouseUp.bind(this)}>
                ${this.#mediaItems.map((mediaItem) => html`
                    <div class="media-item" data-id=${mediaItem}>
                        ${this.#mediaContent[mediaItem]}
                        <span class="media-item-remove" onclick=${this.mediaRemove.bind(this)}></span>
                    </div>
                `)}
            </div>
            ${
                (this.#mediaItems.length === 0 && this.dataset.hasOwnProperty('single') && this.dataset.hasOwnProperty('record'))
                ? html`<span class="media-record"><span class="button-record">⏺️</span><span class="button-stop">⏹️</span></span>`
                : html``
            }
        `, this.root);
    }

    renderView() {
        render(html`
            <div id="media" class="media-viewable">
                ${this.#mediaItems.map((mediaItem) => html`
                    <div class="media-item" data-id=${mediaItem}>${this.#mediaContent[mediaItem]}</div>
                `)}
            </div>
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

                .media-editable[data-single="true"]:empty::after {
                    content: "Drop Media Here (Single)";
                }

                .media-editable[data-record="true"]:empty::after {
                    content: "Drop Media Here or Press Record";
                }

                #media.media-editable.dragging {
                    border: 2px dashed #e09529;
                }

                .media-record {
                    position: relative;
                    right:48px;
                    top: 32px;
                    
                    .button-record, .button-stop {
                        filter: hue-rotate(180deg) brightness(1);
                        cursor: pointer;
                        
                        &:hover {
                            filter: hue-rotate(180deg) brightness(2);
                        }
                    }
                }

                .media-viewable:empty::after {
                    content: "No Media";
                    display: block;
                    text-align: center;
                    line-height: 96px;
                }
               
                .media-viewable[data-single="true"]:empty::after {
                    content: "No Media (Single)";
                }

                #media {
                    box-sizing: border-box;
                    width: 350px;
                    max-width: 350px;
                    min-height: 100px;
                    display: inline-block;
                }

                .media-item {
                    background-color: #eeeeec;
                    color: #000;
                    padding: 5px;
                    border-radius: 2px;
                    margin: 5px 0;
                    position: relative;
                    font-size: .9em;
                    box-sizing: border-box;
                    max-width: 350px;

                    .media-item-remove {
                        cursor: pointer;
                        padding-left: 10px;
                        padding-right:  10px;
                        color: #bf2121;
                        font-weight: bold;
                        position: absolute;
                        right: 0;
                    }

                    .media-item-remove::after {
                        content: "x";
                    }
                }
            }
        `;
    }

    onDragStart(event) {
        let editable = this.root.querySelector("#media.media-editable");

        if  (! editable) {
            return false;
        }

        editable.classList.add("dragging");
    }

    onDragEnd(event) {
        let editable = this.root.querySelector("#media.media-editable");

        if (! editable) {
            return false;
        }

        editable.classList.remove("dragging");
    }

    onMouseUp(event) {
        if (! window.dragHelperData || ! window.dragHelperData[0].classList.contains("sidebar_search_media_result")) {
            return false;
        }

        var selectedMedia = this.#mediaItems;

        Object.keys(window.dragHelperData).forEach((key) => {
            if (! window.dragHelperData[key].dataset) {
                return false;
            }

            if (selectedMedia.includes(parseInt(window.dragHelperData[key].dataset.id))) {
                return false;
            }

            selectedMedia.push(parseInt(window.dragHelperData[key].dataset.id));
        });

        this.value = selectedMedia;
    }

    async mediaContent() {
        return Promise.all(this.#mediaItems.map(async (mediaItem) => {
            if (this.#mediaContent[mediaItem]) {
                return;
            }

            const result = await OB.API.postPromise('media', 'get', { id: mediaItem });
            if (!result.status) {
                return;
            }

            const data = result.data;
            this.#mediaContent[mediaItem] = data.artist + " - " + data.title;
            this.refresh();
        }));
    }

    mediaRemove(event) {
        this.#mediaItems = this.#mediaItems.filter((item) => {
            return item !== parseInt(event.target.parentElement.dataset.id);
        });
        this.mediaContent().then(() => this.refresh());
    }

    get value() {
        return this.#mediaItems;
    }

    set value(value) {
        if (!Array.isArray(value)) {
            return false;
        }

        value = value.map((x) => parseInt(x));

        if (!value.every(Number.isInteger)) {
            return false;
        }

        if (this.dataset.hasOwnProperty('single')) {
            value = value.slice(-1);
        }

        this.#mediaItems = value;
        this.mediaContent().then(() => this.refresh());
    }
}

customElements.define('ob-field-media', OBFieldMedia);