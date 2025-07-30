// assets/controllers/cart_controller.js
import { Controller } from "@hotwired/stimulus";
import NotificationController from "./notification_controller.js";

export default class extends Controller {
    static targets = ["quantity", "total"];
    static values = { url: String };

    async trigger(event) {
        event.preventDefault();
        const target = event.currentTarget || this.element;
        const url = target.dataset.cartUrlValue || this.urlValue;
        const action = this.getActionType(url);

        let options = {
            method: "POST",
            headers: { "X-Requested-With": "XMLHttpRequest" },
        };

        if (action === "add") {
            options.headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(this.prepareQuantityProduct(target));
        } else if (action === "update") {
            options.method = "PUT";
            options.headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(this.prepareQuantityProduct(target));
        } else if (action === "remove") {
            options.method = "DELETE";
        } else if (action === "validate") {
            const form = target.closest("form");
            options.body = new FormData(form);
            delete options.headers["Content-Type"];
        }

        try {
            const response = await fetch(url, options);
            const data = await response.json();
            this.handleSuccess(data, action, target);
        } catch (error) {
            NotificationController.display("Erreur technique", "error");
        }
    }

    getActionType(url) {
        url = url.toLowerCase();
        if (url.includes("/add/")) return "add";
        if (url.includes("/update/")) return "update";
        if (url.includes("/remove/")) return "remove";
        if (url.includes("/clear")) return "clear";
        if (url.includes("/validate")) return "validate";
        return "unknown";
    }

    prepareQuantityProduct(target) {
        let quantity = 1;
        // Si target est un input quantité
        if (target && target.tagName === "INPUT") {
            quantity = parseInt(target.value) || 1;
        } else if (this.hasQuantityTarget) {
            quantity = parseInt(this.quantityTarget.value) || 1;
        }
        return { quantity };
    }

    handleSuccess(data, action, target) {
        switch (action) {
            case "add":
                NotificationController.display(
                    data.message || "Produit ajouté au panier !",
                    data.success ? "success" : "error"
                );
                break;

            case "update":
                if (data.cart && data.cart.updatedItem) {
                    // Met à jour la quantité et le total de la ligne
                    if (this.hasQuantityTarget) {
                        this.quantityTarget.value =
                            data.cart.updatedItem.quantity;
                    }
                    // Cherche le total de la ligne dans le DOM
                    let totalDiv = this.hasTotalTarget
                        ? this.totalTarget
                        : target
                              .closest("article")
                              ?.querySelector('[data-cart-target="total"]');
                    if (totalDiv) {
                        totalDiv.textContent = `Total : ${Number(
                            data.cart.updatedItem.total_price
                        )
                            .toFixed(2)
                            .replace(".", ",")}€`;
                    }
                }
                this.updateCartTotal(data.cart?.total);
                break;

            case "remove":
                // Recharge la page si le panier est vide
                if (data.cart?.count === 0) {
                    window.location.reload();
                    return;
                }
                // Supprime la ligne du DOM
                target.closest("article")?.remove();
                this.updateCartTotal(data.cart?.total);
                NotificationController.display(
                    data.message || "Produit retiré du panier",
                    data.success ? "success" : "error"
                );
                break;

            case "clear":
                if (data.redirectUrl) {
                    sessionStorage.setItem("toast", data.message);
                    window.location.href = data.redirectUrl;
                } else {
                    window.location.reload();
                }
                break;

            case "validate":
                if (data.redirectUrl) {
                    sessionStorage.setItem(
                        "toast",
                        data.message || "Commande validée !"
                    );
                    window.location.href = data.redirectUrl;
                } else {
                    NotificationController.display(
                        data.message || "Commande validée !",
                        data.success ? "success" : "error"
                    );
                }
                break;
        }
    }

    updateCartTotal(total) {
        if (!total) return;
        document.querySelectorAll("[data-cart-total]").forEach((el) => {
            el.textContent = `${Number(total).toFixed(2).replace(".", ",")}€`;
        });
    }
}
