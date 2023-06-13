import { html, render } from './vendor.js'
import { OBInput } from './Input.js';

class OBInputThumbnail extends OBInput {

    #root;
    #imageData;
    #imageWidth;
    #imageHeight;
    #readOnly;

    constructor() {
        super();
        this.#root = this.attachShadow({ mode: 'open'});

        this.#imageData = null;
        this.#imageWidth = this.getAttribute('width') ? this.getAttribute('width') : 128;
        this.#imageHeight = this.getAttribute('height') ? this.getAttribute('height') : 128;
        this.#readOnly = ((this.getAttribute('readonly') !== null) && (this.getAttribute('readonly') !== "false")) ? true : false;
    }

    connectedCallback() {
        this.renderComponent();
    }

    renderComponent() {
        render(html`
            <style>
                .hide {
                    display: none !important;
                }

                .image-wrapper {
                    position: relative;
                    width: ${this.#imageWidth}px;
                    height: ${this.#imageHeight}px;
                }

                .image-wrapper .button-wrapper {
                    position: absolute; 
                    display: flex;
                    bottom: 0px;
                    justify-content: space-between;
                    width: 100%;
                }

                .image-wrapper .button {
                    border: 1px white solid;
                    border-radius: 4px;
                    background-color: black;
                    opacity: 0.8;
                    cursor: pointer;
                }
            </style>

            <div class="wrapper">
                <input type="file" accept="image/*" onchange=${this.onChange.bind(this)} />
                <div class="image-wrapper hide" onmouseover=${this.onMouseOver.bind(this)} onmouseleave=${this.onMouseLeave.bind(this)}>
                    <img src="${this.#imageData}" class="thumbnail" width=${this.#imageWidth} height=${this.#imageHeight}></img>
                    <div class="button-wrapper hide">
                        <div class="button" onclick=${this.replaceImage.bind(this)}>Replace</div>
                        <div class="button" onclick=${this.deleteImage.bind(this)}>Delete</div>
                    </div>
                </div>
            </div>
        `, this.#root);

        this.#toggleDisplay();
    }

    onChange(event) {
        const reader = new FileReader();
        const imgElem = this.#root.querySelector('.image-wrapper');

        reader.onload = (e) => {
            this.#imageData = e.target.result;
            this.renderComponent();
        }
        reader.readAsDataURL(event.target.files[0]);
    }

    onMouseOver(event) {
        if (! this.#imageData || this.#readOnly) return;

        this.#root.querySelector('.image-wrapper .button-wrapper').classList.remove('hide');
    }

    onMouseLeave(event) {
        if (! this.#imageData || this.#readOnly) return; 
        
        this.#root.querySelector('.image-wrapper .button-wrapper').classList.add('hide');
    }

    deleteImage(event) {
        this.#imageData = null;
        this.#root.querySelector('input').value = '';

        this.renderComponent();
    }

    replaceImage(event) {
        this.#root.querySelector('input').click();
    }

    get value() {
        return this.#imageData;
    }

    set value(value) {
        this.#imageData = value;
        this.renderComponent();
    }

    #toggleDisplay() {
        if (this.#imageData == null) {
            this.#root.querySelector('.image-wrapper').classList.add('hide');
            this.#root.querySelector('input').classList.remove('hide');
        } else {
            this.#root.querySelector('.image-wrapper').classList.remove('hide');
            this.#root.querySelector('input').classList.add('hide');
        }

        if (this.#readOnly) {
            this.#root.querySelector('input').disabled = true;
        } else {
            this.#root.querySelector('input').disabled = false;
        }
    }
}

customElements.define('ob-input-thumbnail', OBInputThumbnail);