import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    toggle(event) {
        const trigger = event.currentTarget;
        // On passe par .accordion__item plutôt que par les frères directs : le
        // déclencheur est enveloppé dans un <h2> (hiérarchie de titres), donc
        // nextElementSibling ne pointe plus sur le panneau.
        const panel = trigger.closest(".accordion__item").querySelector(".accordion__panel");
        const isOpen = panel.classList.contains("is-open");

        // Fermer tous les autres
        this.element.querySelectorAll(".accordion__panel.is-open").forEach((p) => {
            if (p !== panel) {
                p.classList.remove("is-open");
                p.closest(".accordion__item")
                    .querySelector(".accordion__trigger")
                    .setAttribute("aria-expanded", "false");
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
