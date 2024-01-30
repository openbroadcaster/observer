import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldMedia extends OBField {

    #mediaItems;
    #mediaContent;

    #mediaRecorder;
    #recordData;
    #recordUrl;

    #init;

    connectedCallback() {
        this.#mediaItems    = [];
        this.#mediaContent  = {};

        this.#recordData    = [];
        this.#recordUrl     = "";
        this.#mediaRecorder = null;

        this.renderComponent();

        if (!this.#init) {
            this.#init = true;

            this.addEventListener("dragstart", this.onDragStart.bind(this));
            this.addEventListener("dragend", this.onDragEnd.bind(this));

            if (this.dataset.hasOwnProperty('single') && this.dataset.hasOwnProperty('record')) {
                if (navigator.mediaDevices.getUserMedia) {
                    var self = this;
                    let onSuccess = function (stream) {
                        self.#mediaRecorder = new MediaRecorder(stream);
                        self.#mediaRecorder.ondataavailable = function (e) {
                            self.#recordData.push(e.data);
                        }
                    }

                    let onError = function (stream) {
                        // TODO: Update element look
                        console.error(`The following getUserMedia error occurred: ${err}`);
                    }

                    this.#mediaRecorder = navigator.mediaDevices.getUserMedia({audio: true}).then(onSuccess, onError);
                } else {
                    // TODO: Update element look.
                    console.error('getUserMedia not supported on your browser!');
                }
            }
        }
    }

    renderEdit() {
        render(html`
            <div id="media" class="media-editable" 
            data-single="${this.dataset.hasOwnProperty('single')}"
            data-record="${this.dataset.hasOwnProperty('single') && this.dataset.hasOwnProperty('record')}"
            data-status="${this.dataset.hasOwnProperty('status') ? this.dataset.status : 'none'}"
            onmouseup=${this.onMouseUp.bind(this)}>
                ${this.#mediaItems.map((mediaItem) => html`
                    <div class="media-item" data-id=${mediaItem}>
                        ${this.#mediaContent[mediaItem]}
                        <span class="media-item-remove" onclick=${this.mediaRemove.bind(this)}></span>
                    </div>
                `)}
                ${
                    this.dataset.status === "cached"
                    ? html`<audio id="audio" controls src=${this.#recordUrl}></audio>`
                    : html``
                }
            </div>
            ${
                (this.#mediaItems.length === 0 && this.dataset.hasOwnProperty('single') && this.dataset.hasOwnProperty('record'))
                ? html`<span class="media-record" data-status="${this.dataset.hasOwnProperty('status') ? this.dataset.status : 'none'}">
                    <span class="button-save" onclick=${this.mediaRecordSave.bind(this)}>💾</span>
                    <span class="button-record" onclick=${this.mediaRecordStart.bind(this)}>⏺️</span>
                    <span class="button-stop" onclick=${this.mediaRecordStop.bind(this)}>⏹️</span>
                </span>`
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

                .media-editable[data-record="true"][data-status="recording"]::after {
                    content: "Recording...";
                }

                .media-editable[data-record="true"][data-status="cached"]::after {
                    //content: "Recorded Media. Click to save.";
                }

                #media.media-editable.dragging {
                    border: 2px dashed #e09529;
                }

                .media-record {
                    position: relative;
                    right: 60px;
                    top: 32px;

                    font-size: 22px;
                }

                .media-record[data-status="none"] {
                    right: 36px; 

                    .button-save, .button-stop {
                        display: none;
                    }

                    .button-record {
                        filter: hue-rotate(180deg) brightness(1);
                        cursor: pointer;
                        
                        &:hover {
                            filter: hue-rotate(180deg) brightness(2);
                        }
                    }
                }

                .media-record[data-status="recording"] {
                    right: 36px; 

                    .button-record, .button-save {
                        display: none;
                    }

                    .button-stop {
                        filter: hue-rotate(180deg) brightness(1);
                        cursor: pointer;

                        &:hover {
                            filter: hue-rotate(180deg) brightness(2);
                        }
                    }
                }

                .media-record[data-status="cached"] {
                    .button-save, .button-record {
                        filter: hue-rotate(180deg) brightness(1);
                        cursor: pointer;
                        
                        &:hover {
                            filter: hue-rotate(180deg) brightness(2);
                        }
                    }

                    .button-stop {
                        display: none;
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
        const newItems = this.#mediaItems.filter((item) => {
            return item !== parseInt(event.target.parentElement.dataset.id);
        });
        this.value = newItems;
    }

    mediaRecordStart(event) {
        if (this.#mediaRecorder.state !== "inactive") {
            return false;
        }

        if (this.dataset.status === "cached") {
            OB.UI.confirm("Are you sure you want to overwrite the existing recording?", () => {
                this.dataset.status = "none";
                this.mediaRecordStart(event);
            }, "Yes", "No");
        } else {
            this.#recordData = [];
            this.dataset.status = "recording";
            this.#mediaRecorder.start();
            this.refresh();
        }
    }

    mediaRecordStop(event) {
        if (this.#mediaRecorder.state !== "recording") {
            return false;
        }

        this.#mediaRecorder.onstop = () => {
            const blob = new Blob(this.#recordData, { type: this.#mediaRecorder.mimeType });
            const audioURL = window.URL.createObjectURL(blob);
            this.#recordUrl = audioURL;

            this.dataset.status = "cached";
            this.refresh();
        }
        this.#mediaRecorder.stop();
    }

    mediaRecordSave(event) {
        if (this.dataset.status !== "cached") {
            return false;
        }

        const blob = new Blob(this.#recordData, { type: this.#mediaRecorder.mimeType });
        fetch('/upload.php', {
            method: 'POST',
            body: blob
        }).then((response) => {
            return response.json();
        }).then((data) => {
            const fileKey = data.file_key;
            const fileId  = data.file_id;
            const date    = new Date();
            const dateStr = date.getFullYear() + "-" + ('0' + date.getMonth() + 1).slice(-2) + "-" + ('0' + date.getDate()).slice(-2);

            const media = OB.API.postPromise('media', 'save', {
                media: {
                    0: {
                        file_id: fileId,
                        file_key: fileKey,
                        artist: OB.Account.userdata.display_name,
                        title: "Media Field Recording " + dateStr,
                        local_id: 1,
                        status: "private",
                        language: 25571,
                        is_copyright_owner: 1,
                        is_approved: 1,
                        dynamic_select: 0
                    }
                }
            });
            media.then((data) => {
                if (! data.status) {
                    console.error(data.msg);
                } else {
                    this.value = data.data;
                }
            });
        }).catch((error) => {
            console.error(error);
        });
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

        this.dataset.status = "none";
        this.#recordUrl = "";
        this.#recordData = [];

        this.#mediaItems = value;
        this.mediaContent().then(() => {
            this.#mediaItems = this.#mediaItems.filter((item) => {
                return Object.keys(this.#mediaContent).includes(item.toString());
            });
            this.refresh();
        });
    }
}

customElements.define('ob-field-media', OBFieldMedia);