// assets/controllers/alert_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        duration: { type: Number, default: 5000 },
        autoHide: { type: Boolean, default: true },
    };

    connect() {
        this.show();

        if (this.autoHideValue) {
            this.timeout = setTimeout(() => {
                this.hide();
            }, this.durationValue);
        }
    }

    disconnect() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    }

    show() {
        this.element.classList.add("show");
    }

    hide() {
        this.element.classList.add("hide");

        setTimeout(() => {
            this.element.remove();
        }, 300); // Temps de l'animation
    }

    // Méthode pour fermer manuellement
    close() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
        this.hide();
    }
}
