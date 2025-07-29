// assets/controllers/cart_controller.js
import { Controller } from "@hotwired/stimulus";
import NotificationController from "./notification_controller.js";

export default class extends Controller {
    static targets = ["total", "quantity"];
    static values = {
        url: String,
    };

    connect() {}

    async trigger(event) {
        event.preventDefault();
        const target = event.currentTarget;
        const url = target.dataset.cartUrlValue || this.urlValue;
        const action = this.getActionType(url);

        let options = {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
            },
        };

        if (action === "add") {
            options.headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(
                this.prepareQuantityProduct(action, target)
            );
        } else if (action === "update") {
            options.method = "PUT";
            options.headers["Content-Type"] = "application/json";
            options.body = JSON.stringify(
                this.prepareQuantityProduct(action, target)
            );
        } else if (action === "remove") {
            options.method = "DELETE";
        } else if (action === "validate") {
            const form = target.closest("form");
            options.body = new FormData(form);
            delete options.headers["Content-Type"]; // Important pour FormData
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

    prepareQuantityProduct(action, target) {
        const data = {};
        if (action === "add" || action === "update") {
            let quantity = 1;
            // Si target n'est pas un input, cherche l'input dans le parent
            if (this.hasQuantityTarget) {
                quantity = parseInt(this.quantityTarget.value) || 1;
            } else if (target && target.tagName === "INPUT") {
                quantity = parseInt(target.value) || 1;
            }
            data.quantity = quantity;
            console.log("data.quantity", data.quantity);
        }
        return data;
    }

    handleSuccess(data, action, target) {
        switch (action) {
            case "add":
                NotificationController.display(
                    "Produit ajouté au panier !",
                    "success"
                );
                break;

            case "update":
                this.updateCartDisplay(data.cart);
                if (data.cart && data.cart.updatedItem) {
                    const item = data.cart.updatedItem;
                    const article = this.element.querySelector(
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
                // Recharge la page si le panier est vide (par exemple si data.cart.count === 0)
                if (data.cart.count === 0) {
                    window.location.reload();
                    return;
                }
                this.updateCartDisplay(data.cart);
                target.closest("article")?.remove();
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
                        "success"
                    );
                }
                break;
        }
    }

    updateCartDisplay(cart) {
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
}
