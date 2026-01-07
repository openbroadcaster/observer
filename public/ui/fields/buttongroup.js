import { OBLitElement } from "../base/litelement.js";
import { lithtml as html, litcss as css } from "../vendor.js";

class OBFieldButtongroup extends OBLitElement {
    static properties = {
        value: { type: String },
        buttons: { type: Array },
        mini: { type: Boolean },
    };

    static styles = css`
        :host {
            display: flex;
            gap: 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            overflow: hidden;
        }

        button {
            border: 0;
            color: rgb(from var(--buttongroup-inactive-color) r g b / 0.66);
            background-color: var(--buttongroup-inactive-background);
            line-height: 0;
        }

        button.selected {
            background-color: var(--buttongroup-active-background);
            color: var(--buttongroup-active-color);
            line-height: 0;
        }
    `;

    constructor() {
        super();
        this.value = "";
        this.buttons = [];
    }

    render() {
        return html`
            <link rel="stylesheet" href="/node_modules/@fortawesome/fontawesome-free/css/all.min.css" />
            ${this.buttons.map(
                (button) => html`
                    <button
                        class=${this.value === button.value ? "selected" : ""}
                        @click=${() => this._handleClick(button.value)}
                    >
                        ${button.icon ? html`<i class="fas fa-${button.icon}"></i>` : ""}
                        ${(!this.mini && button.text) || ""}
                    </button>
                `,
            )}
        `;
    }

    _handleClick(value) {
        this.value = value;
        this.dispatchEvent(
            new CustomEvent("change", {
                detail: { value },
                bubbles: true,
                composed: true,
            }),
        );
    }
}

window.addEventListener("load", () => {
    customElements.define("ob-field-buttongroup", OBFieldButtongroup);
});
