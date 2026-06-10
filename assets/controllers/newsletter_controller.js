import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["input"];

    subscribe(event) {
        event.preventDefault();
        var input = this.inputTarget;
        var email = input.value.trim();

        // Supprimer ancien message
        var old = this.element.parentElement.querySelector(".news__msg");
        if (old) old.remove();

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            this._msg("Veuillez entrer un email valide.", "error");
            return;
        }

        this._msg("Merci ! Vous êtes inscrit.", "ok");
        input.value = "";
    }

    _msg(text, type) {
        var inner = this.element.closest(".news__inner");
        if (!inner) return;
        var old = inner.querySelector(".news__msg");
        if (old) old.remove();

        var el = document.createElement("p");
        el.className = "news__msg news__msg--" + type;
        el.textContent = text;
        inner.appendChild(el);
        setTimeout(function () { el.remove(); }, 3500);
    }
}
