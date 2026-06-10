import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["tab", "loginPanel", "registerPanel"];
    static values = { activeTab: { type: String, default: "login" } };

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
        history.replaceState(null, "", window.location.pathname + "?tab=login");
        this.activeTabValue = "login";
    }

    showRegister() {
        this.loginPanelTarget.hidden = true;
        if (this.hasRegisterPanelTarget) this.registerPanelTarget.hidden = false;
        this.tabTargets.forEach((t) => t.classList.remove("is-active"));
        this.tabTargets[1]?.classList.add("is-active");
        history.replaceState(null, "", window.location.pathname + "?tab=register");
        this.activeTabValue = "register";
    }
}
