import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    toggle(event) {
        const trigger = event.currentTarget;
        const panel = trigger.nextElementSibling;
        const isOpen = panel.classList.contains("is-open");

        // Fermer tous les autres
        this.element.querySelectorAll(".accordion__panel.is-open").forEach((p) => {
            if (p !== panel) {
                p.classList.remove("is-open");
                p.previousElementSibling.setAttribute("aria-expanded", "false");
            }
        });

        if (isOpen) {
            panel.classList.remove("is-open");
            trigger.setAttribute("aria-expanded", "false");
        } else {
            panel.classList.add("is-open");
            trigger.setAttribute("aria-expanded", "true");
        }
    }
}
