import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldInputDevice extends OBField {
    #init;
    #initAudio;
    #initVideo;
    #audioDevices;
    #videoDevices;

    #showDetailed;
    #showVideo;

    #noPermission;

    async connected() {
        if (this.#init) {
            return;
        }

        this.#init = true;

        this.#showDetailed = "simple";
        if (this.dataset.hasOwnProperty("detailed")) {
            this.#showDetailed = "details";
        }

        if (this.dataset.hasOwnProperty("video")) {
            this.#showVideo = true;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            stream.getTracks().forEach((track) => track.stop());
            await this.#refreshDevices();
            navigator.mediaDevices.ondevicechange = (event) => {
                this.#refreshDevices();
            };

            this.renderComponent().then(() => {
                if (this.#initAudio) {
                    this.audio = this.#initAudio;
                }

                if (this.#initVideo) {
                    this.video = this.#initVideo;
                }
            });
        } catch {
            this.#noPermission = true;
        }
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

    renderNotAvailable() {
        render(html`<div>Microphone permission not available.</div>`, this.root);
    }

    renderEdit() {
        if (this.#noPermission) {
            this.renderNotAvailable();
            return;
        }

        render(
            html`
                <div id="audio-input" class="${this.#showDetailed}">
                    <span>Audio Input</span>
                    <select>
                        ${this.#audioDevices?.map(
                            (device) => html` <option value=${device.deviceId}>${device.label}</option> `,
                        )}
                    </select>
                </div>
                ${this.#showVideo &&
                html`
                    <div id="video-input" class="${this.#showDetailed}">
                        <span>Video Input</span>
                        <select>
                            ${this.#videoDevices.map(
                                (device) => html` <option value=${device.deviceId}>${device.label}</option> `,
                            )}
                        </select>
                    </div>
                `}
            `,
            this.root,
        );
    }

    renderView() {
        if (this.#noPermission) {
            this.renderNotAvailable();
            return;
        }

        // TODO?
        render(html`<div></div> `, this.root);
    }

    scss() {
        return `
            :host {
                display: inline-block;
                max-width: 100%;

                #audio-input, #video-input {
                    &.simple {
                        span {
                            display: none;
                        }
                    }

                    select {
                        width: 100%;
                        color: var(--field-color);
                        background-color: var(--field-background);
                        border-radius: var(--field-radius);
                        border: var(--field-border);
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
        } else {
            this.#initAudio = value;
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
        } else {
            this.#initVideo = value;
        }
    }
}

customElements.define("ob-field-input-device", OBFieldInputDevice);
