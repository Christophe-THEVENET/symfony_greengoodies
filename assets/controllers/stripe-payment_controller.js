import { Controller } from "@hotwired/stimulus";

// Paiement embarqué via Stripe Payment Element.
// 1. récupère un client_secret auprès du serveur (montant calculé serveur)
// 2. monte le Payment Element
// 3. confirme le paiement -> Stripe redirige vers return_url (page confirmation)
export default class extends Controller {
    static targets = ["payment", "error", "submit"];
    static values = {
        publicKey: String,
        intentUrl: String,
        returnUrl: String,
        csrfToken: String,
    };

    async connect() {
        // Page restaurée depuis le bfcache (retour après une méthode non
        // finalisée, ex. PayPal/Klarna) : on recharge pour réinitialiser le
        // Payment Element et refaire le fetch du client_secret.
        this._onPageShow = (event) => {
            if (event.persisted) window.location.reload();
        };
        window.addEventListener("pageshow", this._onPageShow);

        // On attend que Stripe.js soit réellement chargé (course possible : le
        // contrôleur peut se connecter avant la fin du chargement du script,
        // notamment lors d'une navigation où js.stripe.com n'est pas en cache).
        let StripeCtor;
        try {
            StripeCtor = await this._loadStripe();
        } catch (e) {
            this._error("Stripe.js n'a pas pu être chargé.");
            this._disable();
            return;
        }

        this.stripe = StripeCtor(this.publicKeyValue);

        // Récupère le client_secret du PaymentIntent
        let clientSecret;
        try {
            const res = await fetch(this.intentUrlValue, {
                method: "POST",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-Token": this.csrfTokenValue,
                },
            });
            const data = await res.json();
            if (!res.ok) {
                this._error(data.error || "Erreur lors de l'initialisation du paiement.");
                this._disable();
                return;
            }
            clientSecret = data.clientSecret;
        } catch (e) {
            this._error("Erreur réseau lors de l'initialisation du paiement.");
            this._disable();
            return;
        }

        // Aligne le Payment Element (iframe Stripe) sur les champs du site :
        // fond crème, pas de bordure, coins arrondis, police Hanken Grotesk.
        // (valeurs = design tokens "Terre Vive" de _variables.scss)
        this.elements = this.stripe.elements({
            clientSecret,
            appearance: {
                theme: "flat",
                variables: {
                    fontFamily: '"Hanken Grotesk", sans-serif',
                    colorPrimary: "#A2594F",      // $terra
                    colorText: "#3A2D29",         // $ink
                    colorTextSecondary: "#6E5A52", // $ink-soft
                    colorDanger: "#894639",       // $terra-deep
                    colorBackground: "#EDE3DA",   // $cream (fond des champs)
                    borderRadius: "12px",         // $radius-md
                    fontSizeBase: "14px",         // $fs-body-sm
                    spacingUnit: "4px",
                },
                rules: {
                    ".Input": {
                        backgroundColor: "#EDE3DA",
                        border: "none",
                        boxShadow: "none",
                        padding: "11px 14px",
                    },
                    ".Input:focus": {
                        backgroundColor: "rgba(58, 45, 41, 0.05)",
                        boxShadow: "none",
                    },
                    ".Label": {
                        fontWeight: "600",
                        color: "#3A2D29",
                        marginBottom: "6px",
                    },
                },
            },
        });
        this.elements
            .create("payment", {
                // Formulaire carte minimal : pas de wallets ni de bloc facultatif
                wallets: { applePay: "never", googlePay: "never" },
            })
            .mount(this.paymentTarget);
    }

    async submit(event) {
        event.preventDefault();
        if (!this.elements) return;

        this._disable(true);
        this._error("");

        const { error } = await this.stripe.confirmPayment({
            elements: this.elements,
            confirmParams: { return_url: this.returnUrlValue },
        });

        // En cas de succès, Stripe redirige vers return_url : on n'arrive ici
        // que si une erreur immédiate survient (carte refusée, validation…).
        if (error) {
            this._error(error.message);
            this._disable(false);
        }
    }

    disconnect() {
        if (this._onPageShow) {
            window.removeEventListener("pageshow", this._onPageShow);
        }
    }

    // Charge Stripe.js de façon fiable : résout dès que `window.Stripe` est
    // disponible, en attendant le <script> existant ou en l'injectant au besoin.
    _loadStripe() {
        return new Promise((resolve, reject) => {
            if (window.Stripe) {
                resolve(window.Stripe);
                return;
            }

            let script = document.querySelector('script[src^="https://js.stripe.com/v3"]');
            if (!script) {
                script = document.createElement("script");
                script.src = "https://js.stripe.com/v3/";
                document.head.appendChild(script);
            }

            script.addEventListener("load", () =>
                window.Stripe ? resolve(window.Stripe) : reject(new Error("Stripe absent"))
            );
            script.addEventListener("error", () => reject(new Error("échec de chargement")));

            // Filet de sécurité si le script était déjà en cours de chargement
            setTimeout(() => {
                window.Stripe ? resolve(window.Stripe) : reject(new Error("timeout"));
            }, 10000);
        });
    }

    _disable(state = true) {
        if (this.hasSubmitTarget) this.submitTarget.disabled = state;
    }

    _error(message) {
        if (this.hasErrorTarget) this.errorTarget.textContent = message;
    }
}
