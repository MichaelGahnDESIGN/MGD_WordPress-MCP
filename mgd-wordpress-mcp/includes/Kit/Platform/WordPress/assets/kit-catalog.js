/* Weitere Plugins - Suche/Filter/Dialog lokal, Verhalten aus Maintenance > catalog.js */
/* Installieren läuft weiter über das geschützte Formular (admin-post.php + Nonce) */
(() => {
  "use strict";
  const normalize = (value) =>
    String(value || "")
      .toLocaleLowerCase("de")
      .normalize("NFD")
      .replace(/[̀-ͯ]/g, "")
      .replace(/ß/g, "ss");

  // jede Liste für sich, falls mal 2 auf einer Seite sind
  document.querySelectorAll("[data-mgd-kit-catalog]").forEach((root) => {
    const cards = [...root.querySelectorAll("[data-card]")];
    const search = root.querySelector("[data-search-input]");
    const status = root.querySelector("[data-status]");
    const price = root.querySelector("[data-price]");
    const categories = [...root.querySelectorAll("button[data-category]")];
    // Tags: mehrere wählbar, alle gewählten müssen passen
    const tagButtons = [...root.querySelectorAll("button[data-tag]")];
    const tagReset = root.querySelector("[data-tag-reset]");
    const picked = new Set();
    const empty = root.querySelector("[data-empty]");
    const reset = root.querySelector("[data-reset]");
    const count = root.querySelector("[data-count]");
    if (!search || !status || !price || !empty || !reset || !count) return;
    let category = "all";

    // Filter erst zeigen wenn JS läuft
    root.querySelectorAll("[data-filters]").forEach((el) => {
      el.hidden = false;
    });
    reset.hidden = !cards.length;

    function filter() {
      const terms = normalize(search.value).trim().split(/\s+/).filter(Boolean);
      let visible = 0;
      cards.forEach((card) => {
        const installed = ["installed", "update"].includes(card.dataset.state);
        const match =
          (category === "all" || card.dataset.category === category) &&
          (status.value === "all" ||
            (status.value === "installed" ? installed : card.dataset.state === status.value)) &&
          (price.value === "all" || card.dataset.tier === price.value) &&
          [...picked].every((t) => (card.dataset.tags || "").split("|").includes(t)) &&
          terms.every((term) => normalize(card.dataset.search).includes(term));
        card.hidden = !match;
        if (match) visible++;
      });
      empty.hidden = visible > 0;
      count.textContent = `${visible} ${visible === 1 ? "Plugin" : "Plugins"}`;
    }

    search.addEventListener("input", filter);
    status.addEventListener("change", filter);
    price.addEventListener("change", filter);
    categories.forEach((button) =>
      button.addEventListener("click", () => {
        category = button.dataset.category;
        categories.forEach((item) => item.setAttribute("aria-pressed", String(item === button)));
        filter();
      }),
    );
    const setTags = () => {
      tagButtons.forEach((b) => b.setAttribute("aria-pressed", String(picked.has(b.dataset.tag))));
      if (tagReset) tagReset.hidden = picked.size === 0;
      filter();
    };
    tagButtons.forEach((b) =>
      b.addEventListener("click", () => {
        if (picked.has(b.dataset.tag)) picked.delete(b.dataset.tag);
        else picked.add(b.dataset.tag);
        setTags();
      }),
    );
    if (tagReset) tagReset.addEventListener("click", () => { picked.clear(); setTags(); });
    reset.addEventListener("click", () => {
      picked.clear();
      tagButtons.forEach((b) => b.setAttribute("aria-pressed", "false"));
      if (tagReset) tagReset.hidden = true;
      search.value = "";
      status.value = "all";
      price.value = "all";
      category = "all";
      categories.forEach((item) => item.setAttribute("aria-pressed", String(item.dataset.category === "all")));
      filter();
      search.focus();
    });

    // Details Dialog mit Fokusfalle, Esc macht der Browser (cancel > close)
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
      // Klick auf den dunklen Rand schließt auch
      dialog.addEventListener("click", (event) => {
        if (event.target === dialog) dialog.close();
      });
      dialog.addEventListener("close", () => {
        if (trigger) trigger.focus();
      });
    }

    // Doppelklick verhindern beim Installieren
    root.querySelectorAll("form").forEach((form) =>
      form.addEventListener("submit", () => {
        form.setAttribute("aria-busy", "true");
        const button = form.querySelector("button");
        if (button) {
          button.disabled = true;
          button.textContent = "Wird installiert …";
        }
      }),
    );
  });
})();
