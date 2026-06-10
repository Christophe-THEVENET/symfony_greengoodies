import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["thumb", "main"];

    select(event) {
        const index = event.currentTarget.dataset.index;
        const imgSrc = event.currentTarget.querySelector("img").src;

        this.mainTarget.src = imgSrc;

        this.thumbTargets.forEach((t) => t.classList.remove("is-active"));
        event.currentTarget.classList.add("is-active");
    }
}
