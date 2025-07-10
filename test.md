{% extends 'base.html.twig' %}

{% block title %}{{parent()}} - Inscription{% endblock %}

{% block body %}
    <section class="login">
        <div class="login__container">
            <div class="login__form">
                <div class="login__wrapper">
                    <form method="post" 
                          data-controller="login"
                          data-login-form-type-value="register"
                          data-action="submit->login#onSubmit">
                        
                        <h1 class="product__title">Inscription</h1>
                        
                        <div class="login__input__block">
                            <label class="product__text" for="firstname">Prénom</label>
                            <input type="text" 
                                   name="firstname" 
                                   id="firstname" 
                                   class="form-control" 
                                   required>
                        </div>
                        
                        <div class="login__input__block">
                            <label class="product__text" for="lastname">Nom</label>
                            <input type="text" 
                                   name="lastname" 
                                   id="lastname" 
                                   class="form-control" 
                                   required>
                        </div>
                        
                        <div class="login__input__block">
                            <label class="product__text" for="email">Email</label>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   class="form-control" 
                                   required
                                   data-login-target="email"
                                   data-action="input->login#checkForm">
                        </div>
                        
                        <div class="login__input__block">
                            <label class="product__text" for="password">Mot de passe</label>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control" 
                                   required
                                   data-login-target="password"
                                   data-action="input->login#checkForm">
                            <div class="password-requirements">
                                <small>Le mot de passe doit contenir :</small>
                                <ul>
                                    <li>Au moins 6 caractères</li>
                                    <li>Une majuscule</li>
                                    <li>Une minuscule</li>
                                    <li>Un chiffre</li>
                                    <li>Un caractère spécial</li>
                                </ul>
                            </div>
                        </div>

                        <button class="btn btn-secondary" 
                                type="submit"
                                data-login-target="submitButton"
                                disabled>
                            <span class="link">S'inscrire</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
{% endblock %}