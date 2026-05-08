/******/ (() => { // webpackBootstrap
/******/ 	// runtime can't be in strict mode because a global variable is assign and maybe created.
/******/ 	var __webpack_modules__ = ({

/***/ "./src/forum/addPopularLinksWidget.tsx"
/*!*********************************************!*\
  !*** ./src/forum/addPopularLinksWidget.tsx ***!
  \*********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ addPopularLinksWidget)
/* harmony export */ });
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/components/DiscussionPage */ "flarum/forum/components/DiscussionPage");
/* harmony import */ var flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_PopularLinksWidget__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/PopularLinksWidget */ "./src/forum/components/PopularLinksWidget.tsx");



function addPopularLinksWidget() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_DiscussionPage__WEBPACK_IMPORTED_MODULE_1___default().prototype), 'sidebarItems', function (items) {
    if (!this.discussion) {
      return;
    }
    items.add('popularLinks', m(_components_PopularLinksWidget__WEBPACK_IMPORTED_MODULE_2__["default"], {
      discussionId: this.discussion.id()
    }), 50);
  });
}

/***/ },

/***/ "./src/forum/addUserPopularLinksWidget.tsx"
/*!*************************************************!*\
  !*** ./src/forum/addUserPopularLinksWidget.tsx ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ addUserPopularLinksWidget)
/* harmony export */ });
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_forum_components_UserPage__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/components/UserPage */ "flarum/forum/components/UserPage");
/* harmony import */ var flarum_forum_components_UserPage__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_components_UserPage__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _components_UserPopularLinksWidget__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/UserPopularLinksWidget */ "./src/forum/components/UserPopularLinksWidget.tsx");



function addUserPopularLinksWidget() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_0__.extend)((flarum_forum_components_UserPage__WEBPACK_IMPORTED_MODULE_1___default().prototype), 'sidebarItems', function (items) {
    if (!this.user) return;
    items.add('linkClicksPopular', m(_components_UserPopularLinksWidget__WEBPACK_IMPORTED_MODULE_2__["default"], {
      userId: String(this.user.id())
    }), -10);
  });
}

/***/ },

/***/ "./src/forum/components/PopularLinksWidget.tsx"
/*!*****************************************************!*\
  !*** ./src/forum/components/PopularLinksWidget.tsx ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ PopularLinksWidget)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__);



class PopularLinksWidget extends (flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default()) {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "loading", true);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "links", []);
  }
  oninit(vnode) {
    super.oninit(vnode);
    this.load();
  }
  async load() {
    try {
      this.links = await flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().request({
        method: 'GET',
        url: "".concat(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('apiUrl'), "/discussions/").concat(this.attrs.discussionId, "/popular-links")
      });
    } catch (_unused) {
      // 404 (invisible discussion) or transport error: hide silently.
    } finally {
      this.loading = false;
      m.redraw();
    }
  }
  view() {
    if (this.loading || !this.links.length) {
      return null;
    }
    return m("div", {
      className: "LinkClicks-popular"
    }, m("h4", {
      className: "LinkClicks-popular-title"
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.forum.popular_links_title')), m("ul", {
      className: "LinkClicks-popular-list"
    }, this.links.map(link => m("li", {
      key: link.id
    }, m("a", {
      href: link.title,
      title: link.title,
      target: "_blank",
      rel: "noopener noreferrer",
      className: "LinkClicks-popular-item",
      onclick: () => {
        fetch(link.track_url, {
          keepalive: true
        }).catch(() => {});
      }
    }, m("span", {
      className: "LinkClicks-popular-host"
    }, link.display), m("span", {
      className: "LinkClicks-popular-count"
    }, link.count))))));
  }
}
flarum.reg.add('datlechin-link-clicks', 'forum/components/PopularLinksWidget', PopularLinksWidget);

/***/ },

/***/ "./src/forum/components/UserPopularLinksWidget.tsx"
/*!*********************************************************!*\
  !*** ./src/forum/components/UserPopularLinksWidget.tsx ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ UserPopularLinksWidget)
/* harmony export */ });
/* harmony import */ var _babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @babel/runtime/helpers/esm/defineProperty */ "./node_modules/@babel/runtime/helpers/esm/defineProperty.js");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/Component */ "flarum/common/Component");
/* harmony import */ var flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_Component__WEBPACK_IMPORTED_MODULE_2__);



class UserPopularLinksWidget extends (flarum_common_Component__WEBPACK_IMPORTED_MODULE_2___default()) {
  constructor() {
    super(...arguments);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "loading", true);
    (0,_babel_runtime_helpers_esm_defineProperty__WEBPACK_IMPORTED_MODULE_0__["default"])(this, "links", []);
  }
  oninit(vnode) {
    super.oninit(vnode);
    this.load();
  }
  async load() {
    try {
      this.links = await flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().request({
        method: 'GET',
        url: "".concat(flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().forum.attribute('apiUrl'), "/users/").concat(this.attrs.userId, "/popular-links")
      });
    } catch (_unused) {
      // hide silently on error
    } finally {
      this.loading = false;
      m.redraw();
    }
  }
  view() {
    if (this.loading || !this.links.length) {
      return null;
    }
    return m("div", {
      className: "LinkClicks-popular UserPopularLinks"
    }, m("h4", {
      className: "LinkClicks-popular-title"
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_1___default().translator.trans('datlechin-link-clicks.forum.user_popular_links_title')), m("ul", {
      className: "LinkClicks-popular-list"
    }, this.links.map(link => m("li", {
      key: link.id
    }, m("a", {
      href: link.title,
      title: link.title,
      target: "_blank",
      rel: "noopener noreferrer",
      className: "LinkClicks-popular-item",
      onclick: () => {
        fetch(link.track_url, {
          keepalive: true
        }).catch(() => {});
      }
    }, m("span", {
      className: "LinkClicks-popular-host"
    }, link.display), m("span", {
      className: "LinkClicks-popular-count"
    }, link.count))))));
  }
}
flarum.reg.add('datlechin-link-clicks', 'forum/components/UserPopularLinksWidget', UserPopularLinksWidget);

/***/ },

/***/ "./src/forum/extendPostControls.tsx"
/*!******************************************!*\
  !*** ./src/forum/extendPostControls.tsx ***!
  \******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ extendPostControls)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Button */ "flarum/common/components/Button");
/* harmony import */ var flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var flarum_forum_utils_PostControls__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! flarum/forum/utils/PostControls */ "flarum/forum/utils/PostControls");
/* harmony import */ var flarum_forum_utils_PostControls__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_utils_PostControls__WEBPACK_IMPORTED_MODULE_3__);




const saving = new Set();
function extendPostControls() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)((flarum_forum_utils_PostControls__WEBPACK_IMPORTED_MODULE_3___default()), 'moderationControls', function (items, post) {
    if (post.contentType() !== 'comment' || !post.canEdit()) return;
    const disabled = !!post.attribute('linkClicksDisabled');
    const postId = Number(post.id());
    items.add('linkClicksToggle', m((flarum_common_components_Button__WEBPACK_IMPORTED_MODULE_2___default()), {
      icon: disabled ? 'fas fa-link' : 'fas fa-link-slash',
      loading: saving.has(postId),
      onclick: () => {
        saving.add(postId);
        m.redraw();
        post.save({
          linkClicksDisabled: !disabled
        }).then(() => {
          saving.delete(postId);
          m.redraw();
        }, err => {
          saving.delete(postId);
          flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().alerts.show({
            type: 'error'
          }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('datlechin-link-clicks.forum.post_controls.toggle_failed'));
          m.redraw();
          throw err;
        });
      }
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans(disabled ? 'datlechin-link-clicks.forum.post_controls.enable_tracking' : 'datlechin-link-clicks.forum.post_controls.disable_tracking')), -10);
  });
}

/***/ },

/***/ "./src/forum/extendRealtime.ts"
/*!*************************************!*\
  !*** ./src/forum/extendRealtime.ts ***!
  \*************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ extendRealtime)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var ext_flarum_realtime_forum_extenders_Realtime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ext:flarum/realtime/forum/extenders/Realtime */ "ext:flarum/realtime/forum/extenders/Realtime");
/* harmony import */ var ext_flarum_realtime_forum_extenders_Realtime__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(ext_flarum_realtime_forum_extenders_Realtime__WEBPACK_IMPORTED_MODULE_1__);


function extendRealtime() {
  new (ext_flarum_realtime_forum_extenders_Realtime__WEBPACK_IMPORTED_MODULE_1___default())().onBothChannelsEvent('linkClickCounted', data => {
    const minDisplay = flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().forum.attribute('linkClicksMinDisplay');
    const selector = ".LinkClicks-link[data-post-id=\"".concat(data.post_id, "\"][data-url-id=\"").concat(data.url_id, "\"]");
    document.querySelectorAll(selector).forEach(el => {
      if (data.clicks_count >= minDisplay) {
        el.setAttribute('data-clicks', String(data.clicks_count));
        if (!el.hasAttribute('data-custom-title')) {
          el.setAttribute('title', data.title);
          el.setAttribute('data-original-title', data.title);
        }
      } else {
        el.removeAttribute('data-clicks');
      }
    });
  }).extend((flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default()), {
    name: 'datlechin-link-clicks',
    exports: {}
  });
}

/***/ },

/***/ "./src/forum/extendSettingsPage.tsx"
/*!******************************************!*\
  !*** ./src/forum/extendSettingsPage.tsx ***!
  \******************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ extendSettingsPage)
/* harmony export */ });
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! flarum/common/extend */ "flarum/common/extend");
/* harmony import */ var flarum_common_extend__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var flarum_common_components_Switch__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! flarum/common/components/Switch */ "flarum/common/components/Switch");
/* harmony import */ var flarum_common_components_Switch__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(flarum_common_components_Switch__WEBPACK_IMPORTED_MODULE_2__);



function extendSettingsPage() {
  (0,flarum_common_extend__WEBPACK_IMPORTED_MODULE_1__.extend)('flarum/forum/components/SettingsPage', 'privacyItems', function (items) {
    var _user$preferences;
    const user = this.user;
    if (!user) return;
    const key = 'datlechin-link-clicks.opt_out';
    const loadingKey = 'linkClicksOptOutLoading';
    items.add('linkClicksOptOut', [m((flarum_common_components_Switch__WEBPACK_IMPORTED_MODULE_2___default()), {
      state: !!((_user$preferences = user.preferences()) != null && _user$preferences[key]),
      loading: this[loadingKey],
      onchange: value => {
        this[loadingKey] = true;
        m.redraw();
        user.savePreferences({
          [key]: value
        }).then(() => {
          this[loadingKey] = false;
          m.redraw();
        }, () => {
          this[loadingKey] = false;
          flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().alerts.show({
            type: 'error'
          }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('datlechin-link-clicks.forum.settings.opt_out_save_failed'));
          m.redraw();
        });
      }
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('datlechin-link-clicks.forum.settings.opt_out_label')), m("p", {
      className: "helpText"
    }, flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().translator.trans('datlechin-link-clicks.forum.settings.opt_out_help'))], 40);
  });
}

/***/ },

/***/ "./src/forum/index.ts"
/*!****************************!*\
  !*** ./src/forum/index.ts ***!
  \****************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! flarum/forum/app */ "flarum/forum/app");
/* harmony import */ var flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(flarum_forum_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _addPopularLinksWidget__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./addPopularLinksWidget */ "./src/forum/addPopularLinksWidget.tsx");
/* harmony import */ var _addUserPopularLinksWidget__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./addUserPopularLinksWidget */ "./src/forum/addUserPopularLinksWidget.tsx");
/* harmony import */ var _extendPostControls__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./extendPostControls */ "./src/forum/extendPostControls.tsx");
/* harmony import */ var _extendRealtime__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./extendRealtime */ "./src/forum/extendRealtime.ts");
/* harmony import */ var _extendSettingsPage__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./extendSettingsPage */ "./src/forum/extendSettingsPage.tsx");






flarum_forum_app__WEBPACK_IMPORTED_MODULE_0___default().initializers.add('datlechin-link-clicks', () => {
  (0,_addPopularLinksWidget__WEBPACK_IMPORTED_MODULE_1__["default"])();
  (0,_addUserPopularLinksWidget__WEBPACK_IMPORTED_MODULE_2__["default"])();
  (0,_extendSettingsPage__WEBPACK_IMPORTED_MODULE_5__["default"])();
  (0,_extendPostControls__WEBPACK_IMPORTED_MODULE_3__["default"])();
  if ('flarum-realtime' in flarum.extensions) {
    (0,_extendRealtime__WEBPACK_IMPORTED_MODULE_4__["default"])();
  }
});

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

/***/ "flarum/common/components/Switch"
/*!*********************************************************************!*\
  !*** external "flarum.reg.get('core', 'common/components/Switch')" ***!
  \*********************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/components/Switch');

/***/ },

/***/ "flarum/common/extend"
/*!**********************************************************!*\
  !*** external "flarum.reg.get('core', 'common/extend')" ***!
  \**********************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'common/extend');

/***/ },

/***/ "flarum/forum/app"
/*!******************************************************!*\
  !*** external "flarum.reg.get('core', 'forum/app')" ***!
  \******************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'forum/app');

/***/ },

/***/ "flarum/forum/components/DiscussionPage"
/*!****************************************************************************!*\
  !*** external "flarum.reg.get('core', 'forum/components/DiscussionPage')" ***!
  \****************************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'forum/components/DiscussionPage');

/***/ },

/***/ "flarum/forum/components/UserPage"
/*!**********************************************************************!*\
  !*** external "flarum.reg.get('core', 'forum/components/UserPage')" ***!
  \**********************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'forum/components/UserPage');

/***/ },

/***/ "flarum/forum/utils/PostControls"
/*!*********************************************************************!*\
  !*** external "flarum.reg.get('core', 'forum/utils/PostControls')" ***!
  \*********************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('core', 'forum/utils/PostControls');

/***/ },

/***/ "ext:flarum/realtime/forum/extenders/Realtime"
/*!********************************************************************************!*\
  !*** external "flarum.reg.get('flarum-realtime', 'forum/extenders/Realtime')" ***!
  \********************************************************************************/
(module) {

"use strict";
module.exports = flarum.reg.get('flarum-realtime', 'forum/extenders/Realtime');

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
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
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
  !*** ./forum.ts ***!
  \******************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _src_forum__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./src/forum */ "./src/forum/index.ts");

})();

module.exports = __webpack_exports__;
/******/ })()
;
//# sourceMappingURL=forum.js.map