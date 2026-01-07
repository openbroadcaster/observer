import { OBField } from "../base/field.js";
import { html, render } from "../vendor.js";

class OBFieldImageRotate extends OBField {
    _value = 0;
    _offset = 0; // how much the thumbnail is already rotated

    async renderEdit() {
        render(
            html`<ob-element-thumbnail
                    id="media_details_thumbnail"
                    data-id=${this.dataset.id}
                    data-rotate=${this._value}
                ></ob-element-thumbnail>
                <div class="controls">
                    <div class="controls-left"><button>⭯ Left</button></div>
                    <div class="controls-label">${this._value}°</div>
                    <div class="controls-right"><button>Right ⭮</button></div>
                </div>`,
            this.root,
        );

        this.root.querySelector(".controls-left button").addEventListener("click", this.rotateLeft.bind(this));
        this.root.querySelector(".controls-right button").addEventListener("click", this.rotateRight.bind(this));

        this.updateImage();
    }

    scss() {
        return `
            ob-element-thumbnail {
                max-width: 300px;
                aspect-ratio: 1 / 1;
                display: block;
                border: 1px solid var(--field-background);
                border-radius: 5px;
            }

            ob-element-thumbnail[data-rotate="90"] {
                transform: rotate(90deg);
            }

            ob-element-thumbnail[data-rotate="180"] {
                transform: rotate(180deg);
            }
            
            ob-element-thumbnail[data-rotate="270"] {
                transform: rotate(270deg);
            }

            .controls {
                display: flex;
                justify-content: space-between;
                margin-top: 10px;
            }

            .controls-left, .controls-right {
                flex: 1 1 0;
                display: flex;
            }

            .controls-right {
                justify-content: flex-end;
            }

            :host {
                display: inline-block; 
                
                input {
                    color: var(--field-color);
                    background-color: var(--field-background);
                    border-radius: var(--field-radius);
                    border: var(--field-border);
                    font-size: 13px;
                    padding: 5px;
                    width: 250px;
                    vertical-align: middle;
                }
            }
        `;
    }

    get offset() {
        return this._offset;
    }

    set offset(offset) {
        this._offset = offset;
        this.updateImage();
    }

    get value() {
        return this._value;
    }

    set value(value) {
        this._value = value;
        this.updateImage();
    }

    updateImage() {
        let rotate = (parseInt(this._value) - parseInt(this._offset)) % 360;
        if (rotate < 0) {
            rotate += 360;
        }

        const thumbnail = this.root.querySelector("ob-element-thumbnail");
        if (thumbnail) {
            thumbnail.dataset.rotate = rotate;
        }

        const label = this.root.querySelector(".controls-label");
        if (label) {
            label.innerText = `${this._value}°`;
        }
    }

    rotateLeft() {
        let rotate = (parseInt(this._value) - 90) % 360;
        if (rotate < 0) {
            rotate += 360;
        }
        this.value = rotate;
    }

    rotateRight() {
        const rotate = (parseInt(this._value) + 90) % 360;
        this.value = rotate;
    }
}

customElements.define("ob-field-image-rotate", OBFieldImageRotate);
