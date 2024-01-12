import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldMedia extends OBField {

    #mediaItems;
    #mediaContent;

    connectedCallback() {
        this.#mediaItems = [];
        this.#mediaContent = {};

        this.renderComponent();

        document.addEventListener("dragstart", this.onDragStart.bind(this));
        document.addEventListener("dragend", this.onDragEnd.bind(this))
    }

    renderEdit() {
        render(html`
            <div id="media" class="media-editable" 
            ondragover=${this.onDragOver.bind(this)}
            ondrop=${this.onDrop.bind(this)}>
                ${this.#mediaItems.map((mediaItem) => html`
                    <div class="media-item" data-id=${mediaItem}>
                        ${this.#mediaContent[mediaItem]}
                        <span class="media-item-remove" onclick=${this.mediaRemove.bind(this)}></span>
                    </div>
                `)}
            </div>
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

                #media.media-editable.dragging {
                    border: 2px dashed #e09529;
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

    onDragOver(event) {
        event.preventDefault();
    }

    onDragStart(event) {
        // Remove other selected elements if the element hasn't already been selected AND 
        // shift or ctrl isn't being held down.
        if (! event.target.classList.contains("sidebar_search_media_selected") && ! event.shiftKey && ! event.ctrlKey) {
            document.querySelectorAll(".sidebar_search_media_selected").forEach((element) => {
                element.classList.remove("sidebar_search_media_selected");
            });
        }

        // Add item currently being dragged to selected media items.
        if (event.target.classList.contains("sidebar_search_media_result")) {
            event.target.classList.add("sidebar_search_media_selected");
        }

        let editable = this.root.querySelector("#media.media-editable");
        editable.classList.add("dragging");
    }

    onDragEnd(event) {
        let editable = this.root.querySelector("#media.media-editable");
        editable.classList.remove("dragging");
    }

    onDrop(event) {
        event.preventDefault();

        var selectedMedia = this.#mediaItems;

        document.querySelectorAll(".sidebar_search_media_selected").forEach((element) => {
            if (selectedMedia.includes(parseInt(element.dataset.id))) {
                return false;
            }
            
            selectedMedia.push(parseInt(element.dataset.id));
        });

        this.#mediaItems = selectedMedia;
        this.mediaContent().then(() => this.refresh());
    }

    async mediaContent() {
        return Promise.all(this.#mediaItems.map(async (mediaItem) => {
            if (this.#mediaContent[mediaItem]) {
                return;
            }

            const result = await OB.API.postPromise('media', 'get', {id: mediaItem});
            if (! result.status) {
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
        if (! Array.isArray(value)) {
            return false;
        }

        value = value.map((x) => parseInt(x));

        if (! value.every(Number.isInteger)) {
            return false;
        }

        this.#mediaItems = value;
        this.mediaContent().then(() => this.refresh());
    }
}

customElements.define('ob-field-media', OBFieldMedia);