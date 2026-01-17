// assets/controllers/cart_controller.js
import { Controller } from "@hotwired/stimulus";
import NotificationController from "./notification_controller.js";

export default class extends Controller {
    static targets = ["quantity", "total"];
    static values = {
        url: String,
        removeUrl: String,
        productId: Number,
    };

    // ****** AJOUT AU PANIER ******
    async addToCart(event) {
        event.preventDefault();
        const quantity = parseInt(this.quantityTarget.value) || 1;
        const url = this.urlValue;

        await this.sendRequest(
            url,
            {
                method: "POST",
                body: JSON.stringify({ quantity }),
            },
            (data) => {
                NotificationController.display(
                    data.message,
                    data.success ? "success" : "error"
                );
                if (data.cart_count !== undefined) {
                    this.updateCartBadge(data.cart_count);
                }
            }
        );
    }

    // ****** MISE À JOUR DE QUANTITÉ ******
    async updateQuantity(event) {
        event.preventDefault();
        const quantity = parseInt(this.quantityTarget.value) || 1;
        const url = this.urlValue;

        await this.sendRequest(
            url,
            {
                method: "PUT",
                body: JSON.stringify({ quantity }),
            },
            (data) => {
                this.updateItemUI(data);
                this.updateCartTotal(data.cart?.total);
                if (data.cart?.count !== undefined) {
                    this.updateCartBadge(data.cart.count);
                }
            }
        );
    }

    // ****** SUPPRESSION D'ARTICLE ******
    async removeItem(event) {
        event.preventDefault();
        const url = this.removeUrlValue || this.urlValue;

        await this.sendRequest(url, { method: "DELETE" }, (data) => {
            if (data.cart?.count === 0) {
                this.updateCartBadge(0);
                window.location.reload();
                return;
            }

            // Supprimer l'article du DOM
            this.element.remove();
            this.updateCartTotal(data.cart?.total);
            if (data.cart?.count !== undefined) {
                this.updateCartBadge(data.cart.count);
            }
            NotificationController.display(
                data.message,
                data.success ? "success" : "error"
            );
        });
    }

    // ****** VIDER LE PANIER ******
    async clearCart(event) {
        event.preventDefault();
        const url = this.urlValue;

        await this.sendRequest(url, { method: "POST" }, (data) => {
            this.updateCartBadge(0);
            if (data.redirectUrl) {
                sessionStorage.setItem("toast", data.message);
                window.location.href = data.redirectUrl;
            } else {
                window.location.reload();
            }
        });
    }

    // ****** VALIDATION DU PANIER ******
    async validateCart(event) {
        event.preventDefault();
        const url = this.urlValue;
        const form = event.currentTarget;

        await this.sendRequest(
            url,
            {
                method: "POST",
                // contient uniquement le token CSRF
                body: new FormData(form),
            },
            (data) => {
                if (data.redirectUrl) {
                    this.updateCartBadge(0);
                    sessionStorage.setItem("toast", data.message);
                    window.location.href = data.redirectUrl;
                } else {
                    NotificationController.display(
                        data.message,
                        data.success ? "success" : "error"
                    );
                }
            }
        );
    }

    // ****** MÉTHODES UTILITAIRES ******
    updateItemUI(data) {
        if (!data.cart?.updatedItem) return;

        // Mise à jour de la quantité si l'élément existe
        if (this.hasQuantityTarget) {
            this.quantityTarget.value = data.cart.updatedItem.quantity;
        }

        // Mise à jour du total de ligne
        if (this.hasTotalTarget) {
            const formattedPrice = Number(data.cart.updatedItem.total_price)
                .toFixed(2)
                .replace(".", ",");
            this.totalTarget.textContent = `Total : ${formattedPrice}€`;
        }
    }

    updateCartTotal(total) {
        if (!total) return;

        const formattedTotal = Number(total).toFixed(2).replace(".", ",");
        document.querySelectorAll("[data-cart-total]").forEach((el) => {
            el.textContent = `${formattedTotal}€`;
        });
    }

    updateCartBadge(count) {
        document.querySelectorAll("[data-cart-badge]").forEach((badge) => {
            if (count > 0) {
                badge.textContent = count;
                badge.classList.remove("header__cart-badge--hidden");
            } else {
                badge.textContent = "";
                badge.classList.add("header__cart-badge--hidden");
            }
        });
    }

    async sendRequest(url, options = {}, callback) {
        const defaultOptions = {
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Content-Type": "application/json",
            },
        };

        if (options.body instanceof FormData) {
            delete defaultOptions.headers["Content-Type"];
        }

        try {
            const response = await fetch(url, {
                ...defaultOptions,
                ...options,
            });
            const data = await response.json();

            if (callback && typeof callback === "function") {
                callback(data);
            }
        } catch (error) {
            NotificationController.display("Erreur technique", "error");
        }
    }
}
