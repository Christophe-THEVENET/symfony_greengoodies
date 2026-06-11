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

        // Anti-spam : on ignore le click si une requête est déjà en cours
        // pour ce bouton, et on le désactive le temps de l'aller-retour.
        if (this.pending) return;
        this.pending = true;
        const button = event.currentTarget;
        if (button) button.disabled = true;

        const quantity = this.hasQuantityTarget ? (parseInt(this.quantityTarget.value) || 1) : 1;
        const url = this.urlValue;

        try {
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
        } finally {
            this.pending = false;
            if (button) button.disabled = false;
        }
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
                this.updateSummary(data.cart?.summary);
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
            this.updateSummary(data.cart?.summary);
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

    // ****** QUANTITÉ +/- ******
    increment() {
        if (!this.hasQuantityTarget) return;
        const input = this.quantityTarget;
        const max = parseInt(input.max) || 99;
        const val = parseInt(input.value) || 1;
        if (val < max) {
            input.value = val + 1;
            this.notifyQuantityChange(input);
        }
    }

    decrement() {
        if (!this.hasQuantityTarget) return;
        const input = this.quantityTarget;
        const min = parseInt(input.min) || 1;
        const val = parseInt(input.value) || 1;
        if (val > min) {
            input.value = val - 1;
            this.notifyQuantityChange(input);
        }
    }

    // Déclenche l'action 'change' de l'input : met à jour le panier là où elle
    // est câblée (page panier), sans effet sur la fiche produit (pas d'action 'change').
    notifyQuantityChange(input) {
        input.dispatchEvent(new Event("change", { bubbles: true }));
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

        // Mise à jour du total de ligne (même format que le template : "12,90€")
        if (this.hasTotalTarget) {
            this.totalTarget.textContent = `${this.formatPrice(data.cart.updatedItem.total_price)}€`;
        }
    }

    // Rafraîchit tout le récapitulatif à partir des valeurs calculées par le serveur
    updateSummary(summary) {
        if (!summary) return;

        const setText = (selector, text) => {
            const el = document.querySelector(selector);
            if (el) el.textContent = text;
        };

        setText("[data-cart-subtotal]", `${this.formatPrice(summary.subtotal)}€`);
        setText("[data-cart-discount]", `−${this.formatPrice(summary.ecoDiscount)}€`);
        setText("[data-cart-total]", `${this.formatPrice(summary.total)}€`);

        const shippingEl = document.querySelector("[data-cart-shipping]");
        if (shippingEl) {
            shippingEl.textContent =
                summary.shipping > 0
                    ? `${this.formatPrice(summary.shipping)}€`
                    : shippingEl.dataset.freeLabel || "";
        }

        this.updateCartBadge(summary.count);
        this.updateShipProgress(summary.freeShippingRemaining);
    }

    // Barre "livraison offerte" : montant restant + remplissage de la jauge
    updateShipProgress(remaining) {
        const box = document.querySelector("[data-ship-progress]");
        const text = box?.querySelector("[data-ship-text]");
        if (!text) return;

        if (remaining > 0) {
            text.innerHTML = `${box.dataset.pre} <strong>${this.formatPrice(remaining)}€</strong> ${box.dataset.post}`;
        } else {
            text.textContent = box.dataset.unlocked;
        }

        const fill = box.querySelector("[data-ship-bar]");
        if (fill) {
            const threshold = parseFloat(box.dataset.threshold) || 49;
            const pct = remaining > 0
                ? Math.min(100, ((threshold - remaining) / threshold) * 100)
                : 100;
            fill.style.width = `${pct}%`;
        }
    }

    // Format identique au filtre Twig number_format(2, ',', ' ')
    formatPrice(value) {
        const [intPart, decPart] = Number(value).toFixed(2).split(".");
        return `${intPart.replace(/\B(?=(\d{3})+(?!\d))/g, " ")},${decPart}`;
    }

    updateCartBadge(count) {
        document.querySelectorAll("[data-cart-badge]").forEach((badge) => {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = "";
            } else {
                badge.textContent = "0";
                badge.style.display = "none";
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
