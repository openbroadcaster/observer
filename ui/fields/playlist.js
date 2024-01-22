import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldPlaylist extends OBField {

    #playlistItems;
    #playlistContent;
    #init;

    connectedCallback() {
        this.#playlistItems = [];
        this.#playlistContent = {};

        this.renderComponent();

        if (!this.#init) {
            this.#init = true;

            this.addEventListener("dragstart", this.onDragStart.bind(this));
            this.addEventListener("dragend", this.onDragEnd.bind(this));
        }
    }

    renderEdit() {
        render(html`
            <div id="playlist" class="playlist-editable"
            data-single="${this.dataset.hasOwnProperty('single')}"
            onmouseup=${this.onMouseUp.bind(this)}>
                ${this.#playlistItems.map((playlistItem) => html`
                    <div class="playlist-item" data-id=${playlistItem}>
                        ${this.#playlistContent[playlistItem]}
                        <span class="playlist-item-remove" onclick=${this.playlistRemove.bind(this)}></span>
                    </div>
                `)}
            </div>
        `, this.root);
    }

    renderView() {
        render(html`
            <div id="playlist" class="playlist-viewable">
                ${this.#playlistItems.map((playlistItem) => html`
                    <div class="media-item" data-id=${playlistItem}>${this.#playlistContent[playlistItem]}</div>
                `)}
            </div>
        `, this.root);
    }

    scss() {
        return `
            :host {
                #playlist:empty {
                    border: 2px dashed #eeeeec;
                }

                .playlist-editable:empty::after {
                    content: "Drop Playlists Here";
                    display: block;
                    text-align: center;
                    line-height: 96px;
                }

                .playlist-editable[data-single="true"]:empty::after {
                    content: "Drop Playlist Here";
                }

                #playlist.playlist-editable.dragging {
                    border: 2px dashed #e09529;
                }

                .playlist-viewable:empty::after {
                    content: "No Playlists";
                    display: block;
                    text-align: center;
                    line-height: 96px;
                }

                #playlist {
                    box-sizing: border-box;
                    width: 350px;
                    max-width: 350px;
                    min-height: 100px;
                    display: inline-block;
                }

                .playlist-item {
                    background-color: #eeeeec;
                    color: #000;
                    padding: 5px;
                    border-radius: 2px;
                    margin: 5px 0;
                    position: relative;
                    font-size: .9em;
                    box-sizing: border-box;
                    max-width: 350px;

                    .playlist-item-remove {
                        cursor: pointer;
                        padding-left: 10px;
                        padding-right:  10px;
                        color: #bf2121;
                        font-weight: bold;
                        position: absolute;
                        right: 0;
                    }

                    .playlist-item-remove::after {
                        content: "x";
                    }
                }
            }
        `;
    }

    onDragStart(event) {
        let editable = this.root.querySelector("#playlist.playlist-editable");

        if  (! editable) {
            return false;
        }

        editable.classList.add("dragging");
    }

    onDragEnd(event) {
        let editable = this.root.querySelector("#playlist.playlist-editable");

        if (! editable) {
            return false;
        }
        
        editable.classList.remove("dragging");
    }

    onMouseUp(event) {
        if (! window.dragHelperData || ! window.dragHelperData[0].classList.contains("sidebar_search_playlist_result")) {
            return false;
        }
        
        var selectedPlaylist = this.#playlistItems;

        Object.keys(window.dragHelperData).forEach((key) => {
            if (! window.dragHelperData[key].dataset) {
                return false;
            }

            if (selectedPlaylist.includes(parseInt(window.dragHelperData[key].dataset.id))) {
                return false;
            }

            selectedPlaylist.push(parseInt(window.dragHelperData[key].dataset.id));
        });

        this.value = selectedPlaylist;
    }

    async playlistContent() {
        return Promise.all(this.#playlistItems.map(async (playlistItem) => {
            if (this.#playlistContent[playlistItem]) {
                return;
            }

            const result = await OB.API.postPromise('playlists', 'get', { id: playlistItem });
            if (!result.status) {
                return;
            }

            const data = result.data;
            this.#playlistContent[playlistItem] = data.name;
            this.refresh();
        }));
    }

    playlistRemove(event) {
        const newItems = this.#playlistItems.filter((item) => {
            return item !== parseInt(event.target.parentElement.dataset.id);
        });
        this.value = newItems;
    }

    get value() {
        return this.#playlistItems;
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

        this.#playlistItems = value;
        this.playlistContent().then(() => {
            this.#playlistItems = this.#playlistItems.filter((item) => {
                return Object.keys(this.#playlistContent).includes(item.toString());
            });
            this.refresh();
        });
    }
}

customElements.define('ob-field-playlist', OBFieldPlaylist);