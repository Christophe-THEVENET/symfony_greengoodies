import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    connect() {
        if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            this.element.classList.add("is-in");
            return;
        }

        this.observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const delay = this.element.dataset.revealDelay || 0;
                        setTimeout(() => {
                            this.element.classList.add("is-in");
                        }, delay);
                        this.observer.unobserve(this.element);
                    }
                });
            },
            { threshold: 0.15, rootMargin: "0px 0px -40px 0px" }
        );

        this.observer.observe(this.element);
    }

    disconnect() {
        this.observer?.unobserve(this.element);
    }
}
