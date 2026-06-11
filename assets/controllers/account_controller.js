import { Controller } from "@hotwired/stimulus";
import NotificationController from "./notification_controller.js";

export default class extends Controller {
    static targets = ["button"];

    async toggleApiAccess(event) {
        event.preventDefault();
        const url = this.buttonTarget.form.action;
        const formData = new FormData(this.buttonTarget.form);

        try {
            const response = await fetch(url, {
                method: "POST",
                body: formData,
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                },
            });

            const data = await response.json();

            if (response.ok) {
                // Met à jour le libellé du bouton avec le texte traduit renvoyé
                if (data.label) {
                    const span = this.buttonTarget.querySelector("span.link");
                    if (span) {
                        span.textContent = data.label;
                    } else {
                        this.buttonTarget.textContent = data.label;
                    }
                }
                NotificationController.display(data.message, "success");
            } else {
                NotificationController.display(
                    data.message || "Erreur",
                    "error"
                );
            }
        } catch (e) {
            NotificationController.display("Erreur technique", "error");
        }
    }
}
