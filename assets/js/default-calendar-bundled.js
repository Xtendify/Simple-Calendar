/*! Simple Calendar - 3.1.46
 * https://simplecalendar.io
 * Copyright (c) Xtendify Technologies 2023
 * Licensed GPLv2+ */

(() => {
  var __create = Object.create;
  var __defProp = Object.defineProperty;
  var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __getProtoOf = Object.getPrototypeOf;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __commonJS = (cb, mod) => function __require() {
    return mod || (0, cb[__getOwnPropNames(cb)[0]])((mod = { exports: {} }).exports, mod), mod.exports;
  };
  var __copyProps = (to, from, except, desc) => {
    if (from && typeof from === "object" || typeof from === "function") {
      for (let key of __getOwnPropNames(from))
        if (!__hasOwnProp.call(to, key) && key !== except)
          __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
    }
    return to;
  };
  var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(
    // If the importer is in node compatibility mode or this is not an ESM
    // file that has been converted to a CommonJS file using a Babel-
    // compatible transform (i.e. "__esModule" has not been set), then set
    // "default" to the CommonJS "module.exports" for node compatibility.
    isNodeMode || !mod || !mod.__esModule ? __defProp(target, "default", { value: mod, enumerable: true }) : target,
    mod
  ));

  // node_modules/dayjs/dayjs.min.js
  var require_dayjs_min = __commonJS({
    "node_modules/dayjs/dayjs.min.js"(exports, module) {
      !function(t, e) {
        "object" == typeof exports && "undefined" != typeof module ? module.exports = e() : "function" == typeof define && define.amd ? define(e) : (t = "undefined" != typeof globalThis ? globalThis : t || self).dayjs = e();
      }(exports, function() {
        "use strict";
        var t = 1e3, e = 6e4, n = 36e5, r = "millisecond", i = "second", s = "minute", u = "hour", a = "day", o = "week", f = "month", h = "quarter", c = "year", d = "date", l = "Invalid Date", $ = /^(\d{4})[-/]?(\d{1,2})?[-/]?(\d{0,2})[Tt\s]*(\d{1,2})?:?(\d{1,2})?:?(\d{1,2})?[.:]?(\d+)?$/, y = /\[([^\]]+)]|Y{1,4}|M{1,4}|D{1,2}|d{1,4}|H{1,2}|h{1,2}|a|A|m{1,2}|s{1,2}|Z{1,2}|SSS/g, M = { name: "en", weekdays: "Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"), months: "January_February_March_April_May_June_July_August_September_October_November_December".split("_"), ordinal: function(t2) {
          var e2 = ["th", "st", "nd", "rd"], n2 = t2 % 100;
          return "[" + t2 + (e2[(n2 - 20) % 10] || e2[n2] || e2[0]) + "]";
        } }, m = function(t2, e2, n2) {
          var r2 = String(t2);
          return !r2 || r2.length >= e2 ? t2 : "" + Array(e2 + 1 - r2.length).join(n2) + t2;
        }, v = { s: m, z: function(t2) {
          var e2 = -t2.utcOffset(), n2 = Math.abs(e2), r2 = Math.floor(n2 / 60), i2 = n2 % 60;
          return (e2 <= 0 ? "+" : "-") + m(r2, 2, "0") + ":" + m(i2, 2, "0");
        }, m: function t2(e2, n2) {
          if (e2.date() < n2.date())
            return -t2(n2, e2);
          var r2 = 12 * (n2.year() - e2.year()) + (n2.month() - e2.month()), i2 = e2.clone().add(r2, f), s2 = n2 - i2 < 0, u2 = e2.clone().add(r2 + (s2 ? -1 : 1), f);
          return +(-(r2 + (n2 - i2) / (s2 ? i2 - u2 : u2 - i2)) || 0);
        }, a: function(t2) {
          return t2 < 0 ? Math.ceil(t2) || 0 : Math.floor(t2);
        }, p: function(t2) {
          return { M: f, y: c, w: o, d: a, D: d, h: u, m: s, s: i, ms: r, Q: h }[t2] || String(t2 || "").toLowerCase().replace(/s$/, "");
        }, u: function(t2) {
          return void 0 === t2;
        } }, g = "en", D = {};
        D[g] = M;
        var p = function(t2) {
          return t2 instanceof _;
        }, S = function t2(e2, n2, r2) {
          var i2;
          if (!e2)
            return g;
          if ("string" == typeof e2) {
            var s2 = e2.toLowerCase();
            D[s2] && (i2 = s2), n2 && (D[s2] = n2, i2 = s2);
            var u2 = e2.split("-");
            if (!i2 && u2.length > 1)
              return t2(u2[0]);
          } else {
            var a2 = e2.name;
            D[a2] = e2, i2 = a2;
          }
          return !r2 && i2 && (g = i2), i2 || !r2 && g;
        }, w = function(t2, e2) {
          if (p(t2))
            return t2.clone();
          var n2 = "object" == typeof e2 ? e2 : {};
          return n2.date = t2, n2.args = arguments, new _(n2);
        }, O = v;
        O.l = S, O.i = p, O.w = function(t2, e2) {
          return w(t2, { locale: e2.$L, utc: e2.$u, x: e2.$x, $offset: e2.$offset });
        };
        var _ = function() {
          function M2(t2) {
            this.$L = S(t2.locale, null, true), this.parse(t2);
          }
          var m2 = M2.prototype;
          return m2.parse = function(t2) {
            this.$d = function(t3) {
              var e2 = t3.date, n2 = t3.utc;
              if (null === e2)
                return /* @__PURE__ */ new Date(NaN);
              if (O.u(e2))
                return /* @__PURE__ */ new Date();
              if (e2 instanceof Date)
                return new Date(e2);
              if ("string" == typeof e2 && !/Z$/i.test(e2)) {
                var r2 = e2.match($);
                if (r2) {
                  var i2 = r2[2] - 1 || 0, s2 = (r2[7] || "0").substring(0, 3);
                  return n2 ? new Date(Date.UTC(r2[1], i2, r2[3] || 1, r2[4] || 0, r2[5] || 0, r2[6] || 0, s2)) : new Date(r2[1], i2, r2[3] || 1, r2[4] || 0, r2[5] || 0, r2[6] || 0, s2);
                }
              }
              return new Date(e2);
            }(t2), this.$x = t2.x || {}, this.init();
          }, m2.init = function() {
            var t2 = this.$d;
            this.$y = t2.getFullYear(), this.$M = t2.getMonth(), this.$D = t2.getDate(), this.$W = t2.getDay(), this.$H = t2.getHours(), this.$m = t2.getMinutes(), this.$s = t2.getSeconds(), this.$ms = t2.getMilliseconds();
          }, m2.$utils = function() {
            return O;
          }, m2.isValid = function() {
            return !(this.$d.toString() === l);
          }, m2.isSame = function(t2, e2) {
            var n2 = w(t2);
            return this.startOf(e2) <= n2 && n2 <= this.endOf(e2);
          }, m2.isAfter = function(t2, e2) {
            return w(t2) < this.startOf(e2);
          }, m2.isBefore = function(t2, e2) {
            return this.endOf(e2) < w(t2);
          }, m2.$g = function(t2, e2, n2) {
            return O.u(t2) ? this[e2] : this.set(n2, t2);
          }, m2.unix = function() {
            return Math.floor(this.valueOf() / 1e3);
          }, m2.valueOf = function() {
            return this.$d.getTime();
          }, m2.startOf = function(t2, e2) {
            var n2 = this, r2 = !!O.u(e2) || e2, h2 = O.p(t2), l2 = function(t3, e3) {
              var i2 = O.w(n2.$u ? Date.UTC(n2.$y, e3, t3) : new Date(n2.$y, e3, t3), n2);
              return r2 ? i2 : i2.endOf(a);
            }, $2 = function(t3, e3) {
              return O.w(n2.toDate()[t3].apply(n2.toDate("s"), (r2 ? [0, 0, 0, 0] : [23, 59, 59, 999]).slice(e3)), n2);
            }, y2 = this.$W, M3 = this.$M, m3 = this.$D, v2 = "set" + (this.$u ? "UTC" : "");
            switch (h2) {
              case c:
                return r2 ? l2(1, 0) : l2(31, 11);
              case f:
                return r2 ? l2(1, M3) : l2(0, M3 + 1);
              case o:
                var g2 = this.$locale().weekStart || 0, D2 = (y2 < g2 ? y2 + 7 : y2) - g2;
                return l2(r2 ? m3 - D2 : m3 + (6 - D2), M3);
              case a:
              case d:
                return $2(v2 + "Hours", 0);
              case u:
                return $2(v2 + "Minutes", 1);
              case s:
                return $2(v2 + "Seconds", 2);
              case i:
                return $2(v2 + "Milliseconds", 3);
              default:
                return this.clone();
            }
          }, m2.endOf = function(t2) {
            return this.startOf(t2, false);
          }, m2.$set = function(t2, e2) {
            var n2, o2 = O.p(t2), h2 = "set" + (this.$u ? "UTC" : ""), l2 = (n2 = {}, n2[a] = h2 + "Date", n2[d] = h2 + "Date", n2[f] = h2 + "Month", n2[c] = h2 + "FullYear", n2[u] = h2 + "Hours", n2[s] = h2 + "Minutes", n2[i] = h2 + "Seconds", n2[r] = h2 + "Milliseconds", n2)[o2], $2 = o2 === a ? this.$D + (e2 - this.$W) : e2;
            if (o2 === f || o2 === c) {
              var y2 = this.clone().set(d, 1);
              y2.$d[l2]($2), y2.init(), this.$d = y2.set(d, Math.min(this.$D, y2.daysInMonth())).$d;
            } else
              l2 && this.$d[l2]($2);
            return this.init(), this;
          }, m2.set = function(t2, e2) {
            return this.clone().$set(t2, e2);
          }, m2.get = function(t2) {
            return this[O.p(t2)]();
          }, m2.add = function(r2, h2) {
            var d2, l2 = this;
            r2 = Number(r2);
            var $2 = O.p(h2), y2 = function(t2) {
              var e2 = w(l2);
              return O.w(e2.date(e2.date() + Math.round(t2 * r2)), l2);
            };
            if ($2 === f)
              return this.set(f, this.$M + r2);
            if ($2 === c)
              return this.set(c, this.$y + r2);
            if ($2 === a)
              return y2(1);
            if ($2 === o)
              return y2(7);
            var M3 = (d2 = {}, d2[s] = e, d2[u] = n, d2[i] = t, d2)[$2] || 1, m3 = this.$d.getTime() + r2 * M3;
            return O.w(m3, this);
          }, m2.subtract = function(t2, e2) {
            return this.add(-1 * t2, e2);
          }, m2.format = function(t2) {
            var e2 = this, n2 = this.$locale();
            if (!this.isValid())
              return n2.invalidDate || l;
            var r2 = t2 || "YYYY-MM-DDTHH:mm:ssZ", i2 = O.z(this), s2 = this.$H, u2 = this.$m, a2 = this.$M, o2 = n2.weekdays, f2 = n2.months, h2 = function(t3, n3, i3, s3) {
              return t3 && (t3[n3] || t3(e2, r2)) || i3[n3].slice(0, s3);
            }, c2 = function(t3) {
              return O.s(s2 % 12 || 12, t3, "0");
            }, d2 = n2.meridiem || function(t3, e3, n3) {
              var r3 = t3 < 12 ? "AM" : "PM";
              return n3 ? r3.toLowerCase() : r3;
            }, $2 = { YY: String(this.$y).slice(-2), YYYY: this.$y, M: a2 + 1, MM: O.s(a2 + 1, 2, "0"), MMM: h2(n2.monthsShort, a2, f2, 3), MMMM: h2(f2, a2), D: this.$D, DD: O.s(this.$D, 2, "0"), d: String(this.$W), dd: h2(n2.weekdaysMin, this.$W, o2, 2), ddd: h2(n2.weekdaysShort, this.$W, o2, 3), dddd: o2[this.$W], H: String(s2), HH: O.s(s2, 2, "0"), h: c2(1), hh: c2(2), a: d2(s2, u2, true), A: d2(s2, u2, false), m: String(u2), mm: O.s(u2, 2, "0"), s: String(this.$s), ss: O.s(this.$s, 2, "0"), SSS: O.s(this.$ms, 3, "0"), Z: i2 };
            return r2.replace(y, function(t3, e3) {
              return e3 || $2[t3] || i2.replace(":", "");
            });
          }, m2.utcOffset = function() {
            return 15 * -Math.round(this.$d.getTimezoneOffset() / 15);
          }, m2.diff = function(r2, d2, l2) {
            var $2, y2 = O.p(d2), M3 = w(r2), m3 = (M3.utcOffset() - this.utcOffset()) * e, v2 = this - M3, g2 = O.m(this, M3);
            return g2 = ($2 = {}, $2[c] = g2 / 12, $2[f] = g2, $2[h] = g2 / 3, $2[o] = (v2 - m3) / 6048e5, $2[a] = (v2 - m3) / 864e5, $2[u] = v2 / n, $2[s] = v2 / e, $2[i] = v2 / t, $2)[y2] || v2, l2 ? g2 : O.a(g2);
          }, m2.daysInMonth = function() {
            return this.endOf(f).$D;
          }, m2.$locale = function() {
            return D[this.$L];
          }, m2.locale = function(t2, e2) {
            if (!t2)
              return this.$L;
            var n2 = this.clone(), r2 = S(t2, e2, true);
            return r2 && (n2.$L = r2), n2;
          }, m2.clone = function() {
            return O.w(this.$d, this);
          }, m2.toDate = function() {
            return new Date(this.valueOf());
          }, m2.toJSON = function() {
            return this.isValid() ? this.toISOString() : null;
          }, m2.toISOString = function() {
            return this.$d.toISOString();
          }, m2.toString = function() {
            return this.$d.toUTCString();
          }, M2;
        }(), T = _.prototype;
        return w.prototype = T, [["$ms", r], ["$s", i], ["$m", s], ["$H", u], ["$W", a], ["$M", f], ["$y", c], ["$D", d]].forEach(function(t2) {
          T[t2[1]] = function(e2) {
            return this.$g(e2, t2[0], t2[1]);
          };
        }), w.extend = function(t2, e2) {
          return t2.$i || (t2(e2, _, w), t2.$i = true), w;
        }, w.locale = S, w.isDayjs = p, w.unix = function(t2) {
          return w(1e3 * t2);
        }, w.en = D[g], w.Ls = D, w.p = {}, w;
      });
    }
  });

  // node_modules/dayjs/plugin/utc.js
  var require_utc = __commonJS({
    "node_modules/dayjs/plugin/utc.js"(exports, module) {
      !function(t, i) {
        "object" == typeof exports && "undefined" != typeof module ? module.exports = i() : "function" == typeof define && define.amd ? define(i) : (t = "undefined" != typeof globalThis ? globalThis : t || self).dayjs_plugin_utc = i();
      }(exports, function() {
        "use strict";
        var t = "minute", i = /[+-]\d\d(?::?\d\d)?/g, e = /([+-]|\d\d)/g;
        return function(s, f, n) {
          var u = f.prototype;
          n.utc = function(t2) {
            var i2 = { date: t2, utc: true, args: arguments };
            return new f(i2);
          }, u.utc = function(i2) {
            var e2 = n(this.toDate(), { locale: this.$L, utc: true });
            return i2 ? e2.add(this.utcOffset(), t) : e2;
          }, u.local = function() {
            return n(this.toDate(), { locale: this.$L, utc: false });
          };
          var o = u.parse;
          u.parse = function(t2) {
            t2.utc && (this.$u = true), this.$utils().u(t2.$offset) || (this.$offset = t2.$offset), o.call(this, t2);
          };
          var r = u.init;
          u.init = function() {
            if (this.$u) {
              var t2 = this.$d;
              this.$y = t2.getUTCFullYear(), this.$M = t2.getUTCMonth(), this.$D = t2.getUTCDate(), this.$W = t2.getUTCDay(), this.$H = t2.getUTCHours(), this.$m = t2.getUTCMinutes(), this.$s = t2.getUTCSeconds(), this.$ms = t2.getUTCMilliseconds();
            } else
              r.call(this);
          };
          var a = u.utcOffset;
          u.utcOffset = function(s2, f2) {
            var n2 = this.$utils().u;
            if (n2(s2))
              return this.$u ? 0 : n2(this.$offset) ? a.call(this) : this.$offset;
            if ("string" == typeof s2 && (s2 = function(t2) {
              void 0 === t2 && (t2 = "");
              var s3 = t2.match(i);
              if (!s3)
                return null;
              var f3 = ("" + s3[0]).match(e) || ["-", 0, 0], n3 = f3[0], u3 = 60 * +f3[1] + +f3[2];
              return 0 === u3 ? 0 : "+" === n3 ? u3 : -u3;
            }(s2), null === s2))
              return this;
            var u2 = Math.abs(s2) <= 16 ? 60 * s2 : s2, o2 = this;
            if (f2)
              return o2.$offset = u2, o2.$u = 0 === s2, o2;
            if (0 !== s2) {
              var r2 = this.$u ? this.toDate().getTimezoneOffset() : -1 * this.utcOffset();
              (o2 = this.local().add(u2 + r2, t)).$offset = u2, o2.$x.$localOffset = r2;
            } else
              o2 = this.utc();
            return o2;
          };
          var h = u.format;
          u.format = function(t2) {
            var i2 = t2 || (this.$u ? "YYYY-MM-DDTHH:mm:ss[Z]" : "");
            return h.call(this, i2);
          }, u.valueOf = function() {
            var t2 = this.$utils().u(this.$offset) ? 0 : this.$offset + (this.$x.$localOffset || this.$d.getTimezoneOffset());
            return this.$d.valueOf() - 6e4 * t2;
          }, u.isUTC = function() {
            return !!this.$u;
          }, u.toISOString = function() {
            return this.toDate().toISOString();
          }, u.toString = function() {
            return this.toDate().toUTCString();
          };
          var l = u.toDate;
          u.toDate = function(t2) {
            return "s" === t2 && this.$offset ? n(this.format("YYYY-MM-DD HH:mm:ss:SSS")).toDate() : l.call(this);
          };
          var c = u.diff;
          u.diff = function(t2, i2, e2) {
            if (t2 && this.$u === t2.$u)
              return c.call(this, t2, i2, e2);
            var s2 = this.local(), f2 = n(t2).local();
            return c.call(s2, f2, i2, e2);
          };
        };
      });
    }
  });

  // node_modules/dayjs/plugin/timezone.js
  var require_timezone = __commonJS({
    "node_modules/dayjs/plugin/timezone.js"(exports, module) {
      !function(t, e) {
        "object" == typeof exports && "undefined" != typeof module ? module.exports = e() : "function" == typeof define && define.amd ? define(e) : (t = "undefined" != typeof globalThis ? globalThis : t || self).dayjs_plugin_timezone = e();
      }(exports, function() {
        "use strict";
        var t = { year: 0, month: 1, day: 2, hour: 3, minute: 4, second: 5 }, e = {};
        return function(n, i, o) {
          var r, a = function(t2, n2, i2) {
            void 0 === i2 && (i2 = {});
            var o2 = new Date(t2), r2 = function(t3, n3) {
              void 0 === n3 && (n3 = {});
              var i3 = n3.timeZoneName || "short", o3 = t3 + "|" + i3, r3 = e[o3];
              return r3 || (r3 = new Intl.DateTimeFormat("en-US", { hour12: false, timeZone: t3, year: "numeric", month: "2-digit", day: "2-digit", hour: "2-digit", minute: "2-digit", second: "2-digit", timeZoneName: i3 }), e[o3] = r3), r3;
            }(n2, i2);
            return r2.formatToParts(o2);
          }, u = function(e2, n2) {
            for (var i2 = a(e2, n2), r2 = [], u2 = 0; u2 < i2.length; u2 += 1) {
              var f2 = i2[u2], s2 = f2.type, m = f2.value, c = t[s2];
              c >= 0 && (r2[c] = parseInt(m, 10));
            }
            var d = r2[3], l = 24 === d ? 0 : d, v = r2[0] + "-" + r2[1] + "-" + r2[2] + " " + l + ":" + r2[4] + ":" + r2[5] + ":000", h = +e2;
            return (o.utc(v).valueOf() - (h -= h % 1e3)) / 6e4;
          }, f = i.prototype;
          f.tz = function(t2, e2) {
            void 0 === t2 && (t2 = r);
            var n2 = this.utcOffset(), i2 = this.toDate(), a2 = i2.toLocaleString("en-US", { timeZone: t2 }), u2 = Math.round((i2 - new Date(a2)) / 1e3 / 60), f2 = o(a2).$set("millisecond", this.$ms).utcOffset(15 * -Math.round(i2.getTimezoneOffset() / 15) - u2, true);
            if (e2) {
              var s2 = f2.utcOffset();
              f2 = f2.add(n2 - s2, "minute");
            }
            return f2.$x.$timezone = t2, f2;
          }, f.offsetName = function(t2) {
            var e2 = this.$x.$timezone || o.tz.guess(), n2 = a(this.valueOf(), e2, { timeZoneName: t2 }).find(function(t3) {
              return "timezonename" === t3.type.toLowerCase();
            });
            return n2 && n2.value;
          };
          var s = f.startOf;
          f.startOf = function(t2, e2) {
            if (!this.$x || !this.$x.$timezone)
              return s.call(this, t2, e2);
            var n2 = o(this.format("YYYY-MM-DD HH:mm:ss:SSS"));
            return s.call(n2, t2, e2).tz(this.$x.$timezone, true);
          }, o.tz = function(t2, e2, n2) {
            var i2 = n2 && e2, a2 = n2 || e2 || r, f2 = u(+o(), a2);
            if ("string" != typeof t2)
              return o(t2).tz(a2);
            var s2 = function(t3, e3, n3) {
              var i3 = t3 - 60 * e3 * 1e3, o2 = u(i3, n3);
              if (e3 === o2)
                return [i3, e3];
              var r2 = u(i3 -= 60 * (o2 - e3) * 1e3, n3);
              return o2 === r2 ? [i3, o2] : [t3 - 60 * Math.min(o2, r2) * 1e3, Math.max(o2, r2)];
            }(o.utc(t2, i2).valueOf(), f2, a2), m = s2[0], c = s2[1], d = o(m).utcOffset(c);
            return d.$x.$timezone = a2, d;
          }, o.tz.guess = function() {
            return Intl.DateTimeFormat().resolvedOptions().timeZone;
          }, o.tz.setDefault = function(t2) {
            r = t2;
          };
        };
      });
    }
  });

  // assets/js/default-calendar.js
  var import_dayjs = __toESM(require_dayjs_min());
  var import_utc = __toESM(require_utc());
  var import_timezone = __toESM(require_timezone());
  import_dayjs.default.extend(import_utc.default);
  import_dayjs.default.extend(import_timezone.default);
  jQuery(function($) {
    $(".simcal-default-calendar").each(function(e, i) {
      var calendar = $(i), id = calendar.data("calendar-id"), offset = calendar.data("offset"), start = calendar.data("events-first"), end = calendar.data("calendar-end"), nav = calendar.find(".simcal-calendar-head"), buttons = nav.find(".simcal-nav-button"), spinner = calendar.find(".simcal-ajax-loader"), current = nav.find(".simcal-current"), currentTime = current.data("calendar-current"), currentMonth = current.find("span.simcal-current-month"), currentYear = current.find("span.simcal-current-year"), currentDate = (0, import_dayjs.default)(currentTime * 1e3).tz(calendar.data("timezone")), date, action;
      if (calendar.hasClass("simcal-default-calendar-grid")) {
        action = "simcal_default_calendar_draw_grid";
        date = new Date(currentDate.year(), currentDate.month());
        toggleGridNavButtons(buttons, date.getTime() / 1e3, start, end);
      } else {
        action = "simcal_default_calendar_draw_list";
        toggleListNavButtons(buttons, calendar, start, end, false, currentTime);
        toggleListHeading(calendar);
      }
      buttons.on("click", function() {
        var direction = $(this).hasClass("simcal-next") ? "next" : "prev";
        if (action == "simcal_default_calendar_draw_grid") {
          var body = calendar.find(".simcal-month"), newDate, month, year;
          if ("prev" == direction) {
            newDate = new Date(date.setMonth(date.getMonth() - 1, 1));
          } else {
            newDate = new Date(date.setMonth(date.getMonth() + 2, 1));
            newDate.setDate(0);
            newDate.setHours(23);
            newDate.setMinutes(59);
            newDate.setSeconds(59);
          }
          month = newDate.getMonth();
          year = newDate.getFullYear();
          $.ajax({
            url: simcal_default_calendar.ajax_url,
            type: "POST",
            dataType: "json",
            cache: false,
            data: {
              action,
              month: month + 1,
              // month count in PHP goes 1-12 vs 0-11 in JavaScript
              year,
              id
            },
            beforeSend: function() {
              spinner.fadeToggle();
            },
            success: function(response) {
              currentMonth.text(simcal_default_calendar.months.full[month]);
              currentYear.text(year);
              current.attr(
                "data-calendar-current",
                newDate.getTime() / 1e3 + offset + 1
              );
              toggleGridNavButtons(buttons, newDate.getTime() / 1e3, start, end);
              spinner.fadeToggle();
              date = newDate;
              body.replaceWith(response.data);
              calendarBubbles(calendar, list);
              expandEventsToggle();
            },
            error: function(response) {
              console.log(response);
            }
          });
        } else {
          var list = calendar.find(".simcal-events-list-container"), prev = list.data("prev"), next = list.data("next"), timestamp = direction == "prev" ? prev : next;
          $.ajax({
            url: simcal_default_calendar.ajax_url,
            type: "POST",
            dataType: "json",
            cache: false,
            data: {
              action,
              ts: timestamp,
              id
            },
            beforeSend: function() {
              spinner.fadeToggle();
            },
            success: function(response) {
              list.replaceWith(response.data);
              current.attr("data-calendar-current", timestamp);
              toggleListHeading(calendar);
              toggleListNavButtons(
                buttons,
                calendar,
                start,
                end,
                direction,
                timestamp
              );
              spinner.fadeToggle();
              expandEventsToggle();
            },
            error: function(response) {
              console.log(response);
            }
          });
        }
      });
    });
    function toggleGridNavButtons(buttons, time, min, max) {
      buttons.each(function(e, i) {
        var button = $(i), month = new Date(time * 1e3);
        if (button.hasClass("simcal-prev")) {
          month = new Date(month.setMonth(month.getMonth(), 1));
          month.setDate(0);
          if (month.getTime() / 1e3 <= min) {
            button.attr("disabled", "disabled");
          } else {
            button.removeAttr("disabled");
          }
        } else {
          month = new Date(month.setMonth(month.getMonth() + 1, 1));
          month.setDate(0);
          month.setHours(23);
          month.setMinutes(59);
          month.setSeconds(59);
          if (month.getTime() / 1e3 >= max) {
            button.attr("disabled", "disabled");
          } else {
            button.removeAttr("disabled");
          }
        }
      });
    }
    function toggleListNavButtons(buttons, calendar, start, end, direction, currentTime) {
      var list = calendar.find(".simcal-events-list-container"), prev = list.data("prev"), next = list.data("next"), last_event = list.find("li.simcal-event:last").data("start");
      buttons.each(function(e, b) {
        var button = $(b);
        if (direction) {
          if (button.hasClass("simcal-prev")) {
            if (direction == "prev") {
              if (prev <= start && currentTime <= start) {
                button.attr("disabled", "disabled");
              }
            } else {
              button.removeAttr("disabled");
            }
          } else if (button.hasClass("simcal-next")) {
            if (direction == "next") {
              if (next >= end && currentTime >= end || last_event >= end) {
                button.attr("disabled", "disabled");
              }
            } else {
              button.removeAttr("disabled");
            }
          }
        } else {
          if (button.hasClass("simcal-prev")) {
            if (prev <= start && currentTime <= start) {
              button.attr("disabled", "disabled");
            }
          } else if (button.hasClass("simcal-next")) {
            if (next >= end && currentTime >= end || last_event >= end) {
              button.attr("disabled", "disabled");
            }
          }
        }
      });
    }
    function toggleListHeading(calendar) {
      var current = $(calendar).find(".simcal-current"), heading = $(calendar).find(".simcal-events-list-container"), small = heading.data("heading-small"), large = heading.data("heading-large"), newHeading = $("<h3 />");
      if (calendar.width() < 400) {
        newHeading.text(small);
      } else {
        newHeading.text(large);
      }
      current.html(newHeading);
    }
    var gridCalendars = $(".simcal-default-calendar-grid");
    function calendarBubbles(calendar) {
      var table = $(calendar).find("> table"), thead = table.find("thead"), weekDayNames = thead.find("th.simcal-week-day"), cells = table.find("td.simcal-day > div"), eventsList = table.find("ul.simcal-events"), eventTitles = eventsList.find("> li > .simcal-event-title"), eventsToggle = table.find(".simcal-events-toggle"), eventsDots = table.find("span.simcal-events-dots"), events = table.find(".simcal-tooltip-content"), hiddenEvents = table.find(".simcal-event-toggled"), bubbleTrigger = table.data("event-bubble-trigger"), width = cells.first().width();
      if (width < 60) {
        weekDayNames.each(function(e, w) {
          $(w).text($(w).data("screen-small"));
        });
        eventsList.hide();
        eventTitles.hide();
        if (eventsToggle != "undefined") {
          eventsToggle.hide();
          if (hiddenEvents != "undefined") {
            hiddenEvents.show();
          }
        }
        eventsDots.show();
        bubbleTrigger = "click";
        var minH = width - 10 + "px";
        cells.css("min-height", minH);
        table.find("span.simcal-events-dots:not(:empty)").css("min-height", minH);
      } else {
        if (width <= 240) {
          weekDayNames.each(function(e, w) {
            $(w).text($(w).data("screen-medium"));
          });
        } else {
          weekDayNames.each(function(e, w) {
            $(w).text($(w).data("screen-large"));
          });
        }
        eventsList.show();
        eventTitles.show();
        if (eventsToggle != "undefined") {
          eventsToggle.show();
          if (hiddenEvents != "undefined") {
            hiddenEvents.hide();
          }
        }
        eventsDots.hide();
        cells.css("min-height", width + "px");
      }
      cells.each(function(e, cell) {
        var cellDots = $(cell).find("span.simcal-events-dots"), tooltips = $(cell).find(".simcal-tooltip"), eventBubbles, content, last;
        if (width < 60) {
          events.show();
          eventBubbles = cellDots;
        } else {
          events.hide();
          eventBubbles = tooltips;
        }
        eventBubbles.each(function(e2, i) {
          $(i).qtip({
            content: width < 60 ? $(cell).find("ul.simcal-events") : $(i).find("> .simcal-tooltip-content"),
            position: {
              my: "top center",
              at: "bottom center",
              target: $(i),
              viewport: width < 60 ? $(window) : true,
              adjust: {
                method: "shift",
                scroll: false
              }
            },
            style: {
              def: false,
              classes: "simcal-default-calendar simcal-event-bubble"
            },
            show: {
              solo: true,
              effect: false,
              event: bubbleTrigger == "hover" ? "mouseenter" : "click"
            },
            hide: {
              fixed: true,
              effect: false,
              event: bubbleTrigger == "click" ? "unfocus" : "mouseleave",
              delay: 100
            },
            events: {
              show: function(event, current) {
                if (last && last.id) {
                  if (last.id != current.id) {
                    last.hide();
                  }
                }
                last = current;
              }
            },
            overwrite: false
          });
        });
      });
    }
    gridCalendars.each(function(e, calendar) {
      calendarBubbles(calendar);
      $(calendar).on("change", function() {
        calendarBubbles(this);
      });
    });
    window.onresize = function() {
      gridCalendars.each(function(e, calendar) {
        calendarBubbles(calendar);
      });
    };
    function expandEventsToggle() {
      $(".simcal-events-toggle").each(function(e, button) {
        var list = $(button).prev(".simcal-events"), toggled = list.find(".simcal-event-toggled"), arrow = $(button).find("i");
        $(button).on("click", function() {
          arrow.toggleClass("simcal-icon-rotate-180");
          toggled.slideToggle();
        });
      });
    }
    expandEventsToggle();
  });
})();
//# sourceMappingURL=default-calendar-bundled.js.map
