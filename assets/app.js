import './bootstrap.js';
import "./styles/app.scss";

// Reveal au scroll — compatible Turbo
function initReveal() {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
        document.querySelectorAll("[data-reveal]:not(.is-in)").forEach(function (el) { el.classList.add("is-in"); });
        return;
    }
    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                var el = entry.target;
                var delay = parseInt(el.getAttribute("data-reveal-delay")) || 0;
                setTimeout(function () { el.classList.add("is-in"); }, delay);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.1, rootMargin: "0px 0px -40px 0px" });
    document.querySelectorAll("[data-reveal]:not(.is-in)").forEach(function (el) { observer.observe(el); });
}

// Exécuter au chargement initial
initReveal();

// Réexécuter après chaque visite Turbo
document.addEventListener("turbo:render", function () {
    initReveal();
});
