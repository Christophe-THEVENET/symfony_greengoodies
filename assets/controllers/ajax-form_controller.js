import { Controller } from "@hotwired/stimulus";
import NotificationController from "./notification_controller.js";

// Soumet en AJAX n'importe quel formulaire placé dans cet élément.
// - Succès : le serveur renvoie { redirect: url }, on suit la redirection (PRG).
// - Erreur de validation (HTTP 422) : le serveur renvoie le HTML du formulaire
//   avec ses messages d'erreur, qu'on réinjecte sans recharger la page.
export default class extends Controller {
    connect() {
        this.element.addEventListener("submit", this.onSubmit);
    }

    disconnect() {
        this.element.removeEventListener("submit", this.onSubmit);
    }

    onSubmit = async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        event.preventDefault();

        const submitButton = form.querySelector('[type="submit"]');
        if (submitButton) submitButton.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: "POST",
                body: new FormData(form),
                headers: { "X-Requested-With": "XMLHttpRequest" },
            });

            // Succès : redirection PRG renvoyée par le serveur
            if (response.ok) {
                const data = await response.json();
                window.location.href = data.redirect;
                return;
            }

            // Validation échouée : réinjection du formulaire avec ses erreurs
            if (response.status === 422) {
                this.element.innerHTML = await response.text();
                return;
            }

            NotificationController.display("Erreur technique", "error");
        } catch (e) {
            NotificationController.display("Erreur technique", "error");
        } finally {
            // Le bouton a pu être remplacé par la réinjection : on le requête à nouveau
            const button = this.element.querySelector('[type="submit"]');
            if (button) button.disabled = false;
        }
    };
}
