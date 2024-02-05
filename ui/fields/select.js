import { html, render } from '../vendor.js';
import { OBField } from '../base/field.js';

class OBFieldSelect extends OBField {

    filterVal = '';

    addSelected(option) {
        if (this.multiple && !this.selected.includes(option)) {
            // add option to selected
            this.selected.push(option);
        }

        else if (!this.multiple) {
            this.selected = option;
        }

        // update selected and blur to hide dropdown
        this.updateSelected();
        this.root.querySelector('#input').blur();
    }

    deleteSelected(option) {
        // remove option from this.selected
        const index = this.selected.indexOf(option);
        if (index > -1) {
            this.selected.splice(index, 1);
        }

        this.updateSelected();
    }

    updateSelected() {
        if (this.selected === undefined || this.selected === null) {
            return;
        }

        if (this.multiple) {
            // clear input-selected
            this.root.querySelector('#input-selected-multiple').innerHTML = '';

            // create tags for each value
            for (const option of this.selected) {
                // create tag and add it to input-selected
                const tag = document.createElement('span');
                tag.classList.add('tag');
                tag.innerHTML = this.options[option];

                // onclick, delete tag
                tag.onclick = () => {
                    this.deleteSelected(option);
                };

                this.root.querySelector('#input-selected-multiple').appendChild(tag);
            }
        }
        else {
            this.root.querySelector('#input-selected').innerHTML = this.options[this.selected] ?? '';
        }

        // emit change
        this.dispatchEvent(new CustomEvent('change', {
            detail: {
                value: this.selected
            }
        }));
    }

    filter(e) {
        // check if key is a regular character
        if (e.key.length === 1) {
            this.filterVal += e.key;
        }
        else if (e.key === 'Backspace') {
            this.filterVal = this.filterVal.slice(0, -1);
        }

        else return;

        // display input filter
        this.root.querySelector('#input-filter').innerHTML = this.filterVal;

        // hide any options that don't contain the filter
        const options = this.root.querySelectorAll('#options li');
        options.forEach(option => {
            if (option.innerHTML.toLowerCase().includes(this.filterVal.toLowerCase())) {
                option.removeAttribute('hidden');
            }
            else {
                option.setAttribute('hidden', true);
            }
        });
    }

    filterReset(e) {
        this.filterVal = '';
        this.root.querySelector('#input-filter').innerHTML = '';
        // remove any hidden attributes
        const options = this.root.querySelectorAll('#options li');
        options.forEach(option => {
            option.removeAttribute('hidden');
        });
    }

    scss() {
        return `
            :host { position: relative; width: var(--field-width); display: inline-block; }
            ul {
                display: none;
                list-style-type: none;
                padding-left: 0;
                position: absolute;
                top: 100%;
                left: 0;
                border: var(--field-border);
                z-index: 1;
                margin-top: 0;
                box-sizing: border-box;
                max-height: 300px;
                overflow: auto;
                border-bottom-left-radius: var(--field-radius);
                border-bottom-right-radius: var(--field-radius);
                background: var(--field-background);
                width: 100%;
            }
            ul li {
                padding: 4px 8px;
                cursor: pointer;
            }
            #input 
            {
                cursor: pointer;
                width: 100%;
                background-color: var(--field-background);
                border: var(--field-border);
                border-radius: var(--field-radius);
                color: var(--field-color);
            }
            #input:focus 
            {
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
                outline: 0;
            }
            #input:focus ul {
                display: block;
            }

            #input-filter {
                // effectively hides the 

            }

            #input-filter:not(:empty) {
                border-bottom: 1px solid var(--field-color);
                margin-bottom: -1px; /* removes extra border thickness at bottom when no results */
            }

            #input-filter:hover {
                background-color: transparent;
                color: #000;
            }

            #input-filter:empty {
                display: none;
            }

            /* prevent the input from collapsing when empty */
            #input-selected:empty::after,
            #input-selected-multiple:empty::after {
                content: "\\00A0";
            }
            #input-selected-multiple:empty::after {
                padding: 4px 8px;
            }

            #input-selected-multiple {
                display: flex;
                flex-wrap: wrap;
            }

            #input-selected {
                padding: 4px 8px;
            }
            .tag {
                border: 1px solid var(--field-color);
                border-radius: var(--field-radius);
                padding: 2px;
                margin: 2px;
                position: relative;
            }
            .tag:hover {
                text-decoration: line-through;
            }`;
    }

    get value() {
        return this.selected;
    }

    set value(value) {
        this.selected = value;
        this.updateSelected();
    }

    renderEdit() {
        // get options from data-options and json decode
        if (!this.options) this.options = JSON.parse(this.getAttribute('data-options'));
        if (!this.selected) this.selected = JSON.parse(this.getAttribute('data-value'));
        this.multiple = this.hasAttribute('data-multiple');

        if (!this.options) this.options = {};
        if (!this.selected && this.multiple) this.selected = [];
        if (!this.selected && !this.multiple) this.selected = '';

        render(html`
            <div id="input" class="field" tabindex="0" onkeydown=${(e) => this.filter(e)} onblur=${(e) => this.filterReset()}>
                ${this.multiple && html`<div id="input-selected-multiple"></div>`}
                ${!this.multiple && html`<div id="input-selected"></div>`}
                <ul id="options" contenteditable="false">
                    <li id="input-filter"></li>
                    ${!this.multiple && html`<li onClick=${() => this.addSelected('')}>(None)</li>`}
                    ${Object.keys(this.options).map(option => html`<li onClick=${() => this.addSelected(option)}>${this.options[option]}</li>`)}
                </ul>
            </div>

    `, this.root);

        this.updateSelected();
    }

}

customElements.define('ob-field-select', OBFieldSelect);