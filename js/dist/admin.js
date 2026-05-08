/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./node_modules/dayjs/dayjs.min.js"
/*!*****************************************!*\
  !*** ./node_modules/dayjs/dayjs.min.js ***!
  \*****************************************/
(module) {

!function (t, e) {
   true ? module.exports = e() : 0;
}(this, function () {
  "use strict";

  var t = 1e3,
    e = 6e4,
    n = 36e5,
    r = "millisecond",
    i = "second",
    s = "minute",
    u = "hour",
    a = "day",
    o = "week",
    c = "month",
    f = "quarter",
    h = "year",
    d = "date",
    l = "Invalid Date",
    $ = /^(\d{4})[-/]?(\d{1,2})?[-/]?(\d{0,2})[Tt\s]*(\d{1,2})?:?(\d{1,2})?:?(\d{1,2})?[.:]?(\d+)?$/,
    y = /\[([^\]]+)]|Y{1,4}|M{1,4}|D{1,2}|d{1,4}|H{1,2}|h{1,2}|a|A|m{1,2}|s{1,2}|Z{1,2}|SSS/g,
    M = {
      name: "en",
      weekdays: "Sunday_Monday_Tuesday_Wednesday_Thursday_Friday_Saturday".split("_"),
      months: "January_February_March_April_May_June_July_August_September_October_November_December".split("_"),
      ordinal: function (t) {
        var e = ["th", "st", "nd", "rd"],
          n = t % 100;
        return "[" + t + (e[(n - 20) % 10] || e[n] || e[0]) + "]";
      }
    },
    m = function (t, e, n) {
      var r = String(t);
      return !r || r.length >= e ? t : "" + Array(e + 1 - r.length).join(n) + t;
    },
    v = {
      s: m,
      z: function (t) {
        var e = -t.utcOffset(),
          n = Math.abs(e),
          r = Math.floor(n / 60),
          i = n % 60;
        return (e <= 0 ? "+" : "-") + m(r, 2, "0") + ":" + m(i, 2, "0");
      },
      m: function t(e, n) {
        if (e.date() < n.date()) return -t(n, e);
        var r = 12 * (n.year() - e.year()) + (n.month() - e.month()),
          i = e.clone().add(r, c),
          s = n - i < 0,
          u = e.clone().add(r + (s ? -1 : 1), c);
        return +(-(r + (n - i) / (s ? i - u : u - i)) || 0);
      },
      a: function (t) {
        return t < 0 ? Math.ceil(t) || 0 : Math.floor(t);
      },
      p: function (t) {
        return {
          M: c,
          y: h,
          w: o,
          d: a,
          D: d,
          h: u,
          m: s,
          s: i,
          ms: r,
          Q: f
        }[t] || String(t || "").toLowerCase().replace(/s$/, "");
      },
      u: function (t) {
        return void 0 === t;
      }
    },
    g = "en",
    D = {};
  D[g] = M;
  var p = "$isDayjsObject",
    S = function (t) {
      return t instanceof _ || !(!t || !t[p]);
    },
    w = function t(e, n, r) {
      var i;
      if (!e) return g;
      if ("string" == typeof e) {
        var s = e.toLowerCase();
        D[s] && (i = s), n && (D[s] = n, i = s);
        var u = e.split("-");
        if (!i && u.length > 1) return t(u[0]);
      } else {
        var a = e.name;
        D[a] = e, i = a;
      }
      return !r && i && (g = i), i || !r && g;
    },
    O = function (t, e) {
      if (S(t)) return t.clone();
      var n = "object" == typeof e ? e : {};
      return n.date = t, n.args = arguments, new _(n);
    },
    b = v;
  b.l = w, b.i = S, b.w = function (t, e) {
    return O(t, {
      locale: e.$L,
      utc: e.$u,
      x: e.$x,
      $offset: e.$offset
    });
  };
  var _ = function () {
      function M(t) {
        this.$L = w(t.locale, null, !0), this.parse(t), this.$x = this.$x || t.x || {}, this[p] = !0;
      }
      var m = M.prototype;
      return m.parse = function (t) {
        this.$d = function (t) {
          var e = t.date,
            n = t.utc;
          if (null === e) return new Date(NaN);
          if (b.u(e)) return new Date();
          if (e instanceof Date) return new Date(e);
          if ("string" == typeof e && !/Z$/i.test(e)) {
            var r = e.match($);
            if (r) {
              var i = r[2] - 1 || 0,
                s = (r[7] || "0").substring(0, 3);
              return n ? new Date(Date.UTC(r[1], i, r[3] || 1, r[4] || 0, r[5] || 0, r[6] || 0, s)) : new Date(r[1], i, r[3] || 1, r[4] || 0, r[5] || 0, r[6] || 0, s);
            }
          }
          return new Date(e);
        }(t), this.init();
      }, m.init = function () {
        var t = this.$d;
        this.$y = t.getFullYear(), this.$M = t.getMonth(), this.$D = t.getDate(), this.$W = t.getDay(), this.$H = t.getHours(), this.$m = t.getMinutes(), this.$s = t.getSeconds(), this.$ms = t.getMilliseconds();
      }, m.$utils = function () {
        return b;
      }, m.isValid = function () {
        return !(this.$d.toString() === l);
      }, m.isSame = function (t, e) {
        var n = O(t);
        return this.startOf(e) <= n && n <= this.endOf(e);
      }, m.isAfter = function (t, e) {
        return O(t) < this.startOf(e);
      }, m.isBefore = function (t, e) {
        return this.endOf(e) < O(t);
      }, m.$g = function (t, e, n) {
        return b.u(t) ? this[e] : this.set(n, t);
      }, m.unix = function () {
        return Math.floor(this.valueOf() / 1e3);
      }, m.valueOf = function () {
        return this.$d.getTime();
      }, m.startOf = function (t, e) {
        var n = this,
          r = !!b.u(e) || e,
          f = b.p(t),
          l = function (t, e) {
            var i = b.w(n.$u ? Date.UTC(n.$y, e, t) : new Date(n.$y, e, t), n);
            return r ? i : i.endOf(a);
          },
          $ = function (t, e) {
            return b.w(n.toDate()[t].apply(n.toDate("s"), (r ? [0, 0, 0, 0] : [23, 59, 59, 999]).slice(e)), n);
          },
          y = this.$W,
          M = this.$M,
          m = this.$D,
          v = "set" + (this.$u ? "UTC" : "");
        switch (f) {
          case h:
            return r ? l(1, 0) : l(31, 11);
          case c:
            return r ? l(1, M) : l(0, M + 1);
          case o:
            var g = this.$locale().weekStart || 0,
              D = (y < g ? y + 7 : y) - g;
            return l(r ? m - D : m + (6 - D), M);
          case a:
          case d:
            return $(v + "Hours", 0);
          case u:
            return $(v + "Minutes", 1);
          case s:
            return $(v + "Seconds", 2);
          case i:
            return $(v + "Milliseconds", 3);
          default:
            return this.clone();
        }
      }, m.endOf = function (t) {
        return this.startOf(t, !1);
      }, m.$set = function (t, e) {
        var n,
          o = b.p(t),
          f = "set" + (this.$u ? "UTC" : ""),
          l = (n = {}, n[a] = f + "Date", n[d] = f + "Date", n[c] = f + "Month", n[h] = f + "FullYear", n[u] = f + "Hours", n[s] = f + "Minutes", n[i] = f + "Seconds", n[r] = f + "Milliseconds", n)[o],
          $ = o === a ? this.$D + (e - this.$W) : e;
        if (o === c || o === h) {
          var y = this.clone().set(d, 1);
          y.$d[l]($), y.init(), this.$d = y.set(d, Math.min(this.$D, y.daysInMonth())).$d;
        } else l && this.$d[l]($);
        return this.init(), this;
      }, m.set = function (t, e) {
        return this.clone().$set(t, e);
      }, m.get = function (t) {
        return this[b.p(t)]();
      }, m.add = function (r, f) {
        var d,
          l = this;
        r = Number(r);
        var $ = b.p(f),
          y = function (t) {
            var e = O(l);
            return b.w(e.date(e.date() + Math.round(t * r)), l);
          };
        if ($ === c) return this.set(c, this.$M + r);
        if ($ === h) return this.set(h, this.$y + r);
        if ($ === a) return y(1);
        if ($ === o) return y(7);
        var M = (d = {}, d[s] = e, d[u] = n, d[i] = t, d)[$] || 1,
          m = this.$d.getTime() + r * M;
        return b.w(m, this);
      }, m.subtract = function (t, e) {
        return this.add(-1 * t, e);
      }, m.format = function (t) {
        var e = this,
          n = this.$locale();
        if (!this.isValid()) return n.invalidDate || l;
        var r = t || "YYYY-MM-DDTHH:mm:ssZ",
          i = b.z(this),
          s = this.$H,
          u = this.$m,
          a = this.$M,
          o = n.weekdays,
          c = n.months,
          f = n.meridiem,
          h = function (t, n, i, s) {
            return t && (t[n] || t(e, r)) || i[n].slice(0, s);
          },
          d = function (t) {
            return b.s(s % 12 || 12, t, "0");
          },
          $ = f || function (t, e, n) {
            var r = t < 12 ? "AM" : "PM";
            return n ? r.toLowerCase() : r;
          };
        return r.replace(y, function (t, r) {
          return r || function (t) {
            switch (t) {
              case "YY":
                return String(e.$y).slice(-2);
              case "YYYY":
                return b.s(e.$y, 4, "0");
              case "M":
                return a + 1;
              case "MM":
                return b.s(a + 1, 2, "0");
              case "MMM":
                return h(n.monthsShort, a, c, 3);
              case "MMMM":
                return h(c, a);
              case "D":
                return e.$D;
              case "DD":
                return b.s(e.$D, 2, "0");
              case "d":
                return String(e.$W);
              case "dd":
                return h(n.weekdaysMin, e.$W, o, 2);
              case "ddd":
                return h(n.weekdaysShort, e.$W, o, 3);
              case "dddd":
                return o[e.$W];
              case "H":
                return String(s);
              case "HH":
                return b.s(s, 2, "0");
              case "h":
                return d(1);
              case "hh":
                return d(2);
              case "a":
                return $(s, u, !0);
              case "A":
                return $(s, u, !1);
              case "m":
                return String(u);
              case "mm":
                return b.s(u, 2, "0");
              case "s":
                return String(e.$s);
              case "ss":
                return b.s(e.$s, 2, "0");
              case "SSS":
                return b.s(e.$ms, 3, "0");
              case "Z":
                return i;
            }
            return null;
          }(t) || i.replace(":", "");
        });
      }, m.utcOffset = function () {
        return 15 * -Math.round(this.$d.getTimezoneOffset() / 15);
      }, m.diff = function (r, d, l) {
        var $,
          y = this,
          M = b.p(d),
          m = O(r),
          v = (m.utcOffset() - this.utcOffset()) * e,
          g = this - m,
          D = function () {
            return b.m(y, m);
          };
        switch (M) {
          case h:
            $ = D() / 12;
            break;
          case c:
            $ = D();
            break;
          case f:
            $ = D() / 3;
            break;
          case o:
            $ = (g - v) / 6048e5;
            break;
          case a:
            $ = (g - v) / 864e5;
            break;
          case u:
            $ = g / n;
            break;
          case s:
            $ = g / e;
            break;
          case i:
            $ = g / t;
            break;
          default:
            $ = g;
        }
        return l ? $ : b.a($);
      }, m.daysInMonth = function () {
        return this.endOf(c).$D;
      }, m.$locale = function () {
        return D[this.$L];
      }, m.locale = function (t, e) {
        if (!t) return this.$L;
        var n = this.clone(),
          r = w(t, e, !0);
        return r && (n.$L = r), n;
      }, m.clone = function () {
        return b.w(this.$d, this);
      }, m.toDate = function () {
        return new Date(this.valueOf());
      }, m.toJSON = function () {
        return this.isValid() ? this.toISOString() : null;
      }, m.toISOString = function () {
        return this.$d.toISOString();
      }, m.toString = function () {
        return this.$d.toUTCString();
      }, M;
    }(),
    k = _.prototype;
  return O.prototype = k, [["$ms", r], ["$s", i], ["$m", s], ["$H", u], ["$W", a], ["$M", c], ["$y", h], ["$D", d]].forEach(function (t) {
    k[t[1]] = function (e) {
      return this.$g(e, t[0], t[1]);
    };
  }), O.extend = function (t, e) {
    return t.$i || (t(e, _, O), t.$i = !0), O;
  }, O.locale = w, O.isDayjs = S, O.unix = function (t) {
    return O(1e3 * t);
  }, O.en = D[g], O.Ls = D, O.p = {}, O;
});

/***/ },

/***/ "./src/admin/components/LinkClickersModal.tsx"
/*!****************************************************!*\
  !*** ./src/admin/components/LinkClickersModal.tsx ***!
  \****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LinkClickersModal)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Modal */ "flarum/common/components/Modal");
/* harmony import */ var flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/LoadingIndicator */ "flarum/common/components/LoadingIndicator");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_components_Pagination__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/components/Pagination */ "flarum/common/components/Pagination");
/* harmony import */ var flarum_common_components_Pagination__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Pagination__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var dayjs__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! dayjs */ "./node_modules/dayjs/dayjs.min.js");
/* harmony import */ var dayjs__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(dayjs__WEBPACK_IMPORTED_MODULE_5__);






const PAGE_SIZE = 25;
class LinkClickersModal extends (flarum_common_components_Modal__WEBPACK_IMPORTED_MODULE_2___default()) {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "loading", true);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "rows", []);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "total", 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "pageNumber", 0);
  }
  oninit(vnode) {
    super.oninit(vnode);
    this.load();
  }
  className() {
    return 'LinkClickersModal Modal--large';
  }
  title() {
    return flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.clickers.title');
  }
  content() {
    return m("div", {
      className: "Modal-body"
    }, m("a", {
      className: "LinkClickersModal-url",
      href: this.attrs.url,
      target: "_blank",
      rel: "noopener noreferrer",
      title: this.attrs.url
    }, m("i", {
      className: "fas fa-arrow-up-right-from-square"
    }), " ", this.attrs.url), this.loading && this.rows.length === 0 ? m((flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_3___default()), null) : this.rows.length === 0 ? m("div", {
      className: "LinkClickersModal-empty"
    }, m("i", {
      className: "far fa-folder-open LinkClickersModal-emptyIcon"
    }), m("p", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.clickers.empty'))) : m("table", {
      className: "LinkClickersModal-table"
    }, m("colgroup", null, m("col", null), m("col", {
      style: "width: 80px"
    }), m("col", {
      style: "width: 140px"
    }), m("col", {
      style: "width: 140px"
    })), m("thead", null, m("tr", null, m("th", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.clickers.column_who')), m("th", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.clickers.column_clicks')), m("th", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.clickers.column_first')), m("th", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.clickers.column_last')))), m("tbody", null, this.rows.map(row => this.renderRow(row)))), this.total > PAGE_SIZE && m((flarum_common_components_Pagination__WEBPACK_IMPORTED_MODULE_4___default()), {
      currentPage: this.pageNumber + 1,
      total: this.total,
      perPage: PAGE_SIZE,
      onChange: page => {
        this.pageNumber = page - 1;
        this.load();
      }
    }));
  }
  renderRow(row) {
    let who;
    if (row.user) {
      const profileUrl = "".concat(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('baseUrl'), "/u/").concat(encodeURIComponent(row.user.username));
      who = m("a", {
        href: profileUrl,
        target: "_blank",
        rel: "noopener"
      }, row.user.avatarUrl ? m("img", {
        className: "Avatar LinkClickersModal-avatar",
        src: row.user.avatarUrl,
        alt: ""
      }) : m("span", {
        className: "Avatar LinkClickersModal-avatar LinkClickersModal-avatarFallback"
      }, row.user.displayName.charAt(0).toUpperCase()), m("span", null, row.user.displayName));
    } else if (row.anonymized) {
      who = m("span", {
        className: "LinkClickersModal-anon"
      }, m("i", {
        className: "fas fa-user-slash"
      }), " ", m("em", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.clickers.anonymized')));
    } else {
      who = m("span", {
        className: "LinkClickersModal-guest"
      }, m("i", {
        className: "fas fa-user"
      }), " ", flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.clickers.guest'), m("code", null, row.ip_address));
    }
    return m("tr", null, m("td", null, who), m("td", {
      className: "LinkClickersModal-count"
    }, row.click_count), m("td", {
      title: row.first_click
    }, dayjs__WEBPACK_IMPORTED_MODULE_5___default()(row.first_click).format('YYYY-MM-DD HH:mm')), m("td", {
      title: row.last_click
    }, dayjs__WEBPACK_IMPORTED_MODULE_5___default()(row.last_click).format('YYYY-MM-DD HH:mm')));
  }
  async load() {
    this.loading = true;
    m.redraw();
    try {
      const res = await flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().request({
        method: 'GET',
        url: "".concat(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('apiUrl'), "/link-click-stats/").concat(this.attrs.urlHash, "/clickers"),
        params: {
          'page[offset]': this.pageNumber * PAGE_SIZE,
          'page[limit]': PAGE_SIZE
        }
      });
      this.rows = res.rows;
      this.total = res.total;
    } catch (_unused) {
      this.rows = [];
      this.total = 0;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }
}
flarum.reg.add('datlechin-link-clicks', 'admin/components/LinkClickersModal', LinkClickersModal);

/***/ },

/***/ "./src/admin/components/LinkClicksAnalytics.tsx"
/*!******************************************************!*\
  !*** ./src/admin/components/LinkClicksAnalytics.tsx ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LinkClicksAnalytics)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/components/LoadingIndicator */ "flarum/common/components/LoadingIndicator");
/* harmony import */ var flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var flarum_common_components_Pagination__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! flarum/common/components/Pagination */ "flarum/common/components/Pagination");
/* harmony import */ var flarum_common_components_Pagination__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Pagination__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! flarum/common/utils/Stream */ "flarum/common/utils/Stream");
/* harmony import */ var flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! flarum/common/utils/extractText */ "flarum/common/utils/extractText");
/* harmony import */ var flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var dayjs__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! dayjs */ "./node_modules/dayjs/dayjs.min.js");
/* harmony import */ var dayjs__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(dayjs__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _LinkClickersModal__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./LinkClickersModal */ "./src/admin/components/LinkClickersModal.tsx");

function ownKeys(e, r) { var t = Object.keys(e); if (Object.getOwnPropertySymbols) { var o = Object.getOwnPropertySymbols(e); r && (o = o.filter(function (r) { return Object.getOwnPropertyDescriptor(e, r).enumerable; })), t.push.apply(t, o); } return t; }
function _objectSpread(e) { for (var r = 1; r < arguments.length; r++) { var t = null != arguments[r] ? arguments[r] : {}; r % 2 ? ownKeys(Object(t), !0).forEach(function (r) { (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(e, r, t[r]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(t)) : ownKeys(Object(t)).forEach(function (r) { Object.defineProperty(e, r, Object.getOwnPropertyDescriptor(t, r)); }); } return e; }









function typeIcon(row) {
  if (row.is_attachment) return {
    className: 'fas fa-paperclip',
    titleKey: 'datlechin-link-clicks.admin.analytics.attachment_label'
  };
  if (row.is_internal) return {
    className: 'fas fa-link',
    titleKey: 'datlechin-link-clicks.admin.analytics.internal_label'
  };
  return {
    className: 'fas fa-arrow-up-right-from-square',
    titleKey: 'datlechin-link-clicks.admin.analytics.external_label'
  };
}
const PAGE_SIZE = 25;
class LinkClicksAnalytics extends (flarum_common_Component__WEBPACK_IMPORTED_MODULE_3___default()) {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "loading", true);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "exporting", false);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "rows", []);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "total", 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "pageNumber", 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "since", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "until", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "scope", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "tagSlug", void 0);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "sort", void 0);
  }
  oninit(vnode) {
    super.oninit(vnode);
    this.since = flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_6___default()(dayjs__WEBPACK_IMPORTED_MODULE_8___default()().subtract(30, 'day').format('YYYY-MM-DD'));
    this.until = flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_6___default()(dayjs__WEBPACK_IMPORTED_MODULE_8___default()().format('YYYY-MM-DD'));
    this.scope = flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_6___default()('all');
    this.tagSlug = flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_6___default()('');
    this.sort = flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_6___default()('-total_clicks');
    this.load();
  }
  async load() {
    this.loading = true;
    m.redraw();
    const params = _objectSpread(_objectSpread({}, this.filterParams()), {}, {
      'sort': this.sort(),
      'page[offset]': this.pageNumber * PAGE_SIZE,
      'page[limit]': PAGE_SIZE
    });
    try {
      const res = await flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().request({
        method: 'GET',
        url: "".concat(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('apiUrl'), "/link-click-stats"),
        params
      });
      this.rows = res.rows;
      this.total = res.total;
    } catch (_unused) {
      this.rows = [];
      this.total = 0;
    } finally {
      this.loading = false;
      m.redraw();
    }
  }
  filterParams() {
    let since = this.since();
    let until = this.until();
    if (since && until && since > until) {
      var _ref = [until, since];
      since = _ref[0];
      until = _ref[1];
    }
    const params = {
      'filter[since]': since,
      'filter[until]': until
    };
    switch (this.scope()) {
      case 'external':
        params['filter[is_internal]'] = '0';
        break;
      case 'internal':
        params['filter[is_internal]'] = '1';
        params['filter[is_attachment]'] = '0';
        break;
      case 'attachments':
        params['filter[is_attachment]'] = '1';
        break;
    }
    if (this.tagSlug()) {
      params['filter[tag]'] = this.tagSlug();
    }
    return params;
  }
  async exportCsv() {
    this.exporting = true;
    m.redraw();
    try {
      const qs = new URLSearchParams(this.filterParams()).toString();
      const url = "".concat(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('apiUrl'), "/link-click-stats/export?").concat(qs);
      const response = await fetch(url, {
        credentials: 'include'
      });
      if (!response.ok) {
        var _body$error;
        const body = await response.json().catch(() => ({}));
        throw new Error((_body$error = body.error) != null ? _body$error : "Export failed (HTTP ".concat(response.status, ")."));
      }
      const blob = await response.blob();
      const objectUrl = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = objectUrl;
      a.download = "link-clicks-".concat(dayjs__WEBPACK_IMPORTED_MODULE_8___default()().format('YYYY-MM-DD'), ".csv");
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(objectUrl);
    } catch (e) {
      flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().alerts.show({
        type: 'error'
      }, e.message);
    } finally {
      this.exporting = false;
      m.redraw();
    }
  }
  view() {
    return m("div", {
      className: "LinkClicksAnalytics"
    }, m("div", {
      className: "LinkClicksAnalytics-filters"
    }, m("label", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.since'), m("input", {
      type: "date",
      className: "FormControl",
      value: this.since(),
      onchange: e => {
        this.since(e.target.value);
        this.pageNumber = 0;
        this.load();
      }
    })), m("label", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.until'), m("input", {
      type: "date",
      className: "FormControl",
      value: this.until(),
      onchange: e => {
        this.until(e.target.value);
        this.pageNumber = 0;
        this.load();
      }
    })), m("label", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.scope'), m("select", {
      className: "FormControl",
      value: this.scope(),
      onchange: e => {
        this.scope(e.target.value);
        this.pageNumber = 0;
        this.load();
      }
    }, m("option", {
      value: "all"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.scope_all')), m("option", {
      value: "external"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.scope_external')), m("option", {
      value: "internal"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.scope_internal')), m("option", {
      value: "attachments"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.scope_attachments')))), m("label", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.tag'), m("input", {
      type: "text",
      className: "FormControl",
      placeholder: flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_7___default()(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.tag_placeholder')),
      value: this.tagSlug(),
      oninput: e => {
        this.tagSlug(e.target.value);
      },
      onchange: () => {
        this.pageNumber = 0;
        this.load();
      }
    })), m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2___default()), {
      className: "Button LinkClicksAnalytics-export",
      icon: "fas fa-file-csv",
      loading: this.exporting,
      disabled: this.loading || this.rows.length === 0,
      onclick: () => this.exportCsv()
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.export_csv'))), this.loading && this.rows.length === 0 ? m((flarum_common_components_LoadingIndicator__WEBPACK_IMPORTED_MODULE_4___default()), null) : this.rows.length === 0 ? m("div", {
      className: "LinkClicksAnalytics-empty"
    }, m("i", {
      className: "far fa-folder-open LinkClicksAnalytics-emptyIcon"
    }), m("p", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.empty'))) : m("table", {
      className: "LinkClicksAnalytics-table"
    }, m("thead", null, m("tr", null, m("th", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.column_url')), m("th", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.column_total')), m("th", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.column_unique')), m("th", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.column_first')), m("th", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.column_last')))), m("tbody", null, this.rows.map(row => {
      const t = typeIcon(row);
      return m("tr", null, m("td", {
        className: "LinkClicksAnalytics-urlCell"
      }, m("i", {
        className: "".concat(t.className, " LinkClicksAnalytics-typeIcon"),
        title: flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_7___default()(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans(t.titleKey))
      }), m("a", {
        href: row.url,
        target: "_blank",
        rel: "noopener noreferrer",
        title: row.url
      }, row.url)), m("td", {
        className: "LinkClicksAnalytics-num"
      }, row.total_clicks), m("td", {
        className: "LinkClicksAnalytics-num"
      }, m("button", {
        type: "button",
        className: "LinkClicksAnalytics-drilldown",
        onclick: () => flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().modal.show(_LinkClickersModal__WEBPACK_IMPORTED_MODULE_9__["default"], {
          urlHash: row.url_hash,
          url: row.url
        }),
        title: flarum_common_utils_extractText__WEBPACK_IMPORTED_MODULE_7___default()(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.analytics.drilldown_tooltip'))
      }, row.unique_users)), m("td", null, row.first_clicked ? dayjs__WEBPACK_IMPORTED_MODULE_8___default()(row.first_clicked).format('YYYY-MM-DD') : '—'), m("td", null, row.last_clicked ? dayjs__WEBPACK_IMPORTED_MODULE_8___default()(row.last_clicked).format('YYYY-MM-DD') : '—'));
    }))), this.total > PAGE_SIZE && m((flarum_common_components_Pagination__WEBPACK_IMPORTED_MODULE_5___default()), {
      currentPage: this.pageNumber + 1,
      total: this.total,
      perPage: PAGE_SIZE,
      onChange: page => {
        this.pageNumber = page - 1;
        this.load();
      }
    }));
  }
}
flarum.reg.add('datlechin-link-clicks', 'admin/components/LinkClicksAnalytics', LinkClicksAnalytics);

/***/ },

/***/ "./src/admin/components/LinkClicksPage.tsx"
/*!*************************************************!*\
  !*** ./src/admin/components/LinkClicksPage.tsx ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ LinkClicksPage)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/admin/components/ExtensionPage */ "flarum/admin/components/ExtensionPage");
/* harmony import */ var flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_common_components_Form__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/common/components/Form */ "flarum/common/components/Form");
/* harmony import */ var flarum_common_components_Form__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Form__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! flarum/common/utils/classList */ "flarum/common/utils/classList");
/* harmony import */ var flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _LinkClicksAnalytics__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./LinkClicksAnalytics */ "./src/admin/components/LinkClicksAnalytics.tsx");






const TAB_STORAGE_KEY = 'datlechin-link-clicks.admin.tab';
const TABS = ['settings', 'analytics', 'webhook'];
function loadTab() {
  try {
    const stored = sessionStorage.getItem(TAB_STORAGE_KEY);
    if (stored && TABS.includes(stored)) {
      return stored;
    }
  } catch (_unused) {
    // sessionStorage may be blocked (private mode in some browsers).
  }
  return 'settings';
}
class LinkClicksPage extends (flarum_admin_components_ExtensionPage__WEBPACK_IMPORTED_MODULE_2___default()) {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "activeTab", loadTab());
  }
  setTab(tab) {
    this.activeTab = tab;
    try {
      sessionStorage.setItem(TAB_STORAGE_KEY, tab);
    } catch (_unused2) {
      // Same as above; ignore quota / disabled storage failures.
    }
  }
  content() {
    return m("div", {
      className: "LinkClicksPage"
    }, m("div", {
      className: "LinkClicksPage-tabsBar"
    }, m("div", {
      className: "container"
    }, m("ul", {
      className: "LinkClicksPage-tabs"
    }, this.renderTabButton('settings', 'fas fa-sliders'), this.renderTabButton('analytics', 'fas fa-chart-bar'), this.renderTabButton('webhook', 'fas fa-bolt')))), this.renderActiveTab());
  }
  renderActiveTab() {
    switch (this.activeTab) {
      case 'analytics':
        return this.renderAnalytics();
      case 'webhook':
        return this.renderWebhook();
      default:
        return this.renderSettings();
    }
  }
  renderTabButton(tab, icon) {
    return m("li", null, m("button", {
      type: "button",
      className: flarum_common_utils_classList__WEBPACK_IMPORTED_MODULE_4___default()('LinkClicksPage-tab', {
        active: this.activeTab === tab
      }),
      onclick: () => this.setTab(tab)
    }, m("i", {
      className: icon
    }), m("span", null, flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans("datlechin-link-clicks.admin.tabs.".concat(tab)))));
  }
  renderSettings() {
    return m("div", {
      className: "ExtensionPage-settings"
    }, m("div", {
      className: "container"
    }, m((flarum_common_components_Form__WEBPACK_IMPORTED_MODULE_3___default()), null, this.buildSettingComponent({
      setting: 'datlechin-link-clicks.enabled',
      type: 'boolean',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.enabled_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.enabled_help')
    }), this.buildSettingComponent({
      setting: 'datlechin-link-clicks.track_internal',
      type: 'boolean',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.track_internal_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.track_internal_help')
    }), this.buildSettingComponent({
      setting: 'datlechin-link-clicks.honor_dnt',
      type: 'boolean',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.honor_dnt_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.honor_dnt_help')
    }), this.buildSettingComponent({
      setting: 'datlechin-link-clicks.skip_guests',
      type: 'boolean',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.skip_guests_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.skip_guests_help')
    }), this.buildSettingComponent({
      setting: 'datlechin-link-clicks.min_display_count',
      type: 'number',
      min: 1,
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.min_display_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.min_display_help')
    }), this.buildSettingComponent({
      setting: 'datlechin-link-clicks.dedup_window_hours',
      type: 'number',
      min: 1,
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.dedup_window_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.dedup_window_help')
    }), this.buildSettingComponent({
      setting: 'datlechin-link-clicks.event_retention_days',
      type: 'number',
      min: 1,
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.retention_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.retention_help')
    }), this.buildSettingComponent({
      setting: 'datlechin-link-clicks.bot_user_agents',
      type: 'textarea',
      rows: 5,
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.bot_ua_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.bot_ua_help'),
      placeholder: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.bot_ua_placeholder')
    }), this.buildSettingComponent({
      setting: 'datlechin-link-clicks.tracking_params_strip',
      type: 'textarea',
      rows: 4,
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.tracking_params_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.tracking_params_help'),
      placeholder: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.tracking_params_placeholder')
    }), this.buildSettingComponent({
      setting: 'datlechin-link-clicks.attachment_path_prefixes',
      type: 'textarea',
      rows: 3,
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.attachment_prefixes_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.attachment_prefixes_help'),
      placeholder: '/assets/files/\n/uploads/'
    }), m("div", {
      className: "Form-group Form-controls"
    }, this.submitButton()))));
  }
  renderWebhook() {
    const v = this.setting('datlechin-link-clicks.webhook_enabled')();
    const enabled = v === true || v === 1 || v === '1';
    return m("div", {
      className: "ExtensionPage-settings"
    }, m("div", {
      className: "container"
    }, m((flarum_common_components_Form__WEBPACK_IMPORTED_MODULE_3___default()), null, this.buildSettingComponent({
      setting: 'datlechin-link-clicks.webhook_enabled',
      type: 'boolean',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.webhook_enabled_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.webhook_enabled_help')
    }), enabled && [this.buildSettingComponent({
      setting: 'datlechin-link-clicks.webhook_url',
      type: 'text',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.webhook_url_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.webhook_url_help'),
      placeholder: 'https://example.com/hook'
    }), this.buildSettingComponent({
      setting: 'datlechin-link-clicks.webhook_secret',
      type: 'text',
      label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.webhook_secret_label'),
      help: flarum_admin_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.admin.settings.webhook_secret_help')
    })], m("div", {
      className: "Form-group Form-controls"
    }, this.submitButton()))));
  }
  renderAnalytics() {
    return m("div", {
      className: "ExtensionPage-settings"
    }, m("div", {
      className: "container"
    }, m(_LinkClicksAnalytics__WEBPACK_IMPORTED_MODULE_5__["default"], null)));
  }
}
flarum.reg.add('datlechin-link-clicks', 'admin/components/LinkClicksPage', LinkClicksPage);

/***/ },

/***/ "./src/admin/extend.ts"
/*!*****************************!*\
  !*** ./src/admin/extend.ts ***!
  \*****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extenders */ "flarum/common/extenders");
/* harmony import */ var flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_LinkClicksPage__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./components/LinkClicksPage */ "./src/admin/components/LinkClicksPage.tsx");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ([new (flarum_common_extenders__WEBPACK_IMPORTED_MODULE_0___default().Admin)().page(_components_LinkClicksPage__WEBPACK_IMPORTED_MODULE_1__["default"])]);

/***/ },

/***/ "./src/admin/extendEditTagModal.tsx"
/*!******************************************!*\
  !*** ./src/admin/extendEditTagModal.tsx ***!
  \******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ extendEditTagModal)
/* harmony export */ });
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/utils/Stream */ "flarum/common/utils/Stream");
/* harmony import */ var flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var ext_flarum_tags_admin_components_EditTagModal__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ext:flarum/tags/admin/components/EditTagModal */ "ext:flarum/tags/admin/components/EditTagModal");
/* harmony import */ var ext_flarum_tags_admin_components_EditTagModal__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(ext_flarum_tags_admin_components_EditTagModal__WEBPACK_IMPORTED_MODULE_3__);




function extendEditTagModal() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((ext_flarum_tags_admin_components_EditTagModal__WEBPACK_IMPORTED_MODULE_3___default().prototype), 'oninit', function () {
    var _this$tag$attribute;
    this.linkClicksDisabled = flarum_common_utils_Stream__WEBPACK_IMPORTED_MODULE_2___default()((_this$tag$attribute = this.tag.attribute('linkClicksDisabled')) != null ? _this$tag$attribute : false);
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((ext_flarum_tags_admin_components_EditTagModal__WEBPACK_IMPORTED_MODULE_3___default().prototype), 'fields', function (items) {
    items.add('linkClicksDisabled', m("div", {
      className: "Form-group"
    }, m("div", null, m("label", {
      className: "checkbox"
    }, m("input", {
      type: "checkbox",
      bidi: this.linkClicksDisabled
    }), flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('datlechin-link-clicks.admin.tags.disable_tracking_label')), m("div", {
      className: "helpText"
    }, flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('datlechin-link-clicks.admin.tags.disable_tracking_help')))), 5);
  });
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((ext_flarum_tags_admin_components_EditTagModal__WEBPACK_IMPORTED_MODULE_3___default().prototype), 'submitData', function (data) {
    data.linkClicksDisabled = this.linkClicksDisabled();
  });
}

/***/ },

/***/ "./src/admin/index.ts"
/*!****************************!*\
  !*** ./src/admin/index.ts ***!
  \****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   extend: () => (/* reexport safe */ _extend__WEBPACK_IMPORTED_MODULE_2__["default"])
/* harmony export */ });
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/admin/app */ "flarum/admin/app");
/* harmony import */ var flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_admin_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _extendEditTagModal__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./extendEditTagModal */ "./src/admin/extendEditTagModal.tsx");
/* harmony import */ var _extend__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./extend */ "./src/admin/extend.ts");



flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().initializers.add('datlechin-link-clicks', () => {
  flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().registry.for('datlechin-link-clicks').registerPermission({
    icon: 'fas fa-chart-bar',
    label: flarum_admin_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('datlechin-link-clicks.admin.permissions.view_analytics_label'),
    permission: 'datlechin-link-clicks.viewAnalytics'
  }, 'moderate', 50);
  if ('flarum-tags' in flarum.extensions) {
    (0,_extendEditTagModal__WEBPACK_IMPORTED_MODULE_1__["default"])();
  }
});

/***/ },

/***/ "flarum/admin/app"
/*!******************************************************!*\
  !*** external "flarum.reg.get('core', 'admin/app')" ***!
  \******************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'admin/app');

/***/ },

/***/ "flarum/admin/components/ExtensionPage"
/*!***************************************************************************!*\
  !*** external "flarum.reg.get('core', 'admin/components/ExtensionPage')" ***!
  \***************************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'admin/components/ExtensionPage');

/***/ },

/***/ "flarum/common/Component"
/*!*************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/Component')" ***!
  \*************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/Component');

/***/ },

/***/ "flarum/common/components/Button"
/*!*********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Button')" ***!
  \*********************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Button');

/***/ },

/***/ "flarum/common/components/Form"
/*!*******************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Form')" ***!
  \*******************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Form');

/***/ },

/***/ "flarum/common/components/LoadingIndicator"
/*!*******************************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/LoadingIndicator')" ***!
  \*******************************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/LoadingIndicator');

/***/ },

/***/ "flarum/common/components/Modal"
/*!********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Modal')" ***!
  \********************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Modal');

/***/ },

/***/ "flarum/common/components/Pagination"
/*!*************************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Pagination')" ***!
  \*************************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Pagination');

/***/ },

/***/ "flarum/common/extend"
/*!**********************************************************!*\
  !*** external "flarum.reg.get('core', 'common/extend')" ***!
  \**********************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/extend');

/***/ },

/***/ "flarum/common/extenders"
/*!*************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/extenders')" ***!
  \*************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/extenders');

/***/ },

/***/ "flarum/common/utils/Stream"
/*!****************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/utils/Stream')" ***!
  \****************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/utils/Stream');

/***/ },

/***/ "flarum/common/utils/classList"
/*!*******************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/utils/classList')" ***!
  \*******************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/utils/classList');

/***/ },

/***/ "flarum/common/utils/extractText"
/*!*********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/utils/extractText')" ***!
  \*********************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/utils/extractText');

/***/ },

/***/ "ext:flarum/tags/admin/components/EditTagModal"
/*!*********************************************************************************!*\
  !*** external "flarum.reg.get('flarum-tags', 'admin/components/EditTagModal')" ***!
  \*********************************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('flarum-tags', 'admin/components/EditTagModal');

/***/ },

/***/ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js"
/*!*******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/defineProperty.js ***!
  \*******************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _defineProperty)
/* harmony export */ });
/* harmony import */ var _toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./toPropertyKey.js */ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js");

function _defineProperty(e, r, t) {
  return (r = (0,_toPropertyKey_js__WEBPACK_IMPORTED_MODULE_0__["default"])(r)) in e ? Object.defineProperty(e, r, {
    value: t,
    enumerable: !0,
    configurable: !0,
    writable: !0
  }) : e[r] = t, e;
}


/***/ },

/***/ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js"
/*!****************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPrimitive.js ***!
  \****************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPrimitive)
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");

function toPrimitive(t, r) {
  if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(t) || !t) return t;
  var e = t[Symbol.toPrimitive];
  if (void 0 !== e) {
    var i = e.call(t, r || "default");
    if ("object" != (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i)) return i;
    throw new TypeError("@@toPrimitive must return a primitive value.");
  }
  return ("string" === r ? String : Number)(t);
}


/***/ },

/***/ "./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js"
/*!******************************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/toPropertyKey.js ***!
  \******************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ toPropertyKey)
/* harmony export */ });
/* harmony import */ var _typeof_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./typeof.js */ "./node_modules/@babel/runtime/helpers/esm/typeof.js");
/* harmony import */ var _toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./toPrimitive.js */ "./node_modules/@babel/runtime/helpers/esm/toPrimitive.js");


function toPropertyKey(t) {
  var i = (0,_toPrimitive_js__WEBPACK_IMPORTED_MODULE_1__["default"])(t, "string");
  return "symbol" == (0,_typeof_js__WEBPACK_IMPORTED_MODULE_0__["default"])(i) ? i : i + "";
}


/***/ },

/***/ "./node_modules/@babel/runtime/helpers/esm/typeof.js"
/*!***********************************************************!*\
  !*** ./node_modules/@babel/runtime/helpers/esm/typeof.js ***!
  \***********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ _typeof)
/* harmony export */ });
function _typeof(o) {
  "@babel/helpers - typeof";

  return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) {
    return typeof o;
  } : function (o) {
    return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
  }, _typeof(o);
}


/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		flarum.reg._webpack_runtimes["datlechin-link-clicks"] ||= __webpack_require__;// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(() => {
"use strict";
/*!******************!*\
  !*** ./admin.ts ***!
  \******************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   extend: () => (/* reexport safe */ _src_admin__WEBPACK_IMPORTED_MODULE_0__.extend)
/* harmony export */ });
/* harmony import */ var _src_admin__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/admin */ "./src/admin/index.ts");

})();

module.exports = __webpack_exports__;
/******/ })()
;
//# sourceMappingURL=admin.js.map