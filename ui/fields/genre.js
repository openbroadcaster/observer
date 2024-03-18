import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldGenre extends OBField {
    #init;

    connectedCallback() {
        if (this.#init) {
            return;
        }
        this.#init = true;

        this.renderComponent().then(() => {
            // NOTE: Select an initial category and genre using the value, possibly?
            // Might be unwieldy in practice. 
        });;
    }

    renderEdit() {
        render(html`
            <select id="category">
                <option>A</option>
                <option>B</option>
                <option>C</option>
            </select>
            <select id="genre">
                <option>1</option>
                <option>2</option>
                <option>3</option>
            </select>
        `, this.root);
    }

    renderView() {
        render(html`
            <select id="category" disabled>
                <option>A</option>
                <option>B</option>
                <option>C</option>
            </select>
            <select id="genre" disabled>
                <option>1</option>
                <option>2</option>
                <option>3</option>
            </select>
        `, this.root);
    }

    scss() {
        return `
            :host {
                #category, #genre {
                    font: inherit;
                    font-size: 13px;
                    display: block;
                    color: #2e3436;
                    padding: 5px;
                    width: 250px;
                    margin-bottom: 0.5em;
                    box-sizing: content-box;
                }
            }
        `;
    }

    get category() {
        // TODO 
    }

    set category(value) {
        // TODO
    }
    
    get genre() {
        // TODO
    }

    set genre(value) {
        // TODO
    }
}

customElements.define('ob-field-genre', OBFieldGenre);
