/* MGD Farbwähler - eine Komponente für alle Plattformen (WP, Shopware, JTL, Themes), ohne Fremdbibliothek
   Speicher per Adapter: MGDColor.setStore({load, save}), Standard localStorage. Texte per MGDColor.texts */
(function () {
  "use strict";
  if (window.MGDColor) return;

  const T = {
    pick: "Farbe wählen",
    dialog: "Farbwähler",
    area: "Sättigung und Helligkeit",
    areaValue: "Sättigung %s %, Helligkeit %v %",
    hue: "Farbton",
    hex: "HEX",
    copy: "Kopieren",
    copied: "Kopiert",
    quick: "Schnellfarben",
    recent: "Zuletzt benutzt",
    mine: "Meine Farben",
    save: "Farbe speichern",
    remove: "%c entfernen",
    empty: "Noch keine Farben gespeichert.",
    done: "Fertig",
    cancel: "Abbrechen",
    invalid: "Bitte eine Farbe wie #1A2B3C eingeben.",
  };
  const QUICK = ["#000000", "#FFFFFF", "#1E2C2C", "#D6C49A", "#B73843", "#2271B1", "#00A32A", "#DBA617", "#8C5BD6", "#F0F0F1"];
  const MAX_MINE = 24;
  const MAX_RECENT = 8;
  const HEX_RE = /^#[0-9A-F]{6}$/;

  // Farbmathe
  const norm = (v) => {
    let s = String(v || "").trim().toUpperCase();
    if (!s.startsWith("#")) s = "#" + s;
    if (/^#[0-9A-F]{3}$/.test(s)) s = "#" + s[1] + s[1] + s[2] + s[2] + s[3] + s[3];
    return HEX_RE.test(s) ? s : null;
  };
  const toRgb = (h) => [1, 3, 5].map((i) => parseInt(h.slice(i, i + 2), 16));
  const toHex = (r, g, b) => "#" + [r, g, b].map((x) => Math.round(x).toString(16).padStart(2, "0")).join("").toUpperCase();
  const toHsv = (hex) => {
    const [r, g, b] = toRgb(hex).map((x) => x / 255);
    const max = Math.max(r, g, b), min = Math.min(r, g, b), d = max - min;
    let h = 0;
    if (d) h = max === r ? ((g - b) / d) % 6 : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
    return { h: (h * 60 + 360) % 360, s: max ? d / max : 0, v: max };
  };
  const fromHsv = ({ h, s, v }) => {
    const f = (n) => {
      const k = (n + h / 60) % 6;
      return (v - v * s * Math.max(0, Math.min(k, 4 - k, 1))) * 255;
    };
    return toHex(f(5), f(3), f(1));
  };
  const clamp = (x, a, b) => Math.min(b, Math.max(a, x));
  const fmt = (t, map) => Object.keys(map).reduce((s, k) => s.split("%" + k).join(map[k]), t);
  const el = (tag, cls, attrs) => {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    Object.entries(attrs || {}).forEach(([k, v]) => e.setAttribute(k, v));
    return e;
  };
  const clean = (list, max) => (Array.isArray(list) ? list : []).map(norm).filter((c, i, a) => c && a.indexOf(c) === i).slice(0, max);

  // Speicher: Standard nur im Browser, Plattform-Adapter überschreibt
  let store = {
    load: async () => {
      try { return JSON.parse(localStorage.getItem("mgd-color") || "{}"); } catch (e) { return {}; }
    },
    save: async (d) => {
      try { localStorage.setItem("mgd-color", JSON.stringify(d)); } catch (e) { /* privat-Modus */ }
    },
  };
  let data = null;
  const loadData = async () => {
    if (data) return data;
    let d = {};
    try { d = (await store.load()) || {}; } catch (e) { d = {}; }
    data = { mine: clean(d.mine, MAX_MINE), recent: clean(d.recent, MAX_RECENT) };
    return data;
  };
  const saveData = () => { try { store.save({ mine: data.mine, recent: data.recent }); } catch (e) { /* egal */ } };

  // ein Popover für alle Felder
  let pop = null, ui = {}, cur = null, hsv = { h: 0, s: 0, v: 0 }, before = "", trigger = null;

  const build = () => {
    pop = el("div", "mgd-color", { role: "dialog", "aria-label": T.dialog, hidden: "" });
    ui.area = el("div", "mgd-color__area", { role: "slider", tabindex: "0", "aria-label": T.area, "aria-valuemin": "0", "aria-valuemax": "100" });
    ui.thumb = el("span", "mgd-color__thumb", { "aria-hidden": "true" });
    ui.area.append(ui.thumb);
    ui.hue = el("input", "mgd-color__hue", { type: "range", min: "0", max: "360", step: "1", "aria-label": T.hue });
    const row = el("div", "mgd-color__row");
    ui.preview = el("span", "mgd-color__preview", { "aria-hidden": "true" });
    const hexId = "mgd-color-hex-" + Math.random().toString(36).slice(2, 8);
    const lab = el("label", "mgd-color__label", { for: hexId });
    lab.textContent = T.hex;
    ui.hex = el("input", "mgd-color__hex", { id: hexId, type: "text", maxlength: "7", spellcheck: "false", autocomplete: "off", inputmode: "text" });
    ui.copy = el("button", "mgd-color__btn", { type: "button" });
    ui.copy.textContent = T.copy;
    row.append(ui.preview, lab, ui.hex, ui.copy);
    ui.msg = el("p", "mgd-color__msg", { role: "status", "aria-live": "polite" });
    ui.quick = group(T.quick);
    ui.recent = group(T.recent);
    ui.mine = group(T.mine);
    ui.save = el("button", "mgd-color__btn mgd-color__save", { type: "button" });
    ui.save.textContent = T.save;
    ui.mine.box.after(ui.save);
    const foot = el("div", "mgd-color__foot");
    ui.cancel = el("button", "mgd-color__btn", { type: "button" });
    ui.cancel.textContent = T.cancel;
    ui.done = el("button", "mgd-color__btn mgd-color__btn--primary", { type: "button" });
    ui.done.textContent = T.done;
    foot.append(ui.cancel, ui.done);
    pop.append(ui.area, ui.hue, row, ui.msg, ui.quick.wrap, ui.recent.wrap, ui.mine.wrap, foot);
    document.body.append(pop);
    events();
  };

  function group(title) {
    const wrap = el("div", "mgd-color__group");
    const h = el("p", "mgd-color__title");
    h.textContent = title;
    const box = el("div", "mgd-color__swatches", { role: "list" });
    wrap.append(h, box);
    return { wrap, box };
  }

  const swatches = (g, list, removable) => {
    g.box.replaceChildren();
    if (!list.length && removable) {
      const p = el("p", "mgd-color__empty");
      p.textContent = T.empty;
      g.box.append(p);
    }
    list.forEach((c) => {
      const item = el("span", "mgd-color__item", { role: "listitem" });
      const b = el("button", "mgd-color__chip", { type: "button", "aria-label": c, title: c, "data-c": c });
      b.style.background = c;
      item.append(b);
      if (removable) {
        const x = el("button", "mgd-color__del", { type: "button", "aria-label": fmt(T.remove, { c }), "data-del": c });
        x.textContent = "×";
        item.append(x);
      }
      g.box.append(item);
    });
    if (!removable && !list.length) g.wrap.hidden = true; else g.wrap.hidden = false;
  };

  const render = (from) => {
    const hex = fromHsv(hsv);
    ui.area.style.setProperty("--mgd-hue", "hsl(" + hsv.h + " 100% 50%)");
    ui.thumb.style.left = hsv.s * 100 + "%";
    ui.thumb.style.top = (1 - hsv.v) * 100 + "%";
    ui.thumb.style.background = hex;
    ui.area.setAttribute("aria-valuenow", String(Math.round(hsv.s * 100)));
    ui.area.setAttribute("aria-valuetext", fmt(T.areaValue, { s: Math.round(hsv.s * 100), v: Math.round(hsv.v * 100) }));
    if (from !== "hue") ui.hue.value = String(Math.round(hsv.h));
    if (from !== "hex") ui.hex.value = hex;
    ui.preview.style.background = hex;
    apply(hex);
  };

  const apply = (hex) => {
    if (!cur) return;
    cur.input.value = hex;
    cur.btn.style.background = hex;
    cur.btn.setAttribute("aria-label", T.pick + ": " + hex);
    cur.input.dispatchEvent(new Event("input", { bubbles: true }));
  };

  const setHex = (hex, from) => {
    const n = norm(hex);
    if (!n) return false;
    const keepHue = hsv.h;
    hsv = toHsv(n);
    if (hsv.s === 0) hsv.h = keepHue;
    render(from);
    return true;
  };

  const areaPick = (e) => {
    const r = ui.area.getBoundingClientRect();
    hsv.s = clamp((e.clientX - r.left) / r.width, 0, 1);
    hsv.v = 1 - clamp((e.clientY - r.top) / r.height, 0, 1);
    render();
  };

  function events() {
    ui.area.addEventListener("pointerdown", (e) => {
      ui.area.setPointerCapture(e.pointerId);
      areaPick(e);
      ui.area.focus();
    });
    ui.area.addEventListener("pointermove", (e) => { if (ui.area.hasPointerCapture(e.pointerId)) areaPick(e); });
    ui.area.addEventListener("keydown", (e) => {
      const step = e.shiftKey ? 0.1 : 0.01;
      const k = { ArrowLeft: ["s", -step], ArrowRight: ["s", step], ArrowUp: ["v", step], ArrowDown: ["v", -step] }[e.key];
      if (!k) return;
      e.preventDefault();
      hsv[k[0]] = clamp(hsv[k[0]] + k[1], 0, 1);
      render();
    });
    ui.hue.addEventListener("input", () => { hsv.h = Number(ui.hue.value); render("hue"); });
    ui.hex.addEventListener("input", () => {
      const ok = norm(ui.hex.value);
      ui.msg.textContent = ok || ui.hex.value.length < 4 ? "" : T.invalid;
      if (ok) setHex(ok, "hex");
    });
    ui.copy.addEventListener("click", async () => {
      const v = fromHsv(hsv);
      try { await navigator.clipboard.writeText(v); } catch (e) { ui.hex.select(); document.execCommand && document.execCommand("copy"); }
      ui.msg.textContent = T.copied + ": " + v;
    });
    pop.addEventListener("click", (e) => {
      const del = e.target.closest("[data-del]");
      if (del) {
        data.mine = data.mine.filter((c) => c !== del.dataset.del);
        saveData();
        swatches(ui.mine, data.mine, true);
        ui.save.focus();
        return;
      }
      const chip = e.target.closest("[data-c]");
      if (chip) setHex(chip.dataset.c);
    });
    ui.save.addEventListener("click", () => {
      const v = fromHsv(hsv);
      data.mine = clean([v, ...data.mine], MAX_MINE);
      saveData();
      swatches(ui.mine, data.mine, true);
    });
    ui.cancel.addEventListener("click", () => close(false));
    ui.done.addEventListener("click", () => close(true));
    pop.addEventListener("keydown", (e) => {
      if (e.key === "Escape") { e.preventDefault(); close(false); }
      if (e.key === "Tab") trap(e);
    });
    document.addEventListener("pointerdown", (e) => {
      if (!pop.hidden && !pop.contains(e.target) && (!cur || e.target !== cur.btn)) close(true);
    });
    window.addEventListener("resize", () => { if (!pop.hidden) place(); });
  }

  // Tab bleibt im Fenster
  function trap(e) {
    const f = [...pop.querySelectorAll("button, input, [tabindex='0']")].filter((x) => !x.closest("[hidden]"));
    if (!f.length) return;
    const first = f[0], last = f[f.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  }

  // Desktop neben dem Knopf, schmal = unten als Blatt
  const place = () => {
    pop.classList.toggle("mgd-color--sheet", window.innerWidth < 600);
    if (window.innerWidth < 600) { pop.style.left = pop.style.top = ""; return; }
    const r = cur.btn.getBoundingClientRect();
    const w = pop.offsetWidth, h = pop.offsetHeight;
    let left = r.left + window.scrollX, top = r.bottom + window.scrollY + 6;
    if (left + w > window.scrollX + document.documentElement.clientWidth - 8) left = window.scrollX + document.documentElement.clientWidth - w - 8;
    if (r.bottom + h + 6 > window.innerHeight && r.top - h - 6 > 0) top = r.top + window.scrollY - h - 6;
    // passt weder unten noch oben > so weit hoch, dass es ganz zu sehen ist
    else if (r.bottom + h + 6 > window.innerHeight) top = window.scrollY + Math.max(8, window.innerHeight - h - 8);
    pop.style.left = Math.max(8, left) + "px";
    pop.style.top = Math.max(8, top) + "px";
  };

  const open = async (field) => {
    if (!pop) build();
    await loadData();
    cur = field;
    trigger = field.btn;
    before = norm(field.input.value) || "#000000";
    hsv = toHsv(before);
    ui.msg.textContent = "";
    swatches(ui.quick, QUICK, false);
    swatches(ui.recent, data.recent, false);
    swatches(ui.mine, data.mine, true);
    pop.hidden = false;
    field.btn.setAttribute("aria-expanded", "true");
    render();
    place();
    ui.area.focus();
  };

  const close = (keep) => {
    if (!cur || pop.hidden) return;
    if (!keep) apply(before);
    else {
      const v = norm(cur.input.value);
      if (v && v !== before) {
        data.recent = clean([v, ...data.recent], MAX_RECENT);
        saveData();
      }
    }
    cur.input.dispatchEvent(new Event("change", { bubbles: true }));
    cur.btn.setAttribute("aria-expanded", "false");
    pop.hidden = true;
    const t = trigger;
    cur = null;
    if (t) t.focus();
  };

  // Feld aufrüsten: input[type=color] / .wp-color-picker / [data-mgd-color] > Textfeld + Farbknopf, name + Wert bleiben
  const attach = (input) => {
    if (!input || input.dataset.mgdColorReady) return;
    input.dataset.mgdColorReady = "1";
    const start = norm(input.value) || "#000000";
    if (input.type === "color") input.type = "text";
    input.value = start;
    input.classList.add("mgd-color__input");
    input.setAttribute("maxlength", "7");
    input.setAttribute("spellcheck", "false");
    const wrap = el("span", "mgd-color__field");
    input.before(wrap);
    const btn = el("button", "mgd-color__swatch", { type: "button", "aria-haspopup": "dialog", "aria-expanded": "false", "aria-label": T.pick + ": " + start });
    btn.style.background = start;
    wrap.append(btn, input);
    const field = { input, btn };
    btn.addEventListener("click", () => (cur === field && !pop.hidden ? close(true) : open(field)));
    input.addEventListener("input", () => {
      const v = norm(input.value);
      if (v) { btn.style.background = v; btn.setAttribute("aria-label", T.pick + ": " + v); }
    });
    input.addEventListener("blur", () => { const v = norm(input.value); if (v) input.value = v; });
  };

  const init = (root) => (root || document).querySelectorAll("input[type=color], input.wp-color-picker, input[data-mgd-color]").forEach(attach);

  window.MGDColor = {
    texts: T,
    attach,
    init,
    norm,
    setStore: (s) => { if (s && typeof s.load === "function" && typeof s.save === "function") { store = s; data = null; } },
    setTexts: (t) => Object.assign(T, t || {}),
  };
  init();
  if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", () => init());
})();
