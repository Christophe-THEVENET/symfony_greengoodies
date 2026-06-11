import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        this._lang = localStorage.getItem("gg-lang") || "fr";
        this._apply();
    }

    toggle(event) {
        var btn = event.currentTarget;
        this._lang = btn.dataset.lang;
        localStorage.setItem("gg-lang", this._lang);
        this._apply();
    }

    _apply() {
        document.querySelectorAll("[data-fr]").forEach(function (el) {
            var text = el.dataset[this._lang];
            if (text === undefined) return;
            var attr = el.dataset.attr;
            if (attr) {
                el.setAttribute(attr, text);
                return;
            }
            el.textContent = text;
        }, this);

        // Mettre à jour les boutons de langue
        document.querySelectorAll("[data-lang-btn]").forEach(function (btn) {
            btn.classList.toggle("is-active", btn.dataset.lang === this._lang);
        }, this);
    }
}
