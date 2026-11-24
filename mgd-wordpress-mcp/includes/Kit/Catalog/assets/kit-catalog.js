/* Weitere Plugins JTL - Suche/Filter/Dialog, Verhalten wie Platform/WordPress/assets/kit-catalog.js */
/* ohne Install-Knopf, Status-Filter nur wenn da (ohne Erkennung gibts den nicht) */
(() => {
  "use strict";
  const normalize = (value) =>
    String(value || "")
      .toLocaleLowerCase("de")
      .normalize("NFD")
      .replace(/[̀-ͯ]/g, "")
      .replace(/ß/g, "ss");

  // JTL zeigt Übersicht + Tab auf einer Seite > jede Liste nur 1x anfassen
  // Skript kann zwischen 2 Listen stehen > 2. Liste ist beim Start noch nicht geparst, deshalb auch nach DOMContentLoaded (Befund Codex-Review Suche)
  const setup = (root) => {
    root.setAttribute("data-ready", "");
    const cards = [...root.querySelectorAll("[data-card]")];
    const search = root.querySelector("[data-search-input]");
    const status = root.querySelector("[data-status]");
    const price = root.querySelector("[data-price]");
    const categories = [...root.querySelectorAll("button[data-category]")];
    const empty = root.querySelector("[data-empty]");
    const reset = root.querySelector("[data-reset]");
    const count = root.querySelector("[data-count]");
    if (!search || !price || !empty || !reset || !count) return;
    let category = "all";

    // Filter erst zeigen wenn JS läuft
    root.querySelectorAll("[data-filters]").forEach((el) => {
      el.hidden = false;
    });
    reset.hidden = !cards.length;

    function filter() {
      const terms = normalize(search.value).trim().split(/\s+/).filter(Boolean);
      const st = status ? status.value : "all";
      let visible = 0;
      cards.forEach((card) => {
        const installed = ["installed", "update"].includes(card.dataset.state);
        const match =
          (category === "all" || card.dataset.category === category) &&
          (st === "all" || (st === "installed" ? installed : card.dataset.state === st)) &&
          (price.value === "all" || card.dataset.tier === price.value) &&
          terms.every((term) => normalize(card.dataset.search).includes(term));
        card.hidden = !match;
        if (match) visible++;
      });
      empty.hidden = visible > 0;
      count.textContent = `${visible} ${visible === 1 ? "Plugin" : "Plugins"}`;
    }

    search.addEventListener("input", filter);
    if (status) status.addEventListener("change", filter);
    price.addEventListener("change", filter);
    categories.forEach((button) =>
      button.addEventListener("click", () => {
        category = button.dataset.category;
        categories.forEach((item) => item.setAttribute("aria-pressed", String(item === button)));
        filter();
      }),
    );
    reset.addEventListener("click", () => {
      search.value = "";
      if (status) status.value = "all";
      price.value = "all";
      category = "all";
      categories.forEach((item) => item.setAttribute("aria-pressed", String(item.dataset.category === "all")));
      filter();
      search.focus();
    });

    // Details Dialog mit Fokusfalle, Esc macht der Browser
    const dialog = root.querySelector("dialog");
    let trigger = null;
    if (dialog && typeof dialog.showModal === "function") {
      const closer = dialog.querySelector("[data-close]");
      dialog.addEventListener("keydown", (event) => {
        if (event.key !== "Tab") return;
        const targets = [...dialog.querySelectorAll('button, a[href], input, select, textarea, [tabindex="0"]')];
        if (!targets.length) return;
        const first = targets[0];
        const last = targets[targets.length - 1];
        if (event.shiftKey && document.activeElement === first) {
          event.preventDefault();
          last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
          event.preventDefault();
          first.focus();
        }
      });
      root.querySelectorAll("[data-details]").forEach((link) => {
        link.setAttribute("aria-haspopup", "dialog");
        link.addEventListener("click", (event) => {
          const template = document.getElementById(link.dataset.details);
          if (!template || !template.content) return;
          event.preventDefault();
          trigger = link;
          dialog.querySelector("h2").textContent = link.dataset.title || "";
          dialog.querySelector("[data-dialog-content]").replaceChildren(template.content.cloneNode(true));
          dialog.showModal();
          if (closer) closer.focus();
        });
      });
      if (closer) closer.addEventListener("click", () => dialog.close());
      dialog.addEventListener("click", (event) => {
        if (event.target === dialog) dialog.close();
      });
      dialog.addEventListener("close", () => {
        if (trigger) trigger.focus();
      });
    }
  };
  const init = () => document.querySelectorAll("[data-mgd-kit-catalog]:not([data-ready])").forEach(setup);
  init();
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", init);
})();
