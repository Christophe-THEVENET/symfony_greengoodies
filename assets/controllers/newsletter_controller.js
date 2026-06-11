import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["input", "button"];
    static values = { url: String };

    async subscribe(event) {
        event.preventDefault();
        const input = this.inputTarget;
        const email = input.value.trim();

        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            this._msg("Veuillez entrer un email valide.", "error");
            return;
        }

        // Anti-spam : on bloque le bouton le temps de la requête
        if (this.pending) return;
        this.pending = true;
        if (this.hasButtonTarget) this.buttonTarget.disabled = true;

        try {
            const response = await fetch(this.urlValue, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify({ email }),
            });
            const data = await response.json();
            this._msg(data.message, data.success ? "ok" : "error");
            if (data.success) input.value = "";
        } catch (e) {
            this._msg("Une erreur est survenue. Réessayez plus tard.", "error");
        } finally {
            this.pending = false;
            if (this.hasButtonTarget) this.buttonTarget.disabled = false;
        }
    }

    _msg(text, type) {
        const inner = this.element.closest(".news__inner");
        if (!inner) return;
        const old = inner.querySelector(".news__msg");
        if (old) old.remove();

        const el = document.createElement("p");
        el.className = "news__msg news__msg--" + type;
        el.textContent = text;
        inner.appendChild(el);
        setTimeout(() => el.remove(), 4000);
    }
}
