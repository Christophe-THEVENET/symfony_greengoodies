// assets/controllers/toggle-password_controller.js
// Bouton Afficher/Masquer d'un champ mot de passe (option « toggle » des formulaires,
// cf. App\Form\Extension\TogglePasswordTypeExtension).
// Reprise allégée de symfony/ux-toggle-password (paquet abandonné par Symfony).
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        visibleLabel: { type: String, default: "Afficher" },
        hiddenLabel: { type: String, default: "Masquer" },
    };

    connect() {
        // Retour arrière Turbo : l'instantané contient déjà un bouton, on le remplace
        this.element.parentElement.querySelector(".toggle-password__button")?.remove();

        this.button = document.createElement("button");
        this.button.type = "button";
        this.button.className = "toggle-password__button";
        this.button.setAttribute("aria-controls", this.element.id);
        this.button.addEventListener("click", () => this.render(this.element.type === "password"));
        this.element.insertAdjacentElement("afterend", this.button);
        this.render(false);
    }

    render(visible) {
        this.element.type = visible ? "text" : "password";
        this.button.textContent = visible ? this.hiddenLabelValue : this.visibleLabelValue;
    }
}
