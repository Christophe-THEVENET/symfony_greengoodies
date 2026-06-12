import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;

        this.factor = parseFloat(this.element.dataset.parallax) || 0.15;
        this.boundHandleScroll = this.handleScroll.bind(this);
        window.addEventListener("scroll", this.boundHandleScroll, { passive: true });
    }

    disconnect() {
        window.removeEventListener("scroll", this.boundHandleScroll);
    }

    handleScroll() {
        const rect = this.element.getBoundingClientRect();
        const center = rect.top + rect.height / 2;
        const viewportCenter = window.innerHeight / 2;
        const offset = (center - viewportCenter) * this.factor;
        this.element.style.transform = `translateY(${offset}px)`;
    }
}
