import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldInputDevice extends OBField {

    #init;
    #audioDevices;
    #videoDevices;

    #showDetailed;
    #showVideo;

    async connectedCallback() {
        if (this.#init) {
            return;
        }

        this.#init = true;

        this.#showDetailed = "simple";
        if (this.dataset.hasOwnProperty('detailed')) {
            this.#showDetailed = "details";
        }

        if (this.dataset.hasOwnProperty('video')) {
            this.#showVideo = true;
        }

        await navigator.mediaDevices.getUserMedia({audio: true});
        await this.#refreshDevices();
        navigator.mediaDevices.ondevicechange = (event) => {
            this.#refreshDevices();
        }

        this.renderComponent();
    }

    async #refreshDevices() {
        this.#audioDevices = [];
        this.#videoDevices = []; 

        let devices = await navigator.mediaDevices.enumerateDevices();
        
        devices.forEach((device) => {
            if (device.kind === "audioinput") {
                this.#audioDevices.push(device);
            } else if (device.kind === "videoinput") {
                this.#videoDevices.push(device);
            }
        });

        this.renderComponent();
    }

    renderEdit() {
        render(html`
            <div id="audio-input" class="${this.#showDetailed}">
                <span>Audio Input</span>
                <select>
                    ${this.#audioDevices.map(device => html`
                        <option value=${device.deviceId}>${device.label}</option>
                    `)}
                </select>
            </div>
            ${this.#showVideo && html`
                <div id="video-input" class="${this.#showDetailed}">
                    <span>Video Input</span>
                    <select>
                        ${this.#videoDevices.map(device => html`
                            <option value=${device.deviceId}>${device.label}</option>
                        `)}
                    </select>
                </div>
            `}
        `, this.root);
    }

    renderView() {
        render(html`
            <div>todo</div>
        `, this.root);
    }

    scss() {
        return `
            :host {
                #audio-input, #video-input {
                    &.simple {
                        span {
                            display: none;
                        }
                    }

                    select {
                        max-width: 100%;
                    }
                }
            }
        `;
    }

    get audio() {
        const audioElem = this.root.querySelector("#audio-input select");
        if (audioElem) {
            return audioElem.value;
        }
    }

    set audio(value) {
        const audioElem = this.root.querySelector("#audio-input select");
        if (audioElem) {
            audioElem.value = value;
        }
    }

    get video() {
        const videoElem = this.root.querySelector("#video-input select");
        if (videoElem) {
            return videoElem.value;
        }
    }

    set video(value) {
        const videoElem = this.root.querySelector("#video-input select");
        if (videoElem) {
            videoElem.value = value;
        }
    }
}

customElements.define('ob-field-input-device', OBFieldInputDevice);