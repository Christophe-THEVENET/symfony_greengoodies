import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["burger", "mobileMenu", "overlay"];
    static classes = ["active"];

    connect() {
        this.isOpen = false;
        this._onScroll = this._handleScroll.bind(this);
        window.addEventListener("scroll", this._onScroll, { passive: true });
        this._handleScroll();
    }

    disconnect() {
        document.body.style.overflow = "";
        window.removeEventListener("scroll", this._onScroll);
    }

    _handleScroll() {
        this.element.classList.toggle("is-scrolled", window.scrollY > 24);
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        this.isOpen = true;
        this.burgerTarget.classList.add(this.activeClass);
        this.mobileMenuTarget.classList.add(this.activeClass);
        this.overlayTarget.classList.add(this.activeClass);
        document.body.style.overflow = "hidden";
    }

    close() {
        this.isOpen = false;
        this.burgerTarget.classList.remove(this.activeClass);
        this.mobileMenuTarget.classList.remove(this.activeClass);
        this.overlayTarget.classList.remove(this.activeClass);
        document.body.style.overflow = "";
    }

    closeOnOverlay() {
        this.close();
    }

    closeOnLink() {
        this.close();
    }
}
