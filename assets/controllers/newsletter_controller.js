import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["input"];

    subscribe(event) {
        event.preventDefault();

        const email = this.inputTarget.value.trim();
        if (!email) return;

        this.inputTarget.value = "";
        this.inputTarget.placeholder = "Merci ! À bientôt ✓";

        setTimeout(() => {
            this.inputTarget.placeholder = "Votre email";
        }, 3000);
    }
}
