import { OBLitElement } from "../../base/litelement.js";
import { lithtml as html, litcss as css } from "../../vendor.js";

class OBElementVoicetrackGraph extends OBLitElement {
    static properties = {
        fadeInDuration: { type: Number },
        fadeOutDuration: { type: Number },
        fadeAmount: { type: Number },
        offsetTime: { type: Number },
        trackDuration: { type: Number },
    };

    constructor() {
        super();
        this.fadeInDuration = 0;
        this.fadeOutDuration = 0;
        this.fadeAmount = 50;
        this.offsetTime = 0;
        this.trackDuration = 5;
    }

    // set styles
    static get styles() {
        return css`
            :host {
                display: block;
                position: relative;
            }

            svg {
                display: block;
            }

            .graph {
                stroke-width: 1px;
                stroke: #fff;
                fill: transparent;
            }

            .tracks {
                position: relative;
                height: 15px;
            }

            .tracks.-voice {
                position: absolute;
                top: calc(1px * var(--voicetrack-graph-voice-top));
                width: 100%;
            }

            .tracks > * {
                position: absolute;
                text-align: center;
                color: #fff;
                top: 0;
                bottom: 0;
                font-size: 10px;
                line-height: 15px;
                border: 1px solid #fff;
                overflow: hidden;
            }

            .tracks-prev {
                left: 0;
                width: calc(100% * var(--voicetrack-graph-prev));
                border-top: 0;
                box-sizing: border-box;
            }

            .tracks-next {
                right: 0;
                left: calc(100% * var(--voicetrack-graph-prev));
                border-top: 0;
                box-sizing: border-box;
            }

            .tracks-voice {
                left: calc(100% * var(--voicetrack-graph-voice-left));
                width: calc(100% * var(--voicetrack-graph-voice-width));
                border-bottom: 0;
                box-sizing: border-box;
            }
        `;
    }

    polygon(offset, headDuration, tailDuration, fadeAmount, inDuration, trackDuration, outDuration, width, height) {
        let start = offset;
        let outBegin = start + headDuration;
        let outEnd = outBegin + outDuration;
        let inBegin = outEnd + trackDuration;
        let inEnd = inBegin + inDuration;
        let fadeTo = fadeAmount;
        let finish = inEnd + tailDuration;

        // scale for width/height
        start *= width / finish;
        outBegin *= width / finish;
        outEnd *= width / finish;
        inBegin *= width / finish;
        inEnd *= width / finish;
        fadeTo *= height / 100;
        finish *= width / finish;

        return `${start},0 ${outBegin},0 ${outEnd},${fadeTo} ${inBegin},${fadeTo} ${inEnd},0 ${finish},0 ${finish},${height} ${start},${height}`;
    }

    render() {
        // get host width
        const width = this.offsetWidth;
        const height = width * 0.33;

        const headDuration = 2;
        const tailDuration = 2;

        const polygon = this.polygon(
            0,
            headDuration,
            tailDuration,
            this.fadeAmount,
            this.fadeInDuration,
            this.trackDuration,
            this.fadeOutDuration,
            width,
            height,
        );

        const totalTime = headDuration + tailDuration + this.trackDuration + this.fadeInDuration + this.fadeOutDuration;
        const lastTrackTime = Math.min(totalTime, Math.max(0, -1 * (this.offsetTime - headDuration)));
        const nextTrackTime = Math.min(totalTime, Math.max(0, totalTime - lastTrackTime));
        const voicetrackStart = headDuration + this.fadeOutDuration;

        this.style.setProperty("--voicetrack-graph-prev", lastTrackTime / totalTime);
        this.style.setProperty("--voicetrack-graph-next", nextTrackTime / totalTime);
        this.style.setProperty("--voicetrack-graph-voice-left", voicetrackStart / totalTime);
        this.style.setProperty("--voicetrack-graph-voice-width", this.trackDuration / totalTime);
        this.style.setProperty("--voicetrack-graph-voice-top", (this.fadeAmount * height) / 100);

        const trackPrevHtml = lastTrackTime > 0 ? html`<div class="tracks-prev"><span>Prev Track</span></div>` : html``;
        const trackNextHtml = nextTrackTime > 0 ? html`<div class="tracks-next"><span>Next Track</span></div>` : html``;

        return html`
            <div style="padding-top: 15px;">
                <svg preserveAspectRatio="none" viewBox="0 0 ${width} ${height}" width="${width}" height="${height}">
                    <polygon points="${polygon}" class="graph" />
                </svg>
            </div>
            <div class="tracks">${trackPrevHtml} ${trackNextHtml}</div>
            <div class="tracks -voice">
                <div class="tracks-voice"><span>Voice Track </span></div>
            </div>
        `;
    }
}

customElements.define("ob-element-voicetrack-graph", OBElementVoicetrackGraph);
