import { html, render } from '../vendor.js';
import { OBField } from '../base/field.js';

class OBFieldTags extends OBField {
    #init;
    #tags;
    #currentTag;

    connectedCallback() {
        if (this.#init) {
            return;
        }

        this.#init = true;
        this.#tags = [];
        this.#currentTag = "";

        this.renderComponent();
    }
    
    renderEdit() {
        render(html`
            <div id="input" class="field" tabindex="0" 
            onkeydown=${(e) => this.tagsInput(e)} 
            >
                <div id="tags">
                    ${this.#tags.map((tag) => html`
                        <span class="saved">${tag}<span class="delete" onclick=${(e) => this.tagsDelete(tag)}></span></span>
                    `)}
                    <span id="current">${this.#currentTag}</span>
                </div>
            </div>
        `, this.root);
    }

    renderView() {
        render(html`
            <div>
                Tags view (TODO)
            </div>
        `, this.root);
    }

    scss() {
        return `
            :host {
                display: inline-block;

                #input {
                    width: 250px;
                    min-height: 1rem; // this isn't quite right will fix later
                    padding: 5px;
                    border: 0;
                    vertical-align: middle;
                    background-color: #fff;
                    font-size: 13px;
                    color: #2e3436;
                    display: inline-block;
                    border-radius: 2px;
                }

                #input:focus-within {
                    #current::after {
                        content: "|";
                    }
                }

                #tags {
                    display: flex;
                    align-items: center;
                    gap: 0.3em;
                    flex-wrap: wrap;

                    span.saved {
                        background-color: #eee;
                        padding: 0.2em;
                        border-radius: 3px;
                        word-wrap: anywhere;

                        .delete::after {
                            content: "x";
                            color: #e00;
                        }
                    }
                }
            }
        `;
    }

    tagsInput(event) {
        let keyCode = (event.key.length === 1) ? event.key.charCodeAt(0) : false;
        
        if ((keyCode >= 65 && keyCode <= 90) || (keyCode >= 97 && keyCode <= 122)) {
            // A-Z and a-z
            this.#currentTag += event.key;
        } else if (keyCode >= 48 && keyCode <= 57) {
            // 0-9
            this.#currentTag += event.key;
        } else if (keyCode === 45 || keyCode === 95) {
            // -_
            this.#currentTag += event.key;
        }

        if (event.key === "Backspace") {
            if (this.#currentTag.length > 0) {
                // Backspace letter from current tag if it exists.
                this.#currentTag = this.#currentTag.slice(0, this.#currentTag.length - 1);
            } else {
                // Otherwise, delete the last finished tag.
                this.#tags.pop();
            }
        }

        if (event.key === "Enter" || event.key === " " || event.key === ",") {
            event.preventDefault();

            if (this.#currentTag.length > 0) {
                if (this.#tags.find((elem) => elem === this.#currentTag) === undefined) {
                    this.#tags.push(this.#currentTag);
                }
                this.#currentTag = "";
            }
        }

        this.renderComponent();
    }

    tagsDelete(tag) {
        this.#tags = this.#tags.filter((elem) => elem != tag);
        this.renderComponent();
    }

    get value() {
        return this.#tags;
    }

    set value(value) {
        if (Array.isArray(value)) {
            this.#tags = [...new Set(value)];
            this.renderComponent();
        }
    }
}

customElements.define('ob-field-tags', OBFieldTags);