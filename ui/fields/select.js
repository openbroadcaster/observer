import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldSelect extends OBField {
    #options;
    filterVal = "";

    static comparisonOperators = {
        eq: "is",
        neq: "is not",
    };

    addSelected(option) {
        if (this.multiple && !this.selected.includes(option)) {
            // add option to selected
            this.selected.push(option);
        } else if (!this.multiple) {
            this.selected = option;
        }

        // update selected and blur to hide dropdown
        this.updateSelected();
        this.root.querySelectorAll("#input, #input *").forEach((child) => child.blur());
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
            this.root.querySelector("#input-selected-multiple").innerHTML = "";

            // create tags for each value
            for (const option of this.selected) {
                // create tag and add it to input-selected
                const tag = document.createElement("span");
                tag.classList.add("tag");
                tag.innerHTML = this.options[option];

                // onclick, delete tag
                tag.onclick = (event) => {
                    const clickPosition = event.clientX - tag.getBoundingClientRect().left;
                    const tagWidth = tag.offsetWidth;

                    if (clickPosition >= tagWidth - 20) {
                        this.deleteSelected(option);
                    }
                };

                this.root.querySelector("#input-selected-multiple").appendChild(tag);
            }
        } else {
            this.root.querySelector("#input-selected").innerHTML = this.options[this.selected] ?? "";
        }

        // emit change
        this.dispatchEvent(
            new CustomEvent("change", {
                detail: {
                    value: this.selected,
                },
            }),
        );
    }

    filter(e) {
        // check if key is a regular character
        if (e.key.length === 1) {
            this.filterVal += e.key;
        } else if (e.key === "Backspace") {
            this.filterVal = this.filterVal.slice(0, -1);
        } else if (["ArrowUp", "ArrowDown", "Escape", "Enter"].indexOf(e.key) > -1) {
            e.preventDefault();
        } else return;

        // display input filter
        this.root.querySelector("#input-filter").innerHTML = this.filterVal;

        // hide any options that don't contain the filter
        const options = this.root.querySelectorAll("#options li");
        options.forEach((option) => {
            if (option.innerHTML.toLowerCase().includes(this.filterVal.toLowerCase())) {
                option.removeAttribute("hidden");
            } else {
                option.setAttribute("hidden", true);
            }
        });

        // hide popular/none if filter used
        if (this.filterVal.length) {
            if (this.root.querySelector(".popular")) {
                this.root.querySelector(".popular").setAttribute("hidden", true);
            }
            if (this.root.querySelector(".none")) {
                this.root.querySelector(".none").setAttribute("hidden", true);
            }
        } else {
            if (this.root.querySelector(".popular")) {
                this.root.querySelector(".popular").removeAttribute("hidden");
            }
            if (this.root.querySelector(".none")) {
                this.root.querySelector(".none").removeAttribute("hidden");
            }
        }

        // if up or down arrow is pressed, select previous/next option that
        // isn't hidden by filter
        if (e.key === "ArrowUp" || e.key === "ArrowDown") {
            let selected = this.root.querySelector("#options li.selected");
            if (!selected) {
                selected = this.root.querySelector("#options li:not([hidden])");
            }

            if (selected) {
                if (e.key === "ArrowUp") {
                    let previous = selected.previousElementSibling;
                    while (previous && previous.hasAttribute("hidden")) {
                        previous = previous.previousElementSibling;
                    }
                    if (previous) {
                        selected.classList.remove("selected");
                        previous.classList.add("selected");
                        previous.scrollIntoView({ block: "nearest" });
                    }
                } else if (e.key === "ArrowDown") {
                    let next = selected.nextElementSibling;
                    while (next && next.hasAttribute("hidden")) {
                        next = next.nextElementSibling;
                    }
                    if (next) {
                        selected.classList.remove("selected");
                        next.classList.add("selected");
                        next.scrollIntoView({ block: "nearest" });
                    }
                }
            }
        }

        // pressing enter confirms the up/down keys selection, and escape closes the dropdown
        if (e.key === "Enter") {
            let selected = this.root.querySelector("#options li.selected");
            if (selected) {
                selected.click();
            }
        }
        if (e.key === "Escape") {
            this.root.querySelector("#input").blur();
        }
    }

    filterReset(e) {
        this.filterVal = "";
        this.root.querySelector("#input-filter").innerHTML = "";
        // remove any hidden attributes
        const options = this.root.querySelectorAll("#options li");
        options.forEach((option) => {
            option.removeAttribute("hidden");
        });
    }

    scss() {
        return `
            :host { position: relative; width: var(--field-width); display: inline-block; }
            ul {
                opacity: 0;
                pointer-events: none;
                list-style-type: none;
                padding-left: 0;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                border: var(--field-border);
                z-index: 100000;
                margin-top: 0;
                box-sizing: border-box;
                max-height: 300px;
                overflow: auto;
                border-bottom-left-radius: var(--field-radius);
                border-bottom-right-radius: var(--field-radius);
                background: var(--field-background);
                display: flex;
                flex-direction: column;
            }
            li {
                padding: 4px 8px;
                cursor: pointer;
                font-size: 0.9em;
                width: 100%;
                box-sizing: border-box;
            }

            li.none + li:not(.none),
            li.popular + li:not(.popular) {
                border-top: 1px solid var(--field-color);
            }

            li.selected {
                font-weight: bold;
            }

            li:hover {
                background-color: #e0e0e0;
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
            #input:focus-within
            {
                border-bottom-left-radius: 0;
                border-bottom-right-radius: 0;
                outline: 0;
            }
            #input:focus-within ul {
                opacity: 1;
                pointer-events: auto;
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
                padding: 2px 5px;
                padding-right: 17.5px;
                margin: 2px;
                position: relative;
            }
            .tag::after {
                content: 'x';
                font-weight: bold;
                color: #bf2121;
                font-size: 0.9em;
                padding-right: 5px;
                position: absolute;
                top: 0;
                right: 0;
                line-height: 0;
                height: 100%;
                display: flex;
                align-items: center;
            }`;
    }

    get value() {
        return this.selected;
    }

    set value(value) {
        this.selected = value;
        if (this._editable) this.updateSelected();
    }

    get options() {
        if (this.#options) {
            return this.#options;
        }
    }

    set options(value) {
        this.#options = value;
        this.renderEdit();
    }

    set settings(value) {
        this._settings = value;
        if (this._settings.options) this.options = this._settings.options;
    }

    get settings() {
        return this._settings;
    }

    renderEdit() {
        // get options from data-options and json decode, or use inner elements
        if (!this.#options) this.#options = JSON.parse(this.getAttribute("data-options"));
        if (!this.#options) this.#options = this.#loadInnerOptions();

        this.multiple = this.hasAttribute("data-multiple");

        // json decode data-value or check inner elements
        if (!this.selected) this.selected = JSON.parse(this.getAttribute("data-value"));
        if (!this.selected) this.selected = this.#loadInnerSelected();

        if (!this.popular) this.popular = JSON.parse(this.getAttribute("data-popular"));
        if (!this.popular) this.popular = [];

        render(
            html`
                <div
                    id="input"
                    class="field"
                    tabindex="0"
                    onkeydown=${(e) => this.filter(e)}
                    onblur=${(e) => this.filterReset()}
                >
                    ${this.multiple && html`<div id="input-selected-multiple"></div>`}
                    ${!this.multiple && html`<div id="input-selected"></div>`}
                    <ul id="options" contenteditable="false">
                        <li id="input-filter"></li>
                        ${!this.multiple && html`<li class="none" onClick=${() => this.addSelected("")}>(None)</li>`}
                        ${this.popular.map(
                            (value) =>
                                html`<li class="popular" onClick=${() => this.addSelected(value)}>
                                    ${this.#options[value]}
                                </li>`,
                        )}
                        ${Object.keys(this.#options).map(
                            (value) => html`<li onClick=${() => this.addSelected(value)}>${this.#options[value]}</li>`,
                        )}
                    </ul>
                </div>
            `,
            this.root,
        );

        // get all options and check the width
        const options = this.root.querySelectorAll("#options li");

        // get the largest width
        let largestWidth = 0;
        options.forEach((option) => {
            const width = option.getBoundingClientRect().width + 30;
            if (width > largestWidth) {
                largestWidth = width;
            }
        });

        // set the host width
        this.style.minWidth = largestWidth + "px";

        this.updateSelected();
    }

    renderView() {
        const output = this.#options ? this.#options[this.selected] : "";
        render(html`${output}`, this.root);
    }

    #loadInnerOptions() {
        var options = {};

        Array.from(this.children).forEach((child) => {
            if (child.tagName === "OB-OPTION") {
                let key = child.getAttribute("value");
                if (key) {
                    options[key] = child.innerText;
                } else {
                    // If you mix items that have a value attribute and items without them, and also
                    // some of the values are numbers lower than the length of the iterated options up
                    // to this point, they'll get overwritten, so this causes problems. But also come
                    // on now.
                    options[Object.keys(options).length] = child.innerText;
                }
            }
        });

        return options;
    }

    #loadInnerSelected() {
        var selected = null;
        if (this.multiple) {
            selected = [];
        } else {
            selected = "";
        }

        let index = 0;
        Array.from(this.children).forEach((child) => {
            if (child.tagName === "OB-OPTION" && child.hasAttribute("selected")) {
                let value = child.getAttribute("value");
                if (!value) {
                    value = index.toString();
                }

                if (this.multiple) {
                    selected.push(value);
                } else {
                    selected = value;
                }
            }

            index = index + 1;
        });

        return selected;
    }
}

customElements.define("ob-field-select", OBFieldSelect);
