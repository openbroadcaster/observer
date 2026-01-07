import { LitElement } from "../vendor.js";

export class OBLitElement extends LitElement {
    constructor() {
        super();
        this.mapDataAttributes();
    }

    // Method to map data attributes to properties
    mapDataAttributes() {
        const attributes = this.attributes;
        for (let attr of attributes) {
            if (attr.name.startsWith("data-")) {
                // console.log(attr.name);
                const propName = this.dataAttrToProperty(attr.name);
                this[propName] = attr.value;
            }
        }
    }

    // Convert data-attribute-name to attributeName
    dataAttrToProperty(attrName) {
        return attrName.replace("data-", "").replace(/-([a-z])/g, (g) => g[1].toUpperCase());
    }
}
