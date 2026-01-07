import { OBElement } from "../base/element.js";
import { html, render } from "../vendor.js";

export class OBElementThumbnail extends OBElement {
    _mediaId;

    async connectedCallback() {
        this.resolveInitialized();
        this.renderComponent();
    }

    async renderComponent() {
        // get media id from data-id attribute
        this._mediaId = this.getAttribute("data-id");

        // API v2 request with raw (get blob data)
        const image = await OB.API.request({
            endpoint: "/downloads/media/" + this._mediaId + "/thumbnail/",
            raw: true,
        });

        // create image element from blob
        const imageElement = document.createElement("img");
        imageElement.src = URL.createObjectURL(image);

        // replace root content with image element
        this.root.innerHTML = "";
        this.root.appendChild(imageElement);
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
