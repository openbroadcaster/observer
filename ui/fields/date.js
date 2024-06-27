import OBFieldDatetime from './datetime.js';

class OBFieldDate extends OBFieldDatetime {
    valueFormat = "YYYY-MM-DD";
    valueStringFormat = "MMM D, YYYY";
}

customElements.define('ob-field-date', OBFieldDate);
