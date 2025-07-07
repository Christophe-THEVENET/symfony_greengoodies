import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["burger", "mobileMenu", "overlay"];
    static classes = ["active"];

    connect() {
        this.isOpen = false;
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.isOpen = true;
        this.burgerTarget.classList.add(this.activeClass);
        this.mobileMenuTarget.classList.add(this.activeClass);
        this.overlayTarget.classList.add(this.activeClass);
    }

    close() {
        this.isOpen = false;
        this.burgerTarget.classList.remove(this.activeClass);
        this.mobileMenuTarget.classList.remove(this.activeClass);
        this.overlayTarget.classList.remove(this.activeClass);
    }

    closeOnOverlay() {
        this.close();
    }

    closeOnLink() {
        this.close();
    }
}
