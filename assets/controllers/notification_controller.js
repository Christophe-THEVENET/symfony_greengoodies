import { Controller } from "@hotwired/stimulus";

const TOAST_DURATION = 4000;

// Récupère (ou crée) le conteneur qui empile les toasts en colonne.
function getToastContainer() {
    let container = document.querySelector(".notification-toasts");
    if (!container) {
        container = document.createElement("div");
        container.className = "notification-toasts";
        document.body.appendChild(container);
    }
    return container;
}

// Crée un nouveau toast à chaque appel (un toast par click, empilés).
function spawnToast(message, type = "success", duration = TOAST_DURATION) {
    const toast = document.createElement("div");
    toast.className = `notification-toast notification-toast--${type}`;
    toast.textContent = message;
    getToastContainer().appendChild(toast);

    setTimeout(() => {
        toast.classList.add("notification-toast--hide");
        setTimeout(() => toast.remove(), 300); // durée de la transition
    }, duration);
}

export default class extends Controller {
    connect() {
        // Réponses HTML (session PHP -> sessionStorage JS -> toasts)
        const toastSession = sessionStorage.getItem("toast");
        if (toastSession) {
            spawnToast(toastSession, "success");
            sessionStorage.removeItem("toast");
        }
        const errorSession = sessionStorage.getItem("error");
        if (errorSession) {
            spawnToast(errorSession, "error");
            sessionStorage.removeItem("error");
        }
    }

    showToast(message, type = "success", duration = TOAST_DURATION) {
        spawnToast(message, type, duration);
    }

    // Méthode statique pour afficher un toast depuis n'importe où
    static display(message, type = "success", duration = TOAST_DURATION) {
        spawnToast(message, type, duration);
    }
}
