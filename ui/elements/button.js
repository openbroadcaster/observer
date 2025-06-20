import { OBLitElement } from "../base/litelement.js";
import { lithtml as html, litcss as css } from "../vendor.js";

class OBElementButton extends OBLitElement {
    static get properties() {
        return {
            text: { type: String },
            iconStyle: { type: String }, // 'solid', 'regular', or 'brands'
            iconName: { type: String }, // 'image', 'save', etc.
            style: { type: String }, // 'mini', 'add', 'edit', 'delete' or 'default'
        };
    }

    iconStyle = "solid"; // default

    static get styles() {
        return css`
            :host {
                display: inline-block;
            }

            button {
                cursor: pointer;
                padding: 4px 8px;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 6px;
                color: var(--button-default-color);
                background: var(--button-default-background);
                border-radius: var(--button-default-radius);
                border: var(--button-default-border);
                font-weight: 600;
                font-family: inherit;
                justify-content: center;
                height: 100%;
            }

            button.add {
                color: var(--button-add-color);
                background: var(--button-add-background);
                border-radius: var(--button-add-radius);
                border: var(--button-add-border);
            }

            button.edit {
                color: var(--button-edit-color);
                background: var(--button-edit-background);
                border-radius: var(--button-edit-radius);
                border: var(--button-edit-border);
            }

            button.delete {
                color: var(--button-delete-color);
                background: var(--button-delete-background);
                border-radius: var(--button-delete-radius);
                border: var(--button-delete-border);
            }

            button:not(.mini) i {
                position: relative;
                top: 0.1em;
            }

            button.mini {
                background-color: transparent;
                color: inherit;
                padding: 0;
            }

            button.mini span {
                display: none;
            }
        `;
    }

    constructor() {
        super();

        // put the button role and focusable on the outer element (not the button itself)
        this.setAttribute("role", "button");
        this.setAttribute("tabindex", "0");
    }

    render() {
        return html`
            <link rel="stylesheet" href="/node_modules/@fortawesome/fontawesome-free/css/all.min.css" />
            <button
                title=${this.style === "mini" ? this.text : ""}
                role="presentation"
                tabindex="-1"
                class=${this.style || "default"}
            >
                ${this.iconName ? html`<i class="fa-${this.iconStyle} fa-${this.iconName}"></i>` : ""}
                ${this.text ? html`<span>${this.text}</span>` : ""}
            </button>
        `;
    }
}

window.addEventListener("load", () => {
    customElements.define("ob-element-button", OBElementButton);
});
