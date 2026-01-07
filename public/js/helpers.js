// see for more info re: append/prepend - http://stackoverflow.com/questions/9134686/adding-code-to-a-javascript-function-programatically
// call function 'f2' at the beginning of 'f1'.
function function_prepend(f1, f2) {
    eval(
        f1 +
            " = (function() { \
      var cached_function = " +
            f1 +
            "; \
      return function() { \
          " +
            f2 +
            "(); \
          cached_function.apply(this, arguments); \
      }; \
  }());",
    );
}

// call function 'f2' at the end of 'f1'.
function function_append(f1, f2) {
    eval(
        f1 +
            " = (function() { \
      var cached_function = " +
            f1 +
            "; \
      return function() { \
          cached_function.apply(this, arguments); \
          " +
            f2 +
            "(); \
      }; \
  }());",
    );
}

/*
function htmlspecialchars(str) {
    if(typeof str == 'string') return $('<span>').text(str).html();
    else return '';
}
*/

function htmlspecialchars(string, quote_style, charset, double_encode) {
    if (string == null) {
        return "";
    }

    // Convert special characters to HTML entities
    //
    // version: 1101.3117
    // discuss at: http://phpjs.org/functions/htmlspecialchars    // +   original by: Mirek Slugen
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Nathan
    // +   bugfixed by: Arno
    // +    revised by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // +      input by: Ratheous
    // +      input by: Mailfaker (http://www.weedem.fr/)
    // +      reimplemented by: Brett Zamir (http://brett-zamir.me)
    // +      input by: felix    // +    bugfixed by: Brett Zamir (http://brett-zamir.me)
    // %        note 1: charset argument not supported
    // *     example 1: htmlspecialchars("<a href='test'>Test</a>", 'ENT_QUOTES');
    // *     returns 1: '&lt;a href=&#039;test&#039;&gt;Test&lt;/a&gt;'
    // *     example 2: htmlspecialchars("ab\"c'd", ['ENT_NOQUOTES', 'ENT_QUOTES']);    // *     returns 2: 'ab"c&#039;d'
    // *     example 3: htmlspecialchars("my "&entity;" is still here", null, null, false);
    // *     returns 3: 'my &quot;&entity;&quot; is still here'
    var optTemp = 0,
        i = 0,
        noquotes = false;
    if (typeof quote_style === "undefined" || quote_style === null) {
        quote_style = 2;
    }
    string = string.toString();
    if (double_encode !== false) {
        // Put this first to avoid double-encoding
        string = string.replace(/&/g, "&amp;");
    }
    string = string.replace(/</g, "&lt;").replace(/>/g, "&gt;");

    var OPTS = {
        ENT_NOQUOTES: 0,
        ENT_HTML_QUOTE_SINGLE: 1,
        ENT_HTML_QUOTE_DOUBLE: 2,
        ENT_COMPAT: 2,
        ENT_QUOTES: 3,
        ENT_IGNORE: 4,
    };
    if (quote_style === 0) {
        noquotes = true;
    }
    if (typeof quote_style !== "number") {
        // Allow for a single string or an array of string flags        quote_style = [].concat(quote_style);
        for (i = 0; i < quote_style.length; i++) {
            // Resolve string input to bitwise e.g. 'PATHINFO_EXTENSION' becomes 4
            if (OPTS[quote_style[i]] === 0) {
                noquotes = true;
            } else if (OPTS[quote_style[i]]) {
                optTemp = optTemp | OPTS[quote_style[i]];
            }
        }
        quote_style = optTemp;
    }
    if (quote_style & OPTS.ENT_HTML_QUOTE_SINGLE) {
        string = string.replace(/'/g, "&#039;");
    }
    if (!noquotes) {
        string = string.replace(/"/g, "&quot;");
    }

    return string;
}

// http://snipplr.com/view/20348/time-in-seconds-to-hmmss/http://snipplr.com/view/20348/time-in-seconds-to-hmmss/
function secsToTime(d, format) {
    d = Number(d);
    d = Math.round(d);
    var h = Math.floor(d / 3600);
    var m = Math.floor((d % 3600) / 60);
    var s = Math.floor((d % 3600) % 60);
    // return ((h > 0 ? h + ":" : "") + (m > 0 ? (h > 0 && m < 10 ? "0" : "") + m + ":" : "0:") + (s < 10 ? "0" : "") + s);

    var seph = "";
    var sepm = "";
    var seps = "";

    if (format == "hms") {
        seph = "h";
        sepm = "m";
        seps = "s";
    } else {
        seph = ":";
        sepm = ":";
    }

    if (format == "hms") {
        var v = "";

        if (h > 0) v = v + h + seph;
        if (m > 0) v = v + m + sepm;
        if (s > 0) v = v + s + seps;
    } else {
        m = timepad(m);
        s = timepad(s);

        var v = m + sepm + s + seps;
        if (h > 0) v = h + seph + v;
    }

    return v;
}

// format timestamp into local date/time
function format_timestamp(unix_timestamp) {
    if (unix_timestamp < 1) return "";

    var date = new Date(unix_timestamp * 1000);

    return (
        date.getFullYear() +
        "-" +
        timepad(date.getMonth() + 1) +
        "-" +
        timepad(date.getDate()) +
        " " +
        timepad(date.getHours()) +
        ":" +
        timepad(date.getMinutes()) +
        ":" +
        timepad(date.getSeconds())
    );
}

// https://stackoverflow.com/questions/7327046/jquery-number-formatting
function format_number(nStr) {
    nStr += "";
    x = nStr.split(".");
    x1 = x[0];
    x2 = x.length > 1 ? "." + x[1] : "";
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, "$1" + "," + "$2");
    }
    return x1 + x2;
}

// Numeric only control handler - http://stackoverflow.com/questions/995183/how-to-allow-only-numeric-0-9-in-html-inputbox-using-jquery
jQuery.fn.ForceNumericOnly = function () {
    return this.each(function () {
        $(this).keydown(function (e) {
            var key = e.charCode || e.keyCode || 0;
            // allow backspace, tab, delete, arrows, numbers and keypad numbers ONLY
            return (
                key == 8 ||
                key == 9 ||
                key == 46 ||
                (key >= 37 && key <= 40) ||
                (key >= 48 && key <= 57) ||
                (key >= 96 && key <= 105)
            );
        });
    });
};

// str pad with length = 2, fill = '0', left-fill.
function timepad(val) {
    while (val.toString().length < 2) val = "0" + val;
    return val;
}

function month_name(number) {
    var months = Array();

    months.push("January");
    months.push("February");
    months.push("March");
    months.push("April");
    months.push("May");
    months.push("June");
    months.push("July");
    months.push("August");
    months.push("September");
    months.push("October");
    months.push("November");
    months.push("December");

    return months[number];
}

// http://www.hardcode.nl/subcategory_1/article_414-copy-or-clone-javascript-array-object
function CloneObject(source) {
    for (i in source) {
        if (typeof source[i] == "source") {
            this[i] = new cloneObject(source[i]);
        } else {
            this[i] = source[i];
        }
    }
}

// clear highlight: http://bytes.com/topic/javascript/answers/635488-prevent-text-selection-after-double-click
function clearSelection() {
    var sel;
    if (document.selection && document.selection.empty) {
        document.selection.empty();
    } else if (window.getSelection) {
        sel = window.getSelection();
        if (sel && sel.removeAllRanges) sel.removeAllRanges();
    }
}

// get outer HTML
(function ($) {
    $.fn.outerHTML = function () {
        return $(this).clone().wrap("<div></div>").parent().html();
    };
})(jQuery);

function nl2br(str) {
    var breakTag = "<br>";
    return (str + "").replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, "$1" + breakTag + "$2");
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(";");
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == " ") c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

// $('#someid').getAttributes();
// http://stackoverflow.com/questions/2048720/get-all-attributes-from-a-html-element-with-javascript-jquery
(function ($) {
    $.fn.getAttributes = function () {
        var attributes = {};

        if (this.length) {
            $.each(this[0].attributes, function (index, attr) {
                attributes[attr.name] = attr.value;
            });
        }

        return attributes;
    };
})(jQuery);

// https://stackoverflow.com/questions/1125292/how-to-move-cursor-to-end-of-contenteditable-entity/3866442#3866442
// move cursor to end of contenteditable
function setEndOfContenteditable(contentEditableElement) {
    var range, selection;
    if (document.createRange) {
        //Firefox, Chrome, Opera, Safari, IE 9+
        range = document.createRange(); //Create a range (a range is a like the selection but invisible)
        range.selectNodeContents(contentEditableElement); //Select the entire contents of the element with the range
        range.collapse(false); //collapse the range to the end point. false means collapse to end rather than the start
        selection = window.getSelection(); //get the selection object (allows you to change selection)
        selection.removeAllRanges(); //remove any selections already made
        selection.addRange(range); //make the range you have just created the visible selection
    } else if (document.selection) {
        //IE 8 and lower
        range = document.body.createTextRange(); //Create a range (a range is a like the selection but invisible)
        range.moveToElementText(contentEditableElement); //Select the entire contents of the element with the range
        range.collapse(false); //collapse the range to the end point. false means collapse to end rather than the start
        range.select(); //Select the range (make it the visible selection
    }
}

/**
 * https://ourcodeworld.com/articles/read/482/how-to-execute-a-function-from-its-string-name-execute-function-by-name-in-javascript
 *
 * Returns the function that you want to execute through its name.
 * It returns undefined if the function || property doesn't exists
 *
 * @param functionName {String}
 * @param context {Object || null}
 */
function getFunctionByName(functionName, context) {
    // If using Node.js, the context will be an empty object
    if (typeof window == "undefined") {
        context = context || global;
    } else {
        // Use the window (from browser) as context if none providen.
        context = context || window;
    }

    // Retrieve the namespaces of the function you want to execute
    // e.g Namespaces of "MyLib.UI.alerti" would be ["MyLib","UI"]
    var namespaces = functionName.split(".");

    // Retrieve the real name of the function i.e alerti
    var functionToExecute = namespaces.pop();

    // Iterate through every namespace to access the one that has the function
    // you want to execute. For example with the alert fn "MyLib.UI.SomeSub.alert"
    // Loop until context will be equal to SomeSub
    for (var i = 0; i < namespaces.length; i++) {
        context = context[namespaces[i]];
    }

    // If the context really exists (namespaces), return the function or property
    if (context) {
        return context[functionToExecute];
    } else {
        return undefined;
    }
}
