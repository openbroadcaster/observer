import { html, render, sass } from "../vendor.js";

let globalStyle = null;
let localStyles = {};

export class OBElement extends HTMLElement {
    root;

    constructor() {
        super();

        this.initialized = new Promise((resolve) => {
            this.resolveInitialized = resolve;
        });

        const shadowRoot = this.attachShadow({ mode: "open" });

        const scss = `
            // common scss here
        `;

        if (!globalStyle) {
            globalStyle = sass.compileString(scss).css;
        }
        if (!localStyles[this.constructor.name]) {
            localStyles[this.constructor.name] = sass.compileString(this.scss()).css;
        }

        render(
            html`
                <style>
                    ${globalStyle}
                    ${localStyles[this.constructor.name]}
                </style>
                <div id="root"></div>
            `,
            shadowRoot,
        );

        this.root = shadowRoot.querySelector("#root");
    }

    scss() {
        return "";
    }

    async refresh() {
        await this.renderComponent();

        // look for any child elements starting with app- and refresh them also
        this.querySelectorAll("*").forEach((element) => {
            if (element.tagName.startsWith("APP-")) {
                if (element.refresh && typeof element.refresh == "function") {
                    element.refresh();
                } else {
                    console.warn("Component " + element.tagName + " does not have a refresh method");
                }
            }
        });

        if (this.shadowRoot) {
            this.shadowRoot.querySelectorAll("*").forEach((element) => {
                if (element.tagName.startsWith("APP-")) {
                    if (element.refresh && typeof element.refresh == "function") {
                        element.refresh();
                    } else {
                        console.warn("Component " + element.tagName + " does not have a refresh method");
                    }
                }
            });
        }
    }

    // chatgpt4 new version
    getClosestAncestorByTag(targetTag) {
        let currentElement = this;

        // Normalize the target tag name to uppercase as HTML tag names are case-insensitive but commonly represented in uppercase in DOM
        targetTag = targetTag.toUpperCase();

        while (currentElement) {
            // Check the tag name before moving up the DOM tree
            if (currentElement.tagName === targetTag) {
                return currentElement;
            }

            if (currentElement.parentNode) {
                // Go to the parent node
                currentElement = currentElement.parentNode;
            } else if (currentElement.host) {
                // If currentElement is a shadow root, jump to its host
                currentElement = currentElement.host;
            } else if (currentElement.shadowRoot && currentElement.shadowRoot.host) {
                // If the element has a shadow root, jump to its host
                currentElement = currentElement.shadowRoot.host;
            } else {
                // Reached the top, break out of the loop
                break;
            }

            // Check if the new current element is actually a shadow root, in which case we want to jump to its host
            if (currentElement instanceof ShadowRoot) {
                currentElement = currentElement.host;
            }
        }

        return null; // Return null if no ancestor with the specified tag name is found
    }

    // add a listener if not already added
    ensureListener(element, event, callback) {
        element.removeEventListener(event, callback);
        element.addEventListener(event, callback);
    }
}
