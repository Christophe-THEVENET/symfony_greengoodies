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

            console.log("🛒 Action:", action);
            console.log("🛒 Data:", requestData);
            console.log("🛒 URL:", this.urlValue);

            const response = await fetch(this.urlValue, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
                body: JSON.stringify(requestData),
            });

            const data = await response.json();

            if (data.success) {
                this.handleSuccess(data, action);
            } else {
                this.showToast(data.message || "Erreur", true);
            }
        } catch (error) {
            console.error("Erreur cart:", error);
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

            // 🔍 DEBUG COMPLET
            console.log("🛒 === DEBUG QUANTITY ===");
            console.log("- Element:", this.element);
            console.log("- hasQuantityTarget:", this.hasQuantityTarget);

            if (this.hasQuantityTarget) {
                console.log("- quantityTarget:", this.quantityTarget);
                console.log(
                    "- quantityTarget.value:",
                    this.quantityTarget.value
                );
                console.log(
                    "- quantityTarget.tagName:",
                    this.quantityTarget.tagName
                );
            }

            // Chercher tous les inputs
            const allInputs = this.element.querySelectorAll("input");
            console.log("- Tous les inputs trouvés:", allInputs);
            allInputs.forEach((input, i) => {
                console.log(`  Input ${i}:`, input, "value:", input.value);
            });

            // Essayer toutes les méthodes dans l'ordre
            if (this.hasQuantityTarget && this.quantityTarget.value) {
                quantity = parseInt(this.quantityTarget.value) || 1;
                console.log("✅ Quantité depuis target:", quantity);
            } else {
                const quantityInput = this.element.querySelector(
                    'input[name="quantity"], .quantity-input, input[type="number"]'
                );
                console.log("- quantityInput trouvé:", quantityInput);

                if (quantityInput && quantityInput.value) {
                    quantity = parseInt(quantityInput.value) || 1;
                    console.log("✅ Quantité depuis querySelector:", quantity);
                } else if (this.hasQuantityValue) {
                    quantity = this.quantityValue;
                    console.log("✅ Quantité depuis value:", quantity);
                }
            }

            console.log("🎯 Quantité finale:", quantity);
            data.quantity = quantity;
        }

        return data;
    }

    handleSuccess(data, action) {
        switch (action) {
            case "add":
                this.showToast(data.message || "Produit ajouté au panier");
                this.updateCartCounter(data.cart_count);
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
                // Redirection ou rechargement de page
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
        // Met à jour TOUS les compteurs de panier sur la page
        const counters = document.querySelectorAll(
            ".cart-counter, .cart-count"
        );
        counters.forEach((counter) => {
            counter.textContent = count;
        });

        // Met à jour aussi les badges dans la navigation
        const badges = document.querySelectorAll("[data-cart-badge]");
        badges.forEach((badge) => {
            badge.textContent = count;
        });
    }

    updateCartDisplay(cart) {
        // Met à jour le total
        if (this.hasTotalTarget) {
            this.totalTarget.textContent = `${cart.total}€`;
        }

        // Met à jour le compteur
        this.updateCartCounter(cart.count);

        // Met à jour les éléments avec data-cart-total
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
            }).showToast();
        } else {
            alert(message);
        }
    }
}
