// modifications to jQuery functions while we complete migration to lit and vanilla js

var _originalShow = jQuery.fn.show;
jQuery.fn.show = function (speed, callback) {
    if (this.length) {
        // Remove hidden attribute if present
        if (this[0].hasAttribute("hidden")) {
            this[0].removeAttribute("hidden");
        }

        // Try original show, but don't let it break our flow
        try {
            return _originalShow.apply(this, arguments);
        } catch (e) {
            console.warn("Original jQuery show() failed:", this, e);
            return this;
        }
    }
    return this;
};

var _originalHide = jQuery.fn.hide;
jQuery.fn.hide = function (speed, callback) {
    if (this.length) {
        // Add hidden attribute
        if (!this[0].hasAttribute("hidden")) {
            this[0].setAttribute("hidden", "");
        }

        // Try original hide, but don't let it break our flow
        try {
            return _originalHide.apply(this, arguments);
        } catch (e) {
            console.warn("Original jQuery hide() failed:", this, e);
            return this;
        }
    }
    return this;
};
