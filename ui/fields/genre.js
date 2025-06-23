import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldGenre extends OBField {
    #init;

    #categories;
    #currentCategory;
    #currentGenre;
    #genres;

    static comparisonOperators = {
        eq: "is",
        neq: "is not",
    };

    async connected() {
        if (this.#init) {
            return;
        }
        this.#init = true;
        this.#currentCategory = null;
        this.#currentGenre = null;

        // Use OB.Settings for loading categories and genres. This should be loaded at
        // this point, but if it's not we'll have to change this to do an API call
        // directly instead.
        this.#categories = OB.Settings.categories;
        this.#genres = OB.Settings.genres;

        this.renderComponent().then(() => {
            // NOTE: Select an initial category and genre using the value, possibly?
            // Might be unwieldy in practice.

            this.onChange();
        });
    }

    renderEdit() {
        render(
            html`
                <select onchange=${this.onChange.bind(this)} id="category">
                    ${this.#categories.map(
                        (category) => html` <option value=${category.id}>${category.name}</option> `,
                    )}
                </select>
                ${this.#currentCategory !== null
                    ? html`
                          <select onchange=${this.onChange.bind(this)} id="genre">
                              ${this.#genres
                                  .filter((genre) => genre.media_category_id === this.#currentCategory)
                                  .map((genre) => html` <option value=${genre.id}>${genre.name}</option> `)}
                          </select>
                      `
                    : ""}
            `,
            this.root,
        );
    }

    renderView() {
        render(
            html`
                <select onchange=${this.onChange.bind(this)} id="category" disabled>
                    ${this.#categories.map(
                        (category) => html` <option value=${category.id}>${category.name}</option> `,
                    )}
                </select>
                ${this.#currentCategory !== null
                    ? html`
                          <select onchange=${this.onChange.bind(this)} id="genre" disabled>
                              ${this.#genres
                                  .filter((genre) => genre.media_category_id === this.#currentCategory)
                                  .map((genre) => html` <option value=${genre.id}>${genre.name}</option> `)}
                          </select>
                      `
                    : ""}
            `,
            this.root,
        );
    }

    onChange(event) {
        const category = this.root.querySelector("#category");
        this.#currentCategory = category.value;

        this.renderComponent().then(() => {
            const genre = this.root.querySelector("#genre");
            if (genre) {
                this.#currentGenre = genre.value;
                this.renderComponent();
            } else {
                this.#currentGenre = null;
            }
        });
    }

    scss() {
        return `
            :host {
                #category, #genre {
                    font: inherit;
                    font-size: 13px;
                    display: block;
                    padding: 5px;
                    width: 250px;
                    margin-bottom: 0.5em;
                    box-sizing: content-box;
                    color: var(--field-color);
                    background-color: var(--field-background);
                    border-radius: var(--field-radius);
                    border: var(--field-border);
                }
            }
        `;
    }

    get category() {
        return this.#currentCategory;
    }

    set category(value) {
        const category = this.root.querySelector('#category option[value="' + value + '"]');

        if (category) {
            this.root.querySelector("#category").value = value;

            // Make sure to call onChange since it updates the internal values and
            // re-renders the component.
            this.onChange();
        }
    }

    get genre() {
        return this.#currentGenre;
    }

    set genre(value) {
        const genre = this.root.querySelector('#genre option[value="' + value + '"]');

        if (genre) {
            this.root.querySelector("#genre").value = value;

            // Make sure to call onChange since it updates the internal values and
            // re-renders the component.
            this.onChange();
        }
    }
}

customElements.define("ob-field-genre", OBFieldGenre);
