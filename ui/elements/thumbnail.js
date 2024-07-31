import { OBElement } from "../base/element.js";
import { html, render } from "../vendor.js";

export class OBElementThumbnail extends OBElement {
    _mediaId;

    async connectedCallback() {
        this._mediaId = this.getAttribute("data-id");
        this.resolveInitialized();
        this.renderComponent();
    }

    async renderComponent() {
        OB.API.post("media", "thumbnail", { id: this._mediaId }, (response) => {
            const imageBase64 = response.data;
            const image = document.createElement("img");
            image.src = response.data;
            this.root.appendChild(image);
        });
    }

    scss() {
        return `
            #root {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
                width: 100%;
            }

            img {
                object-fit: contain;
                display: block;
                width: 100%;
                height: 100%;
            }
        `;
    }
}

customElements.define("ob-element-thumbnail", OBElementThumbnail);
