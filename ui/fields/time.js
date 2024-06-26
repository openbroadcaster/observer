import OBFieldDatetime from './datetime.js';

class OBFieldTime extends OBFieldDatetime {
    valueFormat = "HH:mm:ss";
    valueStringFormat = "h:mm A";
}

customElements.define('ob-field-time', OBFieldTime);
