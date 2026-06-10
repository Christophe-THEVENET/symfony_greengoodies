import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["btn"];

    select(event) {
        this.btnTargets.forEach((b) => b.classList.remove("is-active"));
        event.currentTarget.classList.add("is-active");
    }
}
