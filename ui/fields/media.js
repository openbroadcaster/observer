import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldMedia extends OBField {

    #mediaItems;
    #mediaContent;

    #mediaRecorder;
    #recordData;
    #recordUrl;

    #blob;
    #playback;
    #duration;
    #waveformWidth;
    #waveformHeight;
    #trimStart;
    #trimEnd;

    #init;

    connectedCallback() {
        this.#mediaItems    = [];
        this.#mediaContent  = {};

        this.#recordData    = [];
        this.#recordUrl     = "";
        this.#mediaRecorder = null;

        this.#blob           = null;
        this.#playback       = null;
        this.#duration       = 0.0;
        this.#waveformWidth  = 350;
        this.#waveformHeight = 100;
        this.#trimStart      = 0.0;
        this.#trimEnd        = 0.0;

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
                    ? html`
                        <canvas height="100" width="350" id="waveform"></canvas>
                        <canvas style="display: none;" height="100" width="350" id="waveform-unedited"></canvas>
                        <audio style="display: none;" id="audio" controls src=${this.#recordUrl}></audio>
                    `
                    : html``
                }
            </div>
            ${
                (this.#mediaItems.length === 0 && this.dataset.hasOwnProperty('single') && this.dataset.hasOwnProperty('record'))
                ? html`<span class="media-record" data-status="${this.dataset.hasOwnProperty('status') ? this.dataset.status : 'none'}">
                    <div class="trim-container">
                        <span class="trim">Trim Start</span>
                        <input type="number" class="trim" id="trim-start" value="0" step="0.1" min="0" max="100" onchange=${this.drawTrimStart.bind(this)} />
                        <span class="trim">Trim End</span>
                        <input type="number" class="trim" id="trim-end" value="0" step="0.1" min="0" max="100" onchange=${this.drawTrimEnd.bind(this)} />
                    </div>
                    <div class="button-container">
                        <span class="button-play" onclick=${this.mediaRecordPlay.bind(this)}>▶️</span>
                        <span class="button-save" onclick=${this.mediaRecordSave.bind(this)}>💾</span>
                        <span class="button-record" onclick=${this.mediaRecordStart.bind(this)}>⏺️</span>
                        <span class="button-stop" onclick=${this.mediaRecordStop.bind(this)}>⏹️</span>
                    </div>
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
                    /*position: relative;
                    right: 88px;
                    top: 32px;*/
                    display: flex;
                    justify-content: space-between;
                    max-width: 350px;

                    .trim-container {
                        align-self: center;
                        font-size: 14px;

                        .trim {
                            margin-right: 0.5em;
                        }
                    }

                    .button-container {
                        font-size: 20px;
                    }
                }

                .media-record[data-status="none"] {
                    /*right: 36px; */

                    .button-save, .button-stop, .button-play, .trim {
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
                    /*right: 36px; */

                    .button-record, .button-save, .button-play, .trim {
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
                    /*top: 26px;*/

                    .button-save, .button-record, .button-play {
                        filter: hue-rotate(180deg) brightness(1);
                        cursor: pointer;
                        
                        &:hover {
                            filter: hue-rotate(180deg) brightness(2);
                        }
                    }

                    input.trim {
                        width: 50px;
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
                    /*min-height: 100px;*/
                    display: inline-block;

                    audio {
                        width: 350px;
                    }
                }

                #media[data-single="true"][data-status="none"] {
                    /*min-height: 38px;*/
                }

                #media[data-single="true"][data-status="cached"] {
                    /*min-height: 180px;*/
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

    mediaTrimBuffer(buffer, trimStart, trimEnd) {
        const rate = buffer.sampleRate;
        const duration = buffer.duration;
        const startOffset = trimStart * rate;
        const endOffset = (duration - trimEnd) * rate;
        const frameCount = endOffset - startOffset;

        var ctx = new window.AudioContext();
        const trimmedBuffer = ctx.createBuffer(1, frameCount, rate);
        const sourceData = buffer.getChannelData(0).subarray(startOffset, endOffset);
        trimmedBuffer.getChannelData(0).set(sourceData);

        return trimmedBuffer;
    }

    mediaRecordPlay(event) {
        var ctx = new window.AudioContext();
        this.#blob.arrayBuffer().then((arrayBuffer) => {
            ctx.decodeAudioData(arrayBuffer).then((audioBuffer) => {
                const trimStart = this.root.querySelector('#trim-start').value;
                const trimEnd = this.root.querySelector('#trim-end').value;
                const trimmedBuffer = this.mediaTrimBuffer(audioBuffer, trimStart, trimEnd);

                if (this.#playback !== null) {
                    this.#playback.stop();
                }

                this.#playback = ctx.createBufferSource();
                this.#playback.buffer = trimmedBuffer;
                this.#playback.connect(ctx.destination);
                this.#playback.start();
            });
        });
    }

    mediaRemove(event) {
        const newItems = this.#mediaItems.filter((item) => {
            return item !== parseInt(event.target.parentElement.dataset.id);
        });
        this.value = newItems;
    }

    mediaRecordStart(event) {
        if (this.#mediaRecorder === null && this.dataset.hasOwnProperty('single') && this.dataset.hasOwnProperty('record')) {
            if (navigator.mediaDevices.getUserMedia) {
                var self = this;
                let onSuccess = function (stream) {
                    self.#mediaRecorder = new MediaRecorder(stream, {
                        mimetype: "audio/webm"
                    });
                    self.#mediaRecorder.ondataavailable = function (e) {
                        self.#recordData.push(e.data);
                    }

                    // 250ms timeout because browsers may not be ready to record immediately 
                    // after creating MediaRecorder, causing a small gap in the initial recording
                    setTimeout(() => self.mediaRecordStart(event), "250");
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
        } else {
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
                this.#trimStart = 0.0;
                this.#trimEnd = 0.0;
                this.dataset.status = "recording";
                this.#mediaRecorder.start();
                this.refresh();
            }
        }
    }

    mediaRecordStop(event) {
        if (this.#mediaRecorder.state !== "recording") {
            return false;
        }

        this.#mediaRecorder.onstop = () => {
            const blob = new Blob(this.#recordData, { type: "audio/webm" });
            const audioURL = window.URL.createObjectURL(blob);
            this.#recordUrl = audioURL;
            this.#blob = blob;

            this.dataset.status = "cached";
            this.refresh().then(() => {
                this.drawWaveform();
            });
        }
        this.#mediaRecorder.stop();
        this.#mediaRecorder.stream.getTracks().forEach(track => track.stop());
        this.#mediaRecorder = null;
    }

    mediaRecordSave(event) {
        if (this.dataset.status !== "cached") {
            return false;
        }

        fetch('/upload.php', {
            method: 'POST',
            body: this.#blob
        }).then((response) => {
            return response.json();
        }).then((data) => {
            const fileKey = data.file_key;
            const fileId  = data.file_id;
            const date    = new Date();
            const dateStr = date.getFullYear() + "-" + ('0' + (date.getMonth() + 1)).slice(-2) + "-" + ('0' + date.getDate()).slice(-2);

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
                        dynamic_select: 0,
                        trim_start: this.root.querySelector('#trim-start').value,
                        trim_end: this.root.querySelector('#trim-end').value
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

    drawTrimStart(event) {
        const trim = this.root.querySelector('#trim-start').value;
        const trimFrac = trim / this.#duration;
        this.#trimStart = trimFrac * this.#waveformWidth;
        this.modifyWaveform();
    }

    drawTrimEnd(event) {
        const trim = this.root.querySelector('#trim-end').value;
        const trimFrac = trim / this.#duration;
        this.#trimEnd = trimFrac * this.#waveformWidth;
        this.modifyWaveform();
    }

    modifyWaveform() {
        const canvas = this.root.querySelector('#waveform');
        const canvasCtx = canvas.getContext('2d');
        canvasCtx.clearRect(0, 0, this.#waveformWidth, this.#waveformHeight);
        canvasCtx.drawImage(this.root.querySelector('#waveform-unedited'), 0, 0);

        canvasCtx.fillStyle = 'rgba(51, 51, 204, 0.8)';
        canvasCtx.fillRect(0, 0, this.#trimStart, this.#waveformHeight);
        canvasCtx.fillRect(this.#waveformWidth - this.#trimEnd, 0, this.#trimEnd, this.#waveformHeight);
    }

    drawWaveform() {
        const canvas = this.root.querySelector('#waveform');
        const canvasCtx = canvas.getContext('2d');

        var ctx = new window.AudioContext();
        this.#blob.arrayBuffer().then((arrayBuffer) => {
            ctx.decodeAudioData(arrayBuffer).then((audioBuffer) => {
                const height = this.#waveformHeight;
                const width = this.#waveformWidth;
                const rawData = audioBuffer.getChannelData(0);
                const samples = 10000;
                const blockSize = Math.floor(rawData.length / samples);

                this.#duration = audioBuffer.duration;

                const filteredData = [];
                var max = 0;
                for (let i = 0; i < samples; i++) {
                    let blockStart = blockSize * i;
                    let sum = 0;
                    for (let j = 0; j < blockSize; j++) {
                        sum = sum  + Math.abs(rawData[blockStart + j]);
                    }
                    if (max < (sum / blockSize)) {
                        max = sum / blockSize;
                    }
                    filteredData.push(sum / blockSize);
                }

                canvasCtx.clearRect(0, 0, width, height);
                canvasCtx.fillStyle = "rgb(200, 200, 200)";
                canvasCtx.fillRect(0, 0, width, height);
        
                canvasCtx.lineWidth = 0.1;
                canvasCtx.strokeStyle = "rgb(0, 0, 0)";
        
                canvasCtx.beginPath();
        
                const sliceWidth = (width * 1.0) / samples;
                let x = 0;

                for (let i = 0; i < samples; i++) {
                    const v = filteredData[i];
                    const y = ((v / max) * (height / 2)) + (height / 2);
                    const yNeg = ((-v / max) * (height / 2)) + (height / 2);

                    canvasCtx.moveTo(x, height / 2);
                    canvasCtx.lineTo(x, y);
                    canvasCtx.moveTo(x, height / 2);
                    canvasCtx.lineTo(x, yNeg);

                    x += sliceWidth;
                }

                canvasCtx.stroke();
                
                // copy to waveform-unedited to use for overwriting any potential changes 
                // from trim
                const canvasUnedited = this.root.querySelector('#waveform-unedited');
                const canvasCtxUnedited = canvasUnedited.getContext('2d');
                canvasCtxUnedited.drawImage(canvas, 0, 0);
            });
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

            this.dispatchEvent(new Event('change'));
        });
    }
}

customElements.define('ob-field-media', OBFieldMedia);