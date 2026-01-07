/*
The MIT License

Copyright (c) 2013 Jake Rosoman <jkroso@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

/*
https://github.com/jkroso/parse-duration
Modified for OB/Browser use.
*/

'use strict'

var duration = /(-?(?:\d+\.?\d*|\d*\.?\d+)(?:e[-+]?\d+)?)\s*([a-zµμ]*)/ig

/**
 * conversion ratios
 */

parseDuration.nanosecond =
parseDuration.ns = 1 / 1e6

parseDuration['µs'] =
parseDuration['μs'] =
parseDuration.us =
parseDuration.microsecond = 1 / 1e3

parseDuration.millisecond =
parseDuration.ms = 1

parseDuration.second =
parseDuration.sec =
parseDuration.s = parseDuration.ms * 1000

parseDuration.minute =
parseDuration.min =
parseDuration.m = parseDuration.s * 60

parseDuration.hour =
parseDuration.hr =
parseDuration.h = parseDuration.m * 60

parseDuration.day =
parseDuration.d = parseDuration.h * 24

parseDuration.week =
parseDuration.wk =
parseDuration.w = parseDuration.d * 7

parseDuration.month =
parseDuration.b =
parseDuration.d * (365.25 / 12)

parseDuration.year =
parseDuration.yr =
parseDuration.y = parseDuration.d * 365.25

/**
 * convert `str` to ms
 *
 * @param {String} str
 * @param {String} format
 * @return {Number}
 */

function parseDuration(str='', format='ms'){
  var result = null
  // ignore commas
  str = str.replace(/(\d),(\d)/g, '$1$2')
  str.replace(duration, function(_, n, units){
    units = parseDuration[units] || parseDuration[units.toLowerCase().replace(/s$/, '')]
    if (units) result = (result || 0) + parseFloat(n, 10) * units
  })

  return result && (result / parseDuration[format])
}
