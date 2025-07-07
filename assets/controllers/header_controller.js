import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["burger", "mobileMenu", "overlay"];
    static classes = ["active"];

    connect() {
        console.log("Header controller connected");
        this.isOpen = false;

        // Utiliser matchMedia pour détecter les changements de media query
        this.mobileMediaQuery = window.matchMedia("(max-width: 768px)");
        this.handleMediaQueryChange = this.handleMediaQueryChange.bind(this);

        // Écouter les changements de media query
        this.mobileMediaQuery.addEventListener(
            "change",
            this.handleMediaQueryChange
        );

        // Vérifier l'état initial
        this.handleMediaQueryChange(this.mobileMediaQuery);

        // Log pour debug
        console.log("Initial screen width:", window.innerWidth);
        console.log("Media query matches:", this.mobileMediaQuery.matches);
    }

    disconnect() {
        this.mobileMediaQuery.removeEventListener(
            "change",
            this.handleMediaQueryChange
        );

        // S'assurer que le scroll est restauré
        document.body.style.overflow = "";
    }

    handleMediaQueryChange(e) {
        console.log("Media query changed:", e.matches ? "Mobile" : "Desktop");
        console.log("Current screen width:", window.innerWidth);

        // Si on passe en desktop et que le menu est ouvert, le fermer
        if (!e.matches && this.isOpen) {
            this.close();
        }
    }

    toggle() {
        console.log("Toggle clicked, isOpen:", this.isOpen);
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        console.log("Opening menu");
        this.isOpen = true;
        this.burgerTarget.classList.add(this.activeClass);
        this.mobileMenuTarget.classList.add(this.activeClass);
        this.overlayTarget.classList.add(this.activeClass);

        // Mettre à jour l'attribut aria-expanded
        this.burgerTarget.setAttribute("aria-expanded", "true");

        // Empêcher le scroll du body
        document.body.style.overflow = "hidden";

        // Focus sur le premier lien pour l'accessibilité
        const firstLink = this.mobileMenuTarget.querySelector("a");
        if (firstLink) {
            firstLink.focus();
        }
    }

    close() {
        console.log("Closing menu");
        this.isOpen = false;
        this.burgerTarget.classList.remove(this.activeClass);
        this.mobileMenuTarget.classList.remove(this.activeClass);
        this.overlayTarget.classList.remove(this.activeClass);

        // Mettre à jour l'attribut aria-expanded
        this.burgerTarget.setAttribute("aria-expanded", "false");

        // Restaurer le scroll du body
        document.body.style.overflow = "";
    }

    // Fermer le menu en cliquant sur l'overlay
    closeOnOverlay() {
        console.log("Closing on overlay");
        this.close();
    }

    // Fermer le menu en cliquant sur un lien
    closeOnLink() {
        console.log("Closing on link");
        this.close();
    }
}
