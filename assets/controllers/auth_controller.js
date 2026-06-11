import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["tab", "loginPanel", "registerPanel"];
    static values = {
        activeTab: { type: String, default: "login" },
        loginUrl: String,
        registerUrl: String,
    };

    connect() {
        if (this.activeTabValue === "register") {
            this.showRegister();
        }
    }

    showLogin() {
        this.loginPanelTarget.hidden = false;
        if (this.hasRegisterPanelTarget) this.registerPanelTarget.hidden = true;
        this.tabTargets.forEach((t) => t.classList.remove("is-active"));
        this.tabTargets[0]?.classList.add("is-active");
        this.updateUrl(this.loginUrlValue);
        this.activeTabValue = "login";
    }

    showRegister() {
        this.loginPanelTarget.hidden = true;
        if (this.hasRegisterPanelTarget) this.registerPanelTarget.hidden = false;
        this.tabTargets.forEach((t) => t.classList.remove("is-active"));
        this.tabTargets[1]?.classList.add("is-active");
        this.updateUrl(this.registerUrlValue);
        this.activeTabValue = "register";
    }

    // Met à jour l'URL (sans rechargement) vers la route propre, sans hashtag ni query
    updateUrl(url) {
        if (url) history.replaceState(null, "", url);
    }
}
