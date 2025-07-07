import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["burger", "mobileMenu", "overlay"];
    static classes = ["active"];

    connect() {
        this.isOpen = false;
    }

    disconnect() {
        // S'assurer que le scroll est restauré si le contrôleur est déconnecté
        document.body.style.overflow = "";
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
        // Bloquer le scroll du body
        document.body.style.overflow = "hidden";
    }

    close() {
        this.isOpen = false;
        this.burgerTarget.classList.remove(this.activeClass);
        this.mobileMenuTarget.classList.remove(this.activeClass);
        this.overlayTarget.classList.remove(this.activeClass);
        // Restaurer le scroll du body
        document.body.style.overflow = "";
    }

    closeOnOverlay() {
        this.close();
    }

    closeOnLink() {
        this.close();
    }
}
