import { html, render } from '../vendor.js'
import { OBField } from '../base/field.js';

class OBFieldRange extends OBField {
    #init;
    #value;

    #prefix;
    #suffix;
    #step;
    #decimals;
    #min;
    #max;

    connectedCallback() {
        if (! this.#init) {
            this.#init = true;

            this.#prefix   = '';
            this.#suffix   = '';
            this.#step     = 1;
            this.#decimals = 0;
            this.#min      = 0;
            this.#max      = 100;

            this.renderComponent();

            if (this.dataset.hasOwnProperty('prefix')) {
                this.#prefix = this.dataset.prefix;
            } 

            if (this.dataset.hasOwnProperty('suffix')) {
                this.#suffix = this.dataset.suffix;
            } 

            if (this.dataset.hasOwnProperty('step')) {
                this.#step = this.dataset.step;
                this.#decimals = this.#step.split('.')[1].length;
            } 

            if (this.dataset.hasOwnProperty('min')) {
                this.#min = parseFloat(this.dataset.min).toFixed(this.#decimals);
            } 

            if (this.dataset.hasOwnProperty('max')) {
                this.#max = parseFloat(this.dataset.max).toFixed(this.#decimals);
            } 

            if (this.getAttribute('value') !== null) {
                this.#value = parseFloat(this.getAttribute('value')).toFixed(this.#decimals);
            } else {
                this.value = 0;
            }

            this.refresh();
        }
    }

    renderEdit() {
        render(html`
            <div id="field">
                <div id="min">${this.#prefix}${this.#min}${this.#suffix}</div>
                <div id="range">
                    <span id="value">${this.#prefix}${this.#value}${this.#suffix}</span>
                    <input type="range" min="${this.#min}" max="${this.#max}" step="${this.#step}" 
                        oninput=${this.rangeLabelUpdate.bind(this)}
                        onchange=${this.rangeLabelUpdate.bind(this)} />
                </div>
                <div id="max">${this.#prefix}${this.#max}${this.#suffix}</div>
            </div>
        `, this.root);
    }

    renderView() {
        render(html`
            <div id="field">
                <div id="min">${this.#prefix}${this.#min}${this.#suffix}</div>
                <div id="range">
                    <span id="value">${this.#prefix}${this.#value}${this.#suffix}</span>
                    <input type="range" min="${this.#min}" max="${this.#max}" step="${this.#step}" disabled 
                        onchange=${this.rangeLabelUpdate.bind(this)} />
                </div>
                <div id="max">${this.#prefix}${this.#max}${this.#suffix}</div>
            </div>
        `, this.root);
    }

    scss() {
        return `
            :host {
                #field {
                    display: flex;
                    width: 350px;
                    justify-content: space-between;

                    #range {
                        display: flex;
                        flex-direction: column;

                        #value {
                            text-align: center;
                        }
                    }

                    #min, #max {
                        align-self: center;
                    }
                }
            }
        `;
    }

    rangeLabelUpdate(event) {
        this.#value = this.value.toFixed(this.#decimals);
        this.refresh();
    }

    get value() {
        const rangeElem = this.root.querySelector("input[type=\"range\"]");

        return parseFloat(rangeElem.value);
    }

    set value(value) {
        const rangeElem = this.root.querySelector("input[type=\"range\"]");

        rangeElem.value = value;

        this.rangeLabelUpdate(null);
        this.refresh();
    }

    get step() {
        const rangeElem = this.root.querySelector("input[type=\"range\"]");

        return parseFloat(rangeElem.step);
    }

    get min() {
        const rangeElem = this.root.querySelector("input[type=\"range\"]");

        return parseFloat(rangeElem.min);
    }

    get max() {
        const rangeElem = this.root.querySelector("input[type=\"range\"]");

        return parseFloat(rangeElem.max);
    }
}

customElements.define('ob-field-range', OBFieldRange);
