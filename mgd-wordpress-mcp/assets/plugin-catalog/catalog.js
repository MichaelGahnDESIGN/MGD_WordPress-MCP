/* Suche und Filter bleiben lokal; Installation läuft weiter über geschützte WordPress-Formulare. */
(() => {
  "use strict";
  const root = document.querySelector("[data-mgd-catalog]");
  if (!root) return;
  const cards = [...root.querySelectorAll("[data-card]")];
  const search = root.querySelector("#mgd-catalog-search");
  const status = root.querySelector("#mgd-catalog-status");
  const price = root.querySelector("#mgd-catalog-price");
  const categories = [...root.querySelectorAll("button[data-category]")];
  const empty = root.querySelector("[data-empty]");
  const reset = root.querySelector("[data-reset]");
  const count = root.querySelector("[data-count]");
  let category = "all";
  const normalize = (value) =>
    value
      .toLocaleLowerCase("de")
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/ß/g, "ss");
  root.querySelectorAll("[data-filters]").forEach((element) => {
    element.hidden = false;
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
          (status.value === "installed"
            ? installed
            : card.dataset.state === status.value)) &&
        (price.value === "all" || card.dataset.tier === price.value) &&
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
      categories.forEach((item) =>
        item.setAttribute("aria-pressed", String(item === button)),
      );
      filter();
    }),
  );
  reset.addEventListener("click", () => {
    search.value = "";
    status.value = "all";
    price.value = "all";
    category = "all";
    categories.forEach((item) =>
      item.setAttribute(
        "aria-pressed",
        String(item.dataset.category === "all"),
      ),
    );
    filter();
    search.focus();
  });
  const dialog = root.querySelector("dialog");
  let trigger;
  if (typeof dialog.showModal === "function") {
    dialog.addEventListener("keydown", (event) => {
      if (event.key !== "Tab") return;
      const targets = [...dialog.querySelectorAll('button, a[href], input, select, textarea, [tabindex="0"]')];
      const first = targets[0];
      const last = targets[targets.length - 1];
      if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    root.querySelectorAll("[data-details]").forEach((link) => {
      link.setAttribute("aria-haspopup", "dialog");
      link.addEventListener("click", (event) => {
        const template = root.querySelector(
          `#${CSS.escape(link.dataset.details)}`,
        );
        if (!template) return;
        event.preventDefault();
        trigger = link;
        dialog.querySelector("h2").textContent = link.dataset.title;
        dialog
          .querySelector("[data-dialog-content]")
          .replaceChildren(template.content.cloneNode(true));
        dialog.showModal();
        dialog.querySelector("[data-close]").focus();
      });
    });
    dialog
      .querySelector("[data-close]")
      .addEventListener("click", () => dialog.close());
    dialog.addEventListener("close", () => trigger?.focus());
  }
  root.querySelectorAll("form").forEach((form) =>
    form.addEventListener("submit", () => {
      form.setAttribute("aria-busy", "true");
      const button = form.querySelector("button");
      if (button) {
        button.disabled = true;
        button.textContent = button.hasAttribute("data-refresh")
          ? "Wird aktualisiert …"
          : "Wird installiert …";
      }
    }),
  );
})();
