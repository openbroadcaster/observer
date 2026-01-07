import { OBLitElement } from "../../base/litelement.js";
import { lithtml as html, litcss as css } from "../../vendor.js";

class OBElementVoicetrackPreview extends OBLitElement {
    static properties = {
        isPlaying: { type: Boolean },
        headDuration: { type: Number },
        tailDuration: { type: Number },
        fadeInDuration: { type: Number },
        fadeOutDuration: { type: Number },
        fadeAmount: { type: Number },
        offsetTime: { type: Number },
        prevTrackId: { type: Number },
        nextTrackId: { type: Number },
        voiceTrackId: { type: Number },
    };

    // set styles
    static get styles() {
        return css`
            :host {
                display: block;
            }
        `;
    }

    // constructor
    constructor() {
        super();
        this.isPlaying = false;
        this.headDuration = 2;
        this.tailDuration = 2;
    }

    disconnected() {
        this.stop();
    }

    attributeChangedCallback(name, oldValue, newValue) {
        this.stop();
    }

    valid() {
        // temporary
        return true;

        // make sure all properties set
        const properties = [
            "headDuration",
            "tailDuration",
            "fadeInDuration",
            "fadeOutDuration",
            "fadeAmount",
            "offsetTime",
            "prevTrackId",
            "nextTrackId",
            "voiceTrackId",
        ];
        return properties.every((property) => this[property] !== undefined);
    }

    toggle() {
        if (this.isPlaying) {
            this.stop();
        } else {
            this.play();
        }
    }

    play() {
        this.isPlaying = true;
    }

    stop() {
        this.isPlaying = false;
    }

    render() {
        const buttonText = this.isPlaying ? "Stop" : "Play";

        // temp show all properties html
        const allProperties = html`
            <div>headDuration: ${this.headDuration}</div>
            <div>tailDuration: ${this.tailDuration}</div>
            <div>fadeInDuration: ${this.fadeInDuration}</div>
            <div>fadeOutDuration: ${this.fadeOutDuration}</div>
            <div>fadeAmount: ${this.fadeAmount}</div>
            <div>offsetTime: ${this.offsetTime}</div>
            <div>prevTrackId: ${this.prevTrackId}</div>
            <div>nextTrackId: ${this.nextTrackId}</div>
            <div>voiceTrackId: ${this.voiceTrackId}</div>
            <div>isplaying: ${this.isPlaying}</div>
            <div>isvalid: ${this.valid()}</div>
        `;

        return html`
            <button @click=${this.toggle} ?disabled=${!this.valid()}>${buttonText}</button>
            ${allProperties}
        `;
    }
}

customElements.define("ob-element-voicetrack-preview", OBElementVoicetrackPreview);
