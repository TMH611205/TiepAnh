/*
 * Animation khi cuộn dùng chung cho toàn website (và trang admin).
 * - Mỗi <section> tự fade-in + trượt nhẹ lên một lần khi vào khung nhìn.
 * - Các thẻ con (card, tiện ích...) xuất hiện lần lượt (stagger).
 * - Thêm thủ công: data-reveal (tuỳ chọn data-delay="ms").
 * - Tắt khi prefers-reduced-motion; nội dung vẫn hiện nếu JS lỗi (xem CSS + failsafe trong header).
 */
(function () {
  "use strict";

  var root = document.documentElement;

  if (
    !("IntersectionObserver" in window) ||
    window.matchMedia("(prefers-reduced-motion: reduce)").matches
  ) {
    root.classList.remove("js-reveal");
    return;
  }

  var ITEM_SELECTOR = [
    ".category-card",
    ".product-card",
    ".catalog-product-card",
    ".promotion-card",
    ".news-card",
    ".why-card",
    ".value-card",
    ".eco-card",
    ".benefit-item",
    ".admin-stat",
  ].join(",");

  var MAX_STAGGER = 6;
  var STAGGER_MS = 80;

  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        var target = entry.target;
        target.classList.add("is-visible");
        (target._revealItems || []).forEach(function (item) {
          item.classList.add("is-visible");
        });
        observer.unobserve(target);
      });
    },
    { threshold: 0, rootMargin: "0px 0px -8% 0px" },
  );

  function prepare(element) {
    element.classList.add("reveal");

    var delay = Number(element.dataset.delay);
    if (delay > 0) {
      element.style.transitionDelay = delay + "ms";
    }

    var items = [];

    element.querySelectorAll(ITEM_SELECTOR).forEach(function (item, index) {
      // Chỉ lấy thẻ ngoài cùng, bỏ thẻ lồng nhau.
      if (item.parentElement && item.parentElement.closest(ITEM_SELECTOR)) {
        return;
      }

      item.classList.add("reveal-item");
      item.style.transitionDelay =
        120 + Math.min(index, MAX_STAGGER) * STAGGER_MS + "ms";
      items.push(item);
    });

    element._revealItems = items;
    observer.observe(element);
  }

  function init() {
    document
      .querySelectorAll("section, footer, [data-reveal]")
      .forEach(function (element) {
        if (!element.classList.contains("reveal")) {
          prepare(element);
        }
      });

    window.__revealReady = true;
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
