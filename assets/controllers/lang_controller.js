import { Controller } from "@hotwired/stimulus";

const STORAGE_KEY = "gg-lang";

export default class extends Controller {
    static targets = ["btn"];

    connect() {
        this.currentLang = localStorage.getItem(STORAGE_KEY) || "fr";
        this.applyLang(this.currentLang);
    }

    toggle(event) {
        const lang = event.currentTarget.dataset.lang;
        if (!lang || lang === this.currentLang) return;

        this.currentLang = lang;
        localStorage.setItem(STORAGE_KEY, lang);
        this.applyLang(lang);
    }

    applyLang(lang) {
        // Mettre à jour les boutons
        this.btnTargets.forEach((btn) => {
            btn.classList.toggle("is-active", btn.dataset.lang === lang);
        });

        // Mettre à jour les textes
        document.querySelectorAll("[data-fr]").forEach((el) => {
            const text = el.dataset[lang];
            if (text === undefined) return;

            const attr = el.dataset.attr;
            if (attr) {
                el.setAttribute(attr, text);
                return;
            }
            el.textContent = text;
        });

        // Exposer pour le contenu rendu dynamiquement
        window.__ggApplyLang = (lang) => this.applyLang(lang);
    }
}
