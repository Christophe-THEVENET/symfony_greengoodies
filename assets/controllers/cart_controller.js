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
            const quantityProduct = this.prepareQuantityProduct(action);

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
                        ? JSON.stringify(quantityProduct)
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

    prepareQuantityProduct(action) {
        const data = {};
        if (action === "add" || action === "update") {
            let quantity = 1;
            if (this.hasQuantityTarget && this.quantityTarget.value) {
                quantity = parseInt(this.quantityTarget.value) || 1;
            }
            data.quantity = quantity;
        }
        return data;
    }

    handleSuccess(data, action) {
        switch (action) {
            case "add":
                this.updateCartCounter(data.cart_count);
                showStimulusToast(
                    'Produit ajouté au panier ! <button class="btn-toast" onclick="window.location.href=\'/cart\'">Voir le panier</button>'
                );
                // Suppression de la redirection vers la page d'accueil
                break;

            case "update":
                this.showToast(data.message || "Quantité mise à jour");
                this.updateCartDisplay(data.cart); // <-- met à jour le DOM (total, compteur, etc)

                // Met à jour la quantité et le total de l'item modifié
                if (data.cart && data.cart.updatedItem) {
                    const item = data.cart.updatedItem;
                    const article = document.querySelector(
                        `[data-product-id="${item.product.id}"]`
                    );
                    if (article) {
                        article.querySelector(".cart__item-qty").value =
                            item.quantity;
                        article.querySelector(
                            ".cart__item-total"
                        ).textContent = `Total : ${Number(item.total_price)
                            .toFixed(2)
                            .replace(".", ",")}€`;
                    }
                }
                break;

            case "remove":
                this.showToast(data.message || "Produit supprimé");
                this.element.closest("article, .cart-item, tr")?.remove();
                this.updateCartDisplay(data.cart); // <-- Ajoute cette ligne
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
        console.log("Montant total reçu après suppression :", cart.total);
        if (this.hasTotalTarget) {
            this.totalTarget.textContent = `${Number(cart.total)
                .toFixed(2)
                .replace(".", ",")}€`;
        }
        const totals = document.querySelectorAll("[data-cart-total]");
        totals.forEach((total) => {
            total.textContent = `${Number(cart.total)
                .toFixed(2)
                .replace(".", ",")}€`;
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
    const toastEl = document.createElement("div");
    toastEl.className = `alert alert-${type}`;
    toastEl.classList.add("toast-block");
    toastEl.setAttribute("data-controller", "alert");
    toastEl.setAttribute("data-alert-duration-value", duration);
    toastEl.setAttribute("data-alert-auto-hide-value", "true");
    toastEl.innerHTML = `
        ${message}
        <button type="button"
            class="alert-close"
            data-action="click->alert#close"
            aria-label="Fermer">&times;</button>
    `;
    document.body.appendChild(toastEl);
}
