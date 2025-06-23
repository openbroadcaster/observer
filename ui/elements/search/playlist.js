import { OBLitElement } from "../../base/litelement.js";
import { lithtml as html, litcss as css } from "../../vendor.js";

class OBElementSearchPlaylist extends OBLitElement {
    static properties = {
        query: { type: String },
        view: { type: String }, // 'list', 'grid', etc.
        my: { type: Boolean }, // true if only showing media owned by the user
        status: { type: String }, // 'approved', 'unapproved', 'archived'
        mode: { type: String }, // 'simple', 'advanced',
        settingsOpen: { type: Boolean }, // true if the settings menu is open
    };

    constructor() {
        super();
        this.query = "";
        this.view = "list"; // default view
        this.my = false; // default to showing all media
        this.status = "approved"; // default status
        this.mode = "simple";
        this.settingsOpen = false;
        this.queryTimeout = null;
    }

    connectedCallback() {
        super.connectedCallback();
        window.addEventListener("click", this._clickOutsideMenu.bind(this));
    }

    disconnectedCallback() {
        super.disconnectedCallback();
        window.removeEventListener("click", this._clickOutsideMenu.bind(this));
    }

    static styles = css`
        :host {
            display: flex;
            gap: 10px;
            position: relative;
            height: 25px;
        }

        [hidden] {
            display: none !important;
        }

        input[type="text"] {
            border-radius: var(--radius-default);
            border: 0;
            padding: 0 10px;
        }

        input[type="text"]:focus {
            outline: 2px solid var(--color-accent);
            outline-offset: 2px;
        }

        .settings {
            display: flex;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: fit-content;
            background-color: var(--menu-background);
            color: var(--menu-color);
            padding: 10px;
            flex-direction: column;
            gap: 10px;
            border-radius: var(--radius-default);
            line-height: 1;
            white-space: nowrap;

            > * {
                padding-left: 20px;
                position: relative;
                margin: 0;
            }

            input {
                position: absolute;
                opacity: 0;
                pointer-events: none;
            }

            label {
                display: block;
                cursor: pointer;
            }

            label:has(input[type="checkbox"]:checked)::before {
                position: absolute;
                content: "\\f00c";
                left: 0;
                top: 50%;
                line-height: 0;
                font-family: "Font Awesome 6 Free";
                font-style: normal;
                font-weight: 900;
                font-display: block;
            }

            label:has(input[type="radio"]:checked)::before {
                position: absolute;
                left: 0;
                top: 50%;
                transform: translateY(-50%);
                height: 8px;
                width: 8px;
                background-color: #000;
                border-radius: 100px;
                content: "";
            }

            hr {
                border: 0;
                border-top: var(--menu-divider);
            }

            button {
                background-color: inherit;
                color: inherit;
                border: none;
                padding: 0;
                cursor: pointer;
                font-size: inherit;
                font-family: inherit;
                font-weight: inherit;
                margin: 0;
                line-height: inherit;
            }
        }

        #query,
        #filters {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        ob-element-button {
            display: block;
        }
    `;

    render() {
        const buttons = [
            { text: "List", value: "list", icon: "list" },
            { text: "Grid", value: "grid", icon: "th-large" },
        ];

        return html`
            <link rel="stylesheet" href="/node_modules/@fortawesome/fontawesome-free/css/all.min.css" />

            <input
                id="query"
                ?hidden=${this.mode == "advanced"}
                type="text"
                placeholder="Search playlists..."
                .value=${this.query}
                @input=${this._inputQuery}
                @keyup=${this._inputQuery}
                @blur=${this._inputQueryDispatch}
            />

            <ob-element-button iconName="gear" .text="Settings" @click=${this._toggleSettings}></ob-element-button>

            <div class="settings" ?hidden=${!this.settingsOpen}>
                <label><input type="checkbox" ?checked=${this.my} @change=${this._changeMy} /> My Playlists</label>
            </div>
        `;
    }

    _openSettings() {
        this.settingsOpen = true;
    }

    _closeSettings() {
        this.settingsOpen = false;
    }

    _toggleSettings() {
        this.settingsOpen = !this.settingsOpen;
    }

    _clickOutsideMenu(e) {
        if (e.target == this) {
            const composedPath = e.composedPath();
            if (!this.renderRoot.querySelector(".settings").includes(e.target)) {
                this._closeSettings();
            }
        } else {
            this._closeSettings();
        }
    }

    _inputQuery(e) {
        this.query = e.target.value;

        if (this.queryTimeout) {
            clearTimeout(this.queryTimeout);
        }

        // if enter key, dispatch immediately
        if (e.key === "Enter") {
            this._inputQueryDispatch();
        } else {
            this.queryTimeout = setTimeout(() => {
                this._inputQueryDispatch();
            }, 500);
        }
    }

    _inputQueryDispatch() {
        this.dispatchEvent(
            new CustomEvent("ob-search-playlist-query-changed", {
                bubbles: true,
                composed: true,
                detail: { query: this.query },
            }),
        );
    }

    _changeMy(e) {
        this.my = e.target.checked;
        this.dispatchEvent(
            new CustomEvent("ob-search-playlist-my-changed", {
                bubbles: true,
                composed: true,
                detail: { my: this.my },
            }),
        );
    }
}

customElements.define("ob-element-search-playlist", OBElementSearchPlaylist);
