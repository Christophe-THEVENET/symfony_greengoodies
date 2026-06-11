import { Controller } from "@hotwired/stimulus";

// Validation live du formulaire de contact, sur le même principe que
// login_controller (inscription) : bordures valide/invalide en temps réel
// et bouton d'envoi désactivé tant que tout n'est pas valide.
export default class extends Controller {
    static targets = ["name", "email", "subject", "message", "submitButton"];

    connect() {
        this.validateForm();
    }

    checkForm() {
        if (this.timeout) clearTimeout(this.timeout);
        this.timeout = setTimeout(() => this.validateForm(), 300);
    }

    validateForm() {
        const checks = [
            [this.nameTarget, this.validateText(this.nameTarget.value, 2)],
            [this.emailTarget, this.validateEmail(this.emailTarget.value)],
            [this.subjectTarget, this.validateText(this.subjectTarget.value, 2)],
            [this.messageTarget, this.validateText(this.messageTarget.value, 10)],
        ];

        let isValid = true;
        checks.forEach(([field, ok]) => {
            this.updateField(field, ok);
            isValid = isValid && ok;
        });

        this.submitButtonTarget.disabled = !isValid;
    }

    validateEmail(value) {
        const email = value.trim();
        return email.length > 0 && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    validateText(value, min) {
        return value.trim().length >= min;
    }

    updateField(field, isValid) {
        field.classList.remove("valid", "invalid");
        if (field.value.trim().length > 0) {
            field.classList.add(isValid ? "valid" : "invalid");
        }
    }

    disconnect() {
        if (this.timeout) clearTimeout(this.timeout);
    }
}
