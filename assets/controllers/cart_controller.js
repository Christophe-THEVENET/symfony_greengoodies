// assets/controllers/cart_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static values = {
        url: String,
        productId: Number,
        quantity: Number,
    };

    static targets = ["quantity", "counter", "total"];

    connect() {
        // Affiche le toast si un message est présent dans le sessionStorage
        const toastSession = sessionStorage.getItem("toast");
        if (toastSession) {
            setTimeout(() => {
                this.showToast(toastSession);
                sessionStorage.removeItem("toast");
            }, 200);
        }
    }

    async trigger(event) {
        event?.preventDefault();

        try {
            const action = this.getActionType();
            const requestData = this.prepareRequestData(action);

            // Choix de la méthode HTTP selon l'action
            let method = "POST";
            if (action === "update") method = "PUT";
            if (action === "remove") method = "DELETE";

            const response = await fetch(this.urlValue, {
                method: method,
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body:
                    action === "add" || action === "update"
                        ? JSON.stringify(requestData)
                        : null,
            });

            const data = await response.json();

            if (data.success) {
                this.handleSuccess(data, action);
            } else {
                this.showToast(data.message || "Erreur", true);
            }
        } catch (error) {
            this.showToast("Erreur technique", true);
        }
    }

    getActionType() {
        const url = this.urlValue.toLowerCase();
        if (url.includes("/add/")) return "add";
        if (url.includes("/update/")) return "update";
        if (url.includes("/remove/")) return "remove";
        if (url.includes("/clear")) return "clear";
        return "unknown";
    }

    prepareRequestData(action) {
        const data = {};

        if (action === "add" || action === "update") {
            let quantity = 1;

            if (this.hasQuantityTarget && this.quantityTarget.value) {
                quantity = parseInt(this.quantityTarget.value) || 1;
            } else {
                const quantityInput = this.element.querySelector(
                    'input[name="quantity"], .quantity-input, input[type="number"]'
                );
                if (quantityInput && quantityInput.value) {
                    quantity = parseInt(quantityInput.value) || 1;
                } else if (this.hasQuantityValue) {
                    quantity = this.quantityValue;
                }
            }
            data.quantity = quantity;
        }

        return data;
    }

    handleSuccess(data, action) {
        switch (action) {
            case "add":
                this.showToast(
                    (data.message || "Produit ajouté au panier") +
                        ' <button class="btn btn-link btn-sm" onclick="window.location.href=\'/cart\'">Voir le panier</button>'
                );
                this.updateCartCounter(data.cart_count);
                showStimulusToast(
                    'Produit ajouté au panier ! <button class="btn btn-link btn-sm" onclick="window.location.href=\'/cart\'">Voir le panier</button>'
                );
                // Suppression de la redirection vers la page d'accueil
                break;

            case "update":
                this.showToast(data.message || "Quantité mise à jour");
                this.updateCartDisplay(data.cart);
                break;

            case "remove":
                this.showToast(data.message || "Produit supprimé");
                this.element.closest("article, .cart-item, tr")?.remove();
                break;

            case "clear":
                this.showToast(data.message || "Panier vidé");
                if (data.redirectUrl) {
                    sessionStorage.setItem("toast", data.message);
                    window.location.href = data.redirectUrl;
                } else {
                    window.location.reload();
                }
                break;
        }
    }

    updateCartCounter(count) {
        const counters = document.querySelectorAll(
            ".cart-counter, .cart-count"
        );
        counters.forEach((counter) => {
            counter.textContent = count;
        });

        const badges = document.querySelectorAll("[data-cart-badge]");
        badges.forEach((badge) => {
            badge.textContent = count;
        });
    }

    updateCartDisplay(cart) {
        if (this.hasTotalTarget) {
            this.totalTarget.textContent = `${cart.total}€`;
        }
        this.updateCartCounter(cart.count);

        const totals = document.querySelectorAll("[data-cart-total]");
        totals.forEach((total) => {
            total.textContent = `${cart.total}€`;
        });
    }

    showToast(message, isError = false) {
        if (typeof Toastify !== "undefined") {
            Toastify({
                text: message,
                duration: 3000,
                close: true,
                gravity: "top",
                position: "right",
                style: {
                    background: isError
                        ? "linear-gradient(to right, #dc3545, #ff7675)"
                        : "linear-gradient(to right, #28a745, #00b894)",
                },
                escapeMarkup: false,
            }).showToast();
        }
    }
}

function showStimulusToast(message, type = "success", duration = 5000) {
    const alertDiv = document.createElement("div");
    alertDiv.className = `alert alert-${type}`;
    alertDiv.setAttribute("data-controller", "alert");
    alertDiv.setAttribute("data-alert-duration-value", duration);
    alertDiv.setAttribute("data-alert-auto-hide-value", "true");
    alertDiv.innerHTML = `
        ${message}
        <button type="button"
            class="alert-close"
            data-action="click->alert#close"
            aria-label="Fermer">&times;</button>
    `;
    document.body.appendChild(alertDiv);
}
