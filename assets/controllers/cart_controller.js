// assets/controllers/cart_controller.js
import { Controller } from "@hotwired/stimulus";
import NotificationController from "./notification_controller.js";

export default class extends Controller {
    static targets = ["quantity", "total"];
    static values = { 
        url: String,
        action: String // Nouveau: stocker l'action directement dans le HTML
    };

    // Actions principales - une méthode par action
    async addToCart(event) {
        event.preventDefault();
        const quantity = this.getQuantity(event.currentTarget);
        const url = event.currentTarget.dataset.cartUrlValue || this.urlValue;
        
        await this.sendRequest(url, {
            method: "POST",
            body: JSON.stringify({ quantity })
        }, (data) => {
            NotificationController.display(data.message, data.success ? "success" : "error");
        });
    }
    
    async updateQuantity(event) {
        event.preventDefault();
        const target = event.currentTarget;
        const quantity = this.getQuantity(target);
        const url = target.dataset.cartUrlValue || this.urlValue;
        
        await this.sendRequest(url, {
            method: "PUT",
            body: JSON.stringify({ quantity })
        }, (data) => {
            this.updateItemUI(target, data);
            this.updateCartTotal(data.cart?.total);
        });
    }
    
    async removeItem(event) {
        event.preventDefault();
        const target = event.currentTarget;
        const url = target.dataset.cartUrlValue || this.urlValue;
        
        await this.sendRequest(url, { method: "DELETE" }, (data) => {
            if (data.cart?.count === 0) {
                window.location.reload();
                return;
            }
            
            target.closest("article")?.remove();
            this.updateCartTotal(data.cart?.total);
            NotificationController.display(data.message, data.success ? "success" : "error");
        });
    }
    
    async clearCart(event) {
        event.preventDefault();
        const url = event.currentTarget.dataset.cartUrlValue || this.urlValue;
        
        await this.sendRequest(url, { method: "POST" }, (data) => {
            if (data.redirectUrl) {
                sessionStorage.setItem("toast", data.message);
                window.location.href = data.redirectUrl;
            } else {
                window.location.reload();
            }
        });
    }
    
    async validateCart(event) {
        event.preventDefault();
        const target = event.currentTarget;
        const url = target.dataset.cartUrlValue || this.urlValue;
        const form = target.closest("form");
        
        await this.sendRequest(url, {
            method: "POST",
            body: new FormData(form)
        }, (data) => {
            if (data.redirectUrl) {
                sessionStorage.setItem("toast", data.message);
                window.location.href = data.redirectUrl;
            } else {
                NotificationController.display(data.message, data.success ? "success" : "error");
            }
        });
    }
    
    // Méthodes utilitaires
    async sendRequest(url, options = {}, callback) {
        const defaultOptions = {
            headers: { 
                "X-Requested-With": "XMLHttpRequest",
                "Content-Type": "application/json" 
            }
        };
        
        // Si FormData, ne pas ajouter Content-Type (le navigateur s'en charge)
        if (options.body instanceof FormData) {
            delete defaultOptions.headers["Content-Type"];
        }
        
        try {
            const response = await fetch(url, {...defaultOptions, ...options});
            const data = await response.json();
            
            if (callback && typeof callback === 'function') {
                callback(data);
            }
        } catch (error) {
            NotificationController.display("Erreur technique", "error");
        }
    }
    
    getQuantity(element) {
        // Si c'est un input, prendre sa valeur
        if (element.tagName === "INPUT") {
            return parseInt(element.value) || 1;
        }
        
        // Sinon chercher dans les targets
        const quantityTarget = this.hasQuantityTarget ? this.quantityTarget : null;
        return quantityTarget ? (parseInt(quantityTarget.value) || 1) : 1;
    }
    
    updateItemUI(target, data) {
        if (!data.cart?.updatedItem) return;
        
        // Mettre à jour la quantité si c'est un input
        if (this.hasQuantityTarget) {
            this.quantityTarget.value = data.cart.updatedItem.quantity;
        }
        
        // Mettre à jour le total de ligne
        const totalElement = this.hasTotalTarget 
            ? this.totalTarget 
            : target.closest("article")?.querySelector('[data-cart-target="total"]');
            
        if (totalElement) {
            const formattedPrice = Number(data.cart.updatedItem.total_price)
                .toFixed(2)
                .replace(".", ",");
            totalElement.textContent = `Total : ${formattedPrice}€`;
        }
    }
    
    updateCartTotal(total) {
        if (!total) return;
        
        const formattedTotal = Number(total).toFixed(2).replace(".", ",");
        document.querySelectorAll("[data-cart-total]").forEach((el) => {
            el.textContent = `${formattedTotal}€`;
        });
    }
}
