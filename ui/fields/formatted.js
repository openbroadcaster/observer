import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldFormatted extends OBField {
    #init;
    #editorInstance;

    async connectedCallback() {
        if (this.#init) {
            return;
        }
        this.#init = true;

        this.renderComponent().then(() => {
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
            });
        });
    }

    renderEdit() {
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
    }

    renderView() {
        render(html`<div>TODO</div>`, this.root);
    }

    scss() {
        return `
            :host {
                display: inline-block;
                position: relative;
                z-index: 10000000;
                color: #000;
                max-width: 300px;
            }

            .editor-toolbar {
                background-color: #fff;
            }
        `;
    }

    get value() {
        return this.root.querySelector("#edit").value;
    }

    set value(value) {
        if (this.#editorInstance) {
            this.#editorInstance.value(value);
        } else {
            this.root.querySelector("#edit").value = value;
        }
        this.renderComponent();
    }
}

customElements.define("ob-field-formatted", OBFieldFormatted);
