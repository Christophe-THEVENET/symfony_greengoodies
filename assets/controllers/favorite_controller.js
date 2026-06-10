import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["btn"];

    connect() {
        const key = this._storageKey();
        if (localStorage.getItem(key) === "true") {
            this.btnTarget.classList.add("is-on");
        }
    }

    toggle() {
        const key = this._storageKey();
        const isOn = this.btnTarget.classList.toggle("is-on");
        localStorage.setItem(key, isOn ? "true" : "false");
    }

    _storageKey() {
        return "favorite-" + (this.element.dataset.productId || window.location.pathname);
    }
}
