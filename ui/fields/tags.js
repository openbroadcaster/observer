import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldTags extends OBField {
    #init;
    #tags;
    #suggestions;
    #currentTag;

    static comparisonOperators = {
        has: "has",
        nhas: "does not have",
    };

    static comparisonField = "text";

    // TODO loading tags and suggestions via OB-OPTION and OB-TAGS temporarily disabled.
    // fix needed as these are currently overwriting set value.
    /*
    async connected() {
        if (this.#init) {
            return;
        }

        this.#init = true;
        this.#tags = [];
        this.#suggestions = [];
        this.#currentTag = "";

        this.#tags = this.#loadInnerTags();
        this.#suggestions = this.#loadInnerSuggestions();

        this.refresh();
    }
    */

    renderEdit() {
        if (!this.#tags) {
            this.#tags = [];
        }

        if (!this.#suggestions) {
            this.#suggestions = [];
        }

        render(
            html`
                <div id="input" class="field" tabindex="0" onkeydown=${(e) => this.tagsInput(e)}>
                    <div id="tags">
                        ${this.#tags.map(
                            (tag) => html`
                                <span class="saved"
                                    >${tag}<span class="delete" onclick=${(e) => this.tagsDelete(tag)}></span
                                ></span>
                            `,
                        )}
                        <span id="current">${this.#currentTag}</span>
                    </div>
                </div>
                ${this.#suggestions.filter((tag) => !this.#tags.includes(tag)).length > 0 &&
                html`
                    <div id="suggestions" tabindex="0">
                        ${this.#suggestions
                            .filter((tag) => !this.#tags.includes(tag))
                            .map(
                                (tag) => html`
                                    <span class="suggestion" onclick=${(e) => this.tagsAdd(tag)}>${tag}</span>
                                `,
                            )}
                    </div>
                `}
            `,
            this.root,
        );
    }

    renderView() {
        const output = this.#tags?.join(", ");
        render(html` <div>${output}</div> `, this.root);
    }

    scss() {
        return `
            :host {
                display: inline-block;
                font-size: 13px;

                #input {
                    width: 250px;
                    min-height: 1rem; // this isn't quite right will fix later
                    padding: 5px;
                    vertical-align: middle;
                    display: inline-block;
                    border: 1px solid white;
                    border-radius: 3px;
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
                    color: #2e3436;

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

                    span#current {
                        color: #eee;
                    }
                }

                #suggestions {
                    display: none;
                    align-items: center;
                    gap: 0.3em;
                    flex-wrap: wrap;
                    padding: 5px;

                    border: 1px solid rgba(255, 255, 255, 0.7);
                    border-radius: 3px;
                    background: rgba(0, 0, 0, 0.7);

                    span {
                        color: #2e3436;
                        background-color: rgba(238, 238, 238, 0.9);
                        padding: 0.2em;
                        border-radius: 3px;
                        word-wrap: anywhere;
                    }
                }

                #root:focus-within {
                    #suggestions { display: flex; }
                }
            }
        `;
    }

    tagsInput(event) {
        let keyCode = event.key.length === 1 ? event.key.charCodeAt(0) : false;

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

        this.refresh();
    }

    tagsDelete(tag) {
        this.#tags = this.#tags.filter((elem) => elem != tag);
        this.refresh();
    }

    tagsAdd(tag) {
        if (this.#tags.find((elem) => elem === tag) === undefined) {
            this.#tags.push(tag);
        }
        this.refresh();
    }

    get value() {
        return this.#tags;
    }

    set value(value) {
        let tags = value;
        if (!Array.isArray(tags)) {
            tags = tags.split(",").map((tag) => tag.trim());
        }

        this.#tags = [...new Set(tags)];
        this.refresh();
    }

    set suggestions(value) {
        if (Array.isArray(value)) {
            this.#suggestions = [...new Set(value)];
            this.refresh();
        }
    }

    get suggestions() {
        return this.#suggestions;
    }

    #loadInnerTags() {
        var tags = [];

        Array.from(this.children).forEach((child) => {
            if (child.tagName === "OB-TAG") {
                let tag = child.innerText.replace(/ /g, "-").replace(/[^a-zA-Z0-9-_]/g, "");
                tags.push(tag);
            }
        });

        return tags;
    }

    #loadInnerSuggestions() {
        var suggestions = [];

        Array.from(this.children).forEach((child) => {
            if (child.tagName === "OB-OPTION") {
                let tag = child.innerText.replace(/ /g, "-").replace(/[^a-zA-Z0-9-_]/g, "");
                suggestions.push(tag);
            }
        });

        return suggestions;
    }
}

customElements.define("ob-field-tags", OBFieldTags);
