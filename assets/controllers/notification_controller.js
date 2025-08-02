import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    
    connect() {
        // pour les reponse html session PHP -> sessionStorage JS -> toasts
        const toastSession = sessionStorage.getItem("toast");
        if (toastSession) {
            this.showToast(toastSession, "success");
            sessionStorage.removeItem("toast");
        }
        const errorSession = sessionStorage.getItem("error");
        if (errorSession) {
            this.showToast(errorSession, "error");
            sessionStorage.removeItem("error");
        }

        
    }

    showToast(message, type = "success", duration = 4000) {
        const toast = document.createElement("div");
        toast.className = `notification-toast notification-toast--${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add("notification-toast--hide");
            setTimeout(() => toast.remove(), 300); // transition
        }, duration);
    }

    // Méthode statique pour afficher un toast depuis n'importe où
    static display(message, type = "success", duration = 4000) {
        const toast = document.createElement("div");
        toast.className = `notification-toast notification-toast--${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add("notification-toast--hide");
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
}
