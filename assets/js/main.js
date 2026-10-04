// Địa chỉ gốc của các trang public do server cấp qua <body data-base-url>.
function getBaseUrl() {
  return (
    document.body.dataset.baseUrl ||
    window.location.pathname.replace(/\/[^/]*$/, "")
  );
}

/* =====================================================
   TIỆP ANH MAIN JS
===================================================== */

/* ================= MOBILE MENU ================= */

function toggleSideNav() {
  const sideNav = document.getElementById("sideNavBar");

  const overlay = document.getElementById("sideNavOverlay");

  if (!sideNav || !overlay) {
    return;
  }

  sideNav.classList.toggle("active");

  overlay.classList.toggle("active");
}

/* ================= SEARCH ================= */

document.addEventListener("DOMContentLoaded", function () {
  const searchInput = document.getElementById("headerSearchInput");

  if (!searchInput) {
    return;
  }

  searchInput.addEventListener("keydown", function (event) {
    if (event.key !== "Enter") {
      return;
    }

    const keyword = searchInput.value.trim();

    if (keyword === "") {
      return;
    }

    const baseUrl = getBaseUrl();

    window.location.href =
      baseUrl + "/products.php?search=" + encodeURIComponent(keyword);
  });
});

/* ================= CART ================= */

function updateCartCount(count) {
  const badge = document.getElementById("cartCountBadge");

  if (!badge) {
    return;
  }

  badge.textContent = count;
}

async function addToCart(productId) {
  const token = document.querySelector('meta[name="csrf-token"]')?.content;

  try {
    const response = await fetch(
      getBaseUrl() + "/cart.php",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-Token": token || "",
        },
        body: JSON.stringify({
          action: "add",
          product_id: productId,
        }),
      },
    );
    const result = await response.json();

    if (!response.ok || !result.success) {
      throw new Error(result.message || "Không thể thêm sản phẩm vào giỏ.");
    }

    updateCartCount(result.cart_count);
    showCartFeedback(result.message);
  } catch (error) {
    showCartFeedback(error.message || "Có lỗi khi thêm sản phẩm vào giỏ.");
  }
}

function showCartFeedback(message) {
  let feedback = document.getElementById("cartFeedback");

  if (!feedback) {
    feedback = document.createElement("div");
    feedback.id = "cartFeedback";
    feedback.className = "cart-feedback";
    feedback.setAttribute("role", "status");
    feedback.setAttribute("aria-live", "polite");
    document.body.appendChild(feedback);
  }

  feedback.textContent = message;
  feedback.classList.add("visible");

  window.clearTimeout(feedback.hideTimeout);
  feedback.hideTimeout = window.setTimeout(
    () => feedback.classList.remove("visible"),
    2800,
  );
}

/* ================= DATABASE-BASED ASSISTANT ================= */

document.addEventListener("DOMContentLoaded", function () {
  const launcher = document.getElementById("assistantLauncher");
  const panel = document.getElementById("assistantPanel");
  const closeButton = document.getElementById("assistantClose");
  const form = document.getElementById("assistantForm");
  const input = document.getElementById("assistantInput");
  const messages = document.getElementById("assistantMessages");

  if (!launcher || !panel || !form || !input || !messages) {
    return;
  }

  const setPanelOpen = (open) => {
    panel.hidden = !open;
    launcher.setAttribute("aria-expanded", String(open));

    if (open) {
      input.focus();
    }
  };

  launcher.addEventListener("click", () => {
    setPanelOpen(panel.hidden);
  });
  closeButton?.addEventListener("click", () => setPanelOpen(false));

  const appendMessage = (text, role) => {
    const message = document.createElement("p");
    message.className = `assistant-message assistant-message-${role}`;
    message.textContent = text;
    messages.appendChild(message);
    messages.scrollTop = messages.scrollHeight;
  };

  const sendAssistantRequest = async (payload, displayText) => {
    appendMessage(displayText, "user");
    input.disabled = true;

    try {
      const response = await fetch(
        `${getBaseUrl()}/../api/assistant.php`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token":
              document.querySelector('meta[name="csrf-token"]')?.content || "",
          },
          body: JSON.stringify(payload),
        },
      );
      const result = await response.json();

      if (!response.ok) {
        throw new Error(result.error || "Chưa thể kết nối trợ lý.");
      }

      appendMessage(result.answer, "bot");
    } catch (error) {
      appendMessage(
        error.message || "Trợ lý hiện chưa phản hồi. Vui lòng thử lại.",
        "bot",
      );
    } finally {
      input.disabled = false;
      input.focus();
    }
  };

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const message = input.value.trim();

    if (!message) {
      return;
    }

    input.value = "";
    await sendAssistantRequest({ action: "chat", message }, message);
  });

  document.querySelectorAll(".assistant-quick-intent").forEach((button) => {
    button.addEventListener("click", async () => {
      if (input.disabled) {
        return;
      }

      await sendAssistantRequest(
        { action: "recommend", intent: button.dataset.intent },
        button.textContent.trim(),
      );
    });
  });
});

/* ================= PRODUCT COMPARISON ================= */

document.addEventListener("DOMContentLoaded", function () {
  const dialog = document.getElementById("productComparisonDialog");
  const closeButton = document.getElementById("comparisonClose");
  const title = document.getElementById("comparisonTitle");
  const result = document.getElementById("comparisonResult");
  const tray = document.getElementById("comparisonTray");
  const count = document.getElementById("comparisonCount");
  const compareButton = document.getElementById("compareSelectedProducts");
  const clearButton = document.getElementById("clearSelectedProducts");

  if (
    !dialog ||
    !closeButton ||
    !title ||
    !result ||
    !tray ||
    !count ||
    !compareButton ||
    !clearButton
  ) {
    return;
  }

  const selectedIds = new Set();

  closeButton.addEventListener("click", () => dialog.close());

  const syncSelection = () => {
    count.textContent = String(selectedIds.size);
    compareButton.disabled = selectedIds.size < 2;
    tray.hidden = selectedIds.size === 0;

    document.querySelectorAll(".compare-select-button").forEach((button) => {
      const selected = selectedIds.has(Number(button.dataset.productId));
      button.setAttribute("aria-pressed", String(selected));
      button.title = selected ? "Bỏ chọn so sánh" : "Chọn để so sánh";
      button.setAttribute(
        "aria-label",
        selected ? "Bỏ chọn sản phẩm so sánh" : "Chọn sản phẩm để so sánh",
      );
      const icon = button.querySelector(".material-symbols-outlined");
      if (icon) {
        icon.textContent = selected ? "check" : "add";
      }
    });
  };

  const openComparison = async (request, trigger) => {
    if (trigger) {
      trigger.disabled = true;
    }

    title.textContent = "Đang đối chiếu mẫu xe";
    result.replaceChildren();
    const loading = document.createElement("p");
    loading.className = "comparison-loading";
    loading.textContent = "Đang lấy thông tin từ catalog...";
    result.appendChild(loading);

    if (!dialog.open) {
      dialog.showModal();
    }

    try {
      const response = await fetch(
        `${getBaseUrl()}/../api/assistant.php`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-Token":
              document.querySelector('meta[name="csrf-token"]')?.content || "",
          },
          body: JSON.stringify(request),
        },
      );
      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.error || "Không thể so sánh mẫu xe lúc này.");
      }

      result.replaceChildren();

      if (data.comparison?.rows && data.comparison?.products) {
        title.textContent = "Bảng so sánh sản phẩm";
        const tableWrap = document.createElement("div");
        tableWrap.className = "comparison-table-wrap";
        const table = document.createElement("table");
        table.className = "comparison-table";
        const head = document.createElement("thead");
        const headRow = document.createElement("tr");
        const labelHeader = document.createElement("th");
        labelHeader.scope = "col";
        labelHeader.textContent = "Thông số";
        headRow.appendChild(labelHeader);

        for (const product of data.comparison.products) {
          const cell = document.createElement("th");
          cell.scope = "col";
          cell.textContent = product.name;
          headRow.appendChild(cell);
        }

        head.appendChild(headRow);
        table.appendChild(head);
        const body = document.createElement("tbody");

        for (const row of data.comparison.rows) {
          const tableRow = document.createElement("tr");
          const label = document.createElement("th");
          label.scope = "row";
          label.textContent = row.label;
          tableRow.appendChild(label);

          for (const value of row.values) {
            const cell = document.createElement("td");
            cell.textContent = value;
            tableRow.appendChild(cell);
          }

          body.appendChild(tableRow);
        }

        table.appendChild(body);
        tableWrap.appendChild(table);
        result.appendChild(tableWrap);
        const note = document.createElement("p");
        note.className = "comparison-notice";
        note.textContent = data.comparison.notice;
        result.appendChild(note);
        return;
      }

      if (data.comparison?.advantages || data.comparison?.tradeoffs) {
        title.textContent = `${data.comparison.current} và ${data.comparison.other}`;

        for (const [heading, facts, className] of [
          [
            "Điểm nổi trội",
            data.comparison.advantages,
            "comparison-advantages",
          ],
          ["Điểm thua", data.comparison.tradeoffs, "comparison-tradeoffs"],
        ]) {
          const section = document.createElement("section");
          section.className = `comparison-fact-group ${className}`;
          const sectionTitle = document.createElement("h3");
          sectionTitle.textContent = heading;
          section.appendChild(sectionTitle);
          const list = document.createElement("ul");

          for (const fact of facts || []) {
            const item = document.createElement("li");
            item.textContent = fact;
            list.appendChild(item);
          }

          section.appendChild(list);
          result.appendChild(section);
        }

        return;
      }

      title.textContent = "Chưa đủ dữ liệu đối chiếu";
      const message = document.createElement("p");
      message.textContent =
        data.answer || "Chưa có mẫu cùng danh mục đủ dữ liệu.";
      result.appendChild(message);
    } catch (error) {
      result.replaceChildren();
      title.textContent = "Không thể so sánh";
      const message = document.createElement("p");
      message.textContent = error.message || "Vui lòng thử lại.";
      result.appendChild(message);
    } finally {
      if (trigger) {
        trigger.disabled = false;
      }
    }
  };

  document.addEventListener("click", (event) => {
    const selectButton = event.target.closest(".compare-select-button");

    if (selectButton) {
      const productId = Number(selectButton.dataset.productId);

      if (selectedIds.has(productId)) {
        selectedIds.delete(productId);
      } else if (selectedIds.size >= 3) {
        showCartFeedback("Có thể chọn tối đa 3 sản phẩm để so sánh.");
        return;
      } else {
        selectedIds.add(productId);
      }

      syncSelection();
      return;
    }

    const singleCompareButton = event.target.closest(".compare-product-button");

    if (singleCompareButton) {
      openComparison(
        {
          action: "compare",
          product_id: Number(singleCompareButton.dataset.productId),
        },
        singleCompareButton,
      );
    }
  });

  clearButton.addEventListener("click", () => {
    selectedIds.clear();
    syncSelection();
  });

  compareButton.addEventListener("click", () => {
    if (selectedIds.size < 2) {
      return;
    }

    openComparison(
      {
        action: "compare",
        product_ids: Array.from(selectedIds),
      },
      compareButton,
    );
  });

  syncSelection();
});

/* ================= CLOSE MENU WITH ESC ================= */

document.addEventListener("keydown", function (event) {
  if (event.key !== "Escape") {
    return;
  }

  const sideNav = document.getElementById("sideNavBar");

  const overlay = document.getElementById("sideNavOverlay");

  if (sideNav) {
    sideNav.classList.remove("active");
  }

  if (overlay) {
    overlay.classList.remove("active");
  }
});
/* Slider sản phẩm & ưu đãi ở trang chủ */
(function () {
  var slider = document.getElementById("heroSlider");
  if (!slider) {
    return;
  }

  var track = slider.querySelector(".hero-slider-track");
  var slides = Array.prototype.slice.call(
    slider.querySelectorAll(".hero-slide"),
  );
  var dots = Array.prototype.slice.call(
    slider.querySelectorAll(".hero-slider-dots button"),
  );
  var index = 0;
  var timer = null;
  var DELAY = 5000;

  function go(next) {
    index = (next + slides.length) % slides.length;
    track.style.transform = "translateX(-" + index * 100 + "%)";
    slides.forEach(function (slide, i) {
      slide.classList.toggle("is-active", i === index);
    });
    dots.forEach(function (dot, i) {
      dot.classList.toggle("is-active", i === index);
      dot.setAttribute("aria-selected", i === index ? "true" : "false");
    });
  }

  function stop() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  }

  function start() {
    stop();
    if (
      slides.length > 1 &&
      !window.matchMedia("(prefers-reduced-motion: reduce)").matches
    ) {
      timer = setInterval(function () {
        go(index + 1);
      }, DELAY);
    }
  }

  var prev = slider.querySelector(".hero-slider-arrow.prev");
  var next = slider.querySelector(".hero-slider-arrow.next");
  if (prev) {
    prev.addEventListener("click", function () {
      go(index - 1);
      start();
    });
  }
  if (next) {
    next.addEventListener("click", function () {
      go(index + 1);
      start();
    });
  }
  dots.forEach(function (dot, i) {
    dot.addEventListener("click", function () {
      go(i);
      start();
    });
  });

  slider.addEventListener("mouseenter", stop);
  slider.addEventListener("mouseleave", start);
  slider.addEventListener("focusin", stop);
  slider.addEventListener("focusout", start);

  // Vuốt ngang trên điện thoại
  var startX = null;
  slider.addEventListener(
    "touchstart",
    function (e) {
      startX = e.touches[0].clientX;
      stop();
    },
    { passive: true },
  );
  slider.addEventListener(
    "touchend",
    function (e) {
      if (startX !== null) {
        var dx = e.changedTouches[0].clientX - startX;
        if (Math.abs(dx) > 50) {
          go(index + (dx < 0 ? 1 : -1));
        }
        startX = null;
      }
      start();
    },
    { passive: true },
  );

  go(0);
  start();
})();
