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

// Au chargement : si l'URL contient un hash de section (arrivée depuis une
// autre page, ex. /#histoire), on scrolle vers la cible puis on retire le #.
function handleHashScroll() {
    var hash = window.location.hash;
    if (hash.length < 2) return;
    var el = document.getElementById(hash.slice(1));
    if (!el) return;
    el.scrollIntoView({ behavior: "smooth" });
    history.replaceState(null, "", window.location.pathname + window.location.search);
}

// Exécuter au chargement initial
initReveal();
handleHashScroll();

// Réexécuter après chaque visite Turbo
document.addEventListener("turbo:render", function () {
    initReveal();
    handleHashScroll();
});

// Compteur de navigations internes. Turbo Drive conserve l'état JS entre les
// visites, donc ce compteur survit à la navigation : > 1 signifie qu'on a déjà
// navigué dans le site, et qu'un history.back() reste sur le site.
var internalNavCount = 0;
document.addEventListener("turbo:load", function () { internalNavCount++; });

// Liens « retour » (vue produit, login, panier vide). On reproduit le retour
// navigateur (instantané, scroll restauré, pas de ré-animation ni double rendu)
// quand on a un historique interne ; sinon on laisse le lien suivre son href
// (ex. /#produits, géré par handleHashScroll).
document.addEventListener("click", function (e) {
    var link = e.target.closest("[data-back]");
    if (!link) return;
    if (internalNavCount > 1 && window.history.length > 1) {
        e.preventDefault();
        window.history.back();
    }
});

// Scroll smooth si la cible est sur la page courante (accueil),
// sinon on laisse le navigateur suivre le href (ex. depuis une autre page).
document.addEventListener("click", function (e) {
    var link = e.target.closest("[data-scroll]");
    if (!link) return;
    var id = link.getAttribute("data-scroll");
    var el = document.getElementById(id);
    if (!el) return; // cible absente -> navigation normale vers l'accueil
    e.preventDefault();
    el.scrollIntoView({ behavior: "smooth" });
    // On ne modifie pas l'URL : pas de hashtag visible
});
