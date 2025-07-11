// assets/controllers/login_controller.js
import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = [
        "email",
        "password",
        "passwordConfirm",
        "firstname",
        "lastname",
        "submitButton",
    ];
    static values = {
        formType: { type: String, default: "login" }, // "login" ou "register"
    };

    connect() {
        this.checkForm();
    }

    checkForm() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }

        this.timeout = setTimeout(() => {
            this.validateForm();
        }, 300);
    }

    validateForm() {
        const email = this.emailTarget.value.trim();
        const password = this.passwordTarget.value;

        let isValid = true;

        // Validation email
        const isEmailValid = this.validateEmail(email);
        this.updateField(this.emailTarget, isEmailValid);
        isValid = isValid && isEmailValid;

        // Validation mot de passe
        const isPasswordValid = this.validatePassword(password);
        this.updateField(this.passwordTarget, isPasswordValid);
        isValid = isValid && isPasswordValid;

        // Validations spécifiques à l'inscription
        if (this.formTypeValue === "register") {
            // Validation prénom
            if (this.hasFirstnameTarget) {
                const isFirstnameValid = this.validateName(
                    this.firstnameTarget.value.trim()
                );
                this.updateField(this.firstnameTarget, isFirstnameValid);
                isValid = isValid && isFirstnameValid;
            }

            // Validation nom
            if (this.hasLastnameTarget) {
                const isLastnameValid = this.validateName(
                    this.lastnameTarget.value.trim()
                );
                this.updateField(this.lastnameTarget, isLastnameValid);
                isValid = isValid && isLastnameValid;
            }

            // ✅ Validation confirmation mot de passe
            if (this.hasPasswordConfirmTarget) {
                const passwordConfirm = this.passwordConfirmTarget.value;
                const isPasswordConfirmValid = this.validatePasswordConfirm(
                    password,
                    passwordConfirm
                );
                this.updateField(
                    this.passwordConfirmTarget,
                    isPasswordConfirmValid
                );
                isValid = isValid && isPasswordConfirmValid;
            }
        }

        this.submitButtonTarget.disabled = !isValid;
    }

    validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return email.length > 0 && emailRegex.test(email);
    }

    validatePassword(password) {
        // CONNEXION : Validation simple
        if (this.formTypeValue === "login") {
            return password.length > 0;
        }

        // INSCRIPTION : Validation complexe selon regex back-end
        const fullRegex =
            /^(?=.*[0-9])(?=.*[a-z])(?=.*[A-Z])(?=.*\W)(?!.*\s).{6,32}$/;
        return fullRegex.test(password);
    }

    validateName(name) {
        // Au moins 2 caractères
        return name.length >= 2 && name.length <= 255;
    }

    validatePasswordConfirm(password, passwordConfirm) {
        // ✅ Validation améliorée pour la confirmation
        // Le champ doit être rempli ET identique au mot de passe
        return (
            passwordConfirm.length > 0 &&
            password === passwordConfirm &&
            password.length > 0
        );
    }
  // style des inputs en live
    updateField(field, isValid) {
        const value = field.value.trim();
        // Retire les classes de validation précédentes (Nettoie)
        field.classList.remove("valid", "invalid");

        if (value.length > 0) {
            if (isValid) {
                field.classList.add("valid");
            } else {
                field.classList.add("invalid");
            }
        }
    }

    onSubmit(event) {
        if (!this.submitButtonTarget.disabled) {
            this.submitButtonTarget.disabled = true;
            const buttonText =
                this.formTypeValue === "login"
                    ? "Connexion..."
                    : "Inscription...";
            this.submitButtonTarget.innerHTML = `<span class="spinner"></span> ${buttonText}`;

            setTimeout(() => {
                this.submitButtonTarget.disabled = false;
                const originalText =
                    this.formTypeValue === "login" ? "Connexion" : "S'inscrire";
                this.submitButtonTarget.innerHTML = `<span class="link">${originalText}</span>`;
            }, 3000);
        }
    }

    disconnect() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }
    }
}
