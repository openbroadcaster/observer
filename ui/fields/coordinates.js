import { html, render } from "../vendor.js";
import { OBField } from "../base/field.js";

class OBFieldCoordinates extends OBField {
    _value = null;
    _lat = null;
    _lng = null;

    renderEdit() {
        let lat = null;
        let lng = null;

        if (this._lat != null) {
            lat = parseFloat(this._lat).toFixed(5);
        }

        if (this._lng != null) {
            lng = parseFloat(this._lng).toFixed(5);
        }

        render(
            html`
                <div id="input">
                    <div id="lat">
                        <input
                            type="text"
                            placeholder="Latitute"
                            onchange=${this._updateLat.bind(this)}
                            value="${lat}"
                        />
                    </div>
                    <div id="lng">
                        <input
                            type="text"
                            placeholder="Longitude"
                            onchange=${this._updateLng.bind(this)}
                            value="${lng}"
                        />
                    </div>
                </div>
            `,
            this.root,
        );
    }

    renderView() {
        if (this._lat != null && this._lng != null) {
            render(html` <div>${this._lat}, ${this._lng}</div> `, this.root);
        } else {
            render(html` <div></div> `, this.root);
        }
    }

    scss() {
        return `
            :host {
                display: inline-block;

                input {
                    color: #2e3436;
                    font-size: 13px;
                    border-radius: 2px;
                    border: 0;
                    padding: 5px;
                    width: 75px;
                    vertical-align: middle;
                }

                #input {
                    display: flex;

                    #lat::after {
                        content: ',';
                        padding-right: 10px;
                        padding-left: 2px;
                        font-size: 1.2em;
                        font-weight: bold;
                    }
                }

                
            }
        `;
    }

    _updateLat(event) {
        const lat = parseFloat(event.target.value);
        if (lat >= -90 && lat <= 90) {
            this._lat = lat;
        }
        this.refresh();
    }

    _updateLng(event) {
        const lng = event.target.value;
        if (lng >= -180 && lng <= 180) {
            this._lng = lng;
        }
        this.refresh();
    }

    get value() {
        return [this._lat, this._lng];
    }

    set value(value) {
        // make sure value is an array of 2 numbers
        if (value == null) {
            this._lat = null;
            this._lng = null;
        } else if (Array.isArray(value) && value.length == 2 && !isNaN(value[0]) && !isNaN(value[1])) {
            this._lat = value[0];
            this._lng = value[1];
        } else {
            console.warn("Invalid coordinates value: " + value);
        }

        this.refresh();
    }

    /*
    // TODO address lookup
    async #addressCoordinates(address) {
        return OB.API.postPromise("metadata", "address_coordinates", { address: address }).then((response) => {
            if (response.status) {
                let result = response.data;
                return result.lat + ", " + result.lng;
            }
        });
    }
    */
}

customElements.define("ob-field-coordinates", OBFieldCoordinates);
