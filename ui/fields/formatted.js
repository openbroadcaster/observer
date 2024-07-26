import { html, render, marked, dompurify } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldFormatted extends OBField {
    #init;
    #editorInstance;

    static comparisonOperators = {
        eq: "is",
        neq: "is not",
        contains: "contains",
        ncontains: "does not contain",
    };

    static comparisonField = "text";

    renderEdit() {
        if (this.#editorInstance) {
            // update value only
            this.#editorInstance.value(this._value);
        } else {
            render(
                html` <link
                        rel="stylesheet"
                        type="text/css"
                        href="extras/fontawesome-free-5.15.3-web/css/all.css?v=1712184964"
                    />
                    <link rel="stylesheet" href="/node_modules/easymde/dist/easymde.min.css" />
                    <textarea id="edit"></textarea>`,
                this.root,
            );

            this.#editorInstance = new EasyMDE({
                element: this.root.querySelector("#edit"),
                minHeight: "200px",
                autoDownloadFontAwesome: false,
                status: false,
                toolbarTips: true,
                forceSync: true,
                toolbar: [
                    "bold",
                    "italic",
                    "strikethrough",
                    "|",
                    "link",
                    "|",
                    "unordered-list",
                    "ordered-list",
                    "|",
                    "fullscreen",
                ],
                onToggleFullScreen: (isFullScreen) => {
                    // add data-fullscreen
                    if (isFullScreen) {
                        this.setAttribute("data-fullscreen", "");
                    } else {
                        this.removeAttribute("data-fullscreen");
                    }
                },
            });

            this.#editorInstance.codemirror.on("change", () => {
                this._value = this.#editorInstance.value();
            });
        }
    }

    renderView() {
        this.root.innerHTML = `<div id="view">${dompurify.sanitize(marked(this._value ?? ""))}</div>`;
    }

    scss() {
        return `
            :host {
                display: inline-block;
                position: relative;
                z-index: 1000;
                max-width: 300px;
            }

            :host([data-fullscreen]) {
                z-index: 20000;
            }

            .editor-toolbar {
                background-color: #fff;
            }

            #view > *:first-child {
                margin-top: 0;
            }

            #view > *:last-child {
                margin-bottom: 0;
            }
        `;
    }
}

customElements.define("ob-field-formatted", OBFieldFormatted);
