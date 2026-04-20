(function () {
  function onReady(fn) {
    if (document.readyState !== "loading") {
      fn();
    } else {
      document.addEventListener("DOMContentLoaded", fn);
    }
  }

  function getConfig(slot) {
    var cfg = window.CatTailConfig || {};

    if (slot === "top") {
      return {
        id: "ims-top-el-wrap",
        selector: cfg.top_selector || cfg.selector || ".content-wrapper",
        position: cfg.top_position || "beforebegin",
      };
    }

    return {
      id: "ims-bottom-el-wrap",
      selector: cfg.bottom_selector || cfg.selector || ".content-wrapper",
      position: cfg.bottom_position || cfg.position || "afterend",
    };
  }

  function moveSlotWrap(slot) {
    var conf = getConfig(slot);
    var wrap = document.getElementById(conf.id);

    // Nothing to move for this slot.
    if (!wrap) return true;
    if (wrap.dataset.moved) return true;

    var target = document.querySelector(conf.selector);
    if (!target || !target.parentNode) return false;

    try {
      target.insertAdjacentElement(conf.position, wrap);
      wrap.dataset.moved = "1";
      return true;
    } catch (e) {
      try {
        target.insertAdjacentElement("afterend", wrap);
        wrap.dataset.moved = "1";
        return true;
      } catch (_) {
        return false;
      }
    }
  }

  function moveAllWraps() {
    var topOk = moveSlotWrap("top");
    var bottomOk = moveSlotWrap("bottom");
    return topOk && bottomOk;
  }

  onReady(function () {
    if (moveAllWraps()) return;

    var tries = 0;
    var iv = setInterval(function () {
      tries++;
      if (moveAllWraps() || tries >= 10) {
        clearInterval(iv);
      }
    }, 200);

    try {
      var mo = new MutationObserver(function () {
        if (moveAllWraps()) {
          mo.disconnect();
        }
      });
      mo.observe(document.documentElement, { childList: true, subtree: true });
    } catch (_) {}
  });
})();
