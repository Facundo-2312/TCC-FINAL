(function () {
  if (window.__POPUPS_DISABLED__) {
    return;
  }

  window.__POPUPS_DISABLED__ = true;

  var originalAlert = window.alert;
  var originalConfirm = window.confirm;

  window.alert = function (message) {
    if (window.console && typeof window.console.warn === 'function') {
      window.console.warn('[Popup bloqueado][alert]:', message);
    }
    return undefined;
  };

  window.confirm = function (message) {
    if (window.console && typeof window.console.warn === 'function') {
      window.console.warn('[Popup bloqueado][confirm]:', message);
    }
    return false;
  };

  window.__ORIGINAL_ALERT__ = originalAlert;
  window.__ORIGINAL_CONFIRM__ = originalConfirm;
})();
