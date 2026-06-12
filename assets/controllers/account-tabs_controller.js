import { Controller } from "@hotwired/stimulus";

// Sidebar du compte : affiche le panneau correspondant à l'onglet cliqué
// (sans rechargement) et synchronise ?tab= dans l'URL.
export default class extends Controller {
    static targets = ["tab", "panel"];
    static values = { active: String };

    connect() {
        this.show(this.activeValue || "overview");
    }

    select(event) {
        // Bascule purement côté client : on ne touche pas à l'URL.
        this.show(event.currentTarget.dataset.tab);
    }

    show(tab) {
        this.tabTargets.forEach((el) => {
            el.classList.toggle("is-active", el.dataset.tab === tab);
        });
        this.panelTargets.forEach((el) => {
            el.hidden = el.dataset.tab !== tab;
        });
    }
}
