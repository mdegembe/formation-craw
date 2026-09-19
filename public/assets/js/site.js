/**
 * Formation CRA-W — améliorations progressives.
 *
 * Tout le site fonctionne sans JavaScript : ce fichier ne fait qu'améliorer
 * le confort d'usage. Aucune dépendance, aucun appel réseau externe.
 */
(function () {
    'use strict';

    /* ------------------------------------------------------------ Thème
     * Clair / sombre / auto, mémorisé côté navigateur uniquement (pas de
     * cookie de suivi, donc pas de bandeau de consentement).
     */
    var STORAGE_KEY = 'craw-theme';

    function systemPrefersDark() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function storedTheme() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (error) {
            return null;
        }
    }

    function applyTheme(theme) {
        var resolved = theme === 'auto' || !theme ? (systemPrefersDark() ? 'dark' : 'light') : theme;
        document.documentElement.setAttribute('data-theme', resolved);

        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute(
                'aria-label',
                resolved === 'dark' ? 'Activer le thème clair' : 'Activer le thème sombre'
            );
            button.setAttribute('aria-pressed', resolved === 'dark' ? 'true' : 'false');
        });
    }

    applyTheme(storedTheme() || 'auto');

    if (window.matchMedia) {
        var query = window.matchMedia('(prefers-color-scheme: dark)');
        var onChange = function () {
            if (!storedTheme()) {
                applyTheme('auto');
            }
        };

        if (query.addEventListener) {
            query.addEventListener('change', onChange);
        } else if (query.addListener) {
            query.addListener(onChange);
        }
    }

    document.addEventListener('click', function (event) {
        var toggle = event.target.closest('[data-theme-toggle]');
        if (!toggle) {
            return;
        }

        var next = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';

        try {
            localStorage.setItem(STORAGE_KEY, next);
        } catch (error) {
            /* Navigation privée ou stockage bloqué : le thème reste valable
               pour la page courante. */
        }

        applyTheme(next);
    });

    /* ------------------------------------------------- Menu de navigation */
    var navToggle = document.querySelector('[data-nav-toggle]');
    var primaryNav = document.getElementById('primary-navigation');

    if (navToggle && primaryNav) {
        navToggle.addEventListener('click', function () {
            var isOpen = primaryNav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && primaryNav.classList.contains('is-open')) {
                primaryNav.classList.remove('is-open');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.focus();
            }
        });
    }

    /* --------------------------------------------------- Copie d'un champ
     * Utilisé sur la page de paiement : IBAN, montant, communication.
     */
    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-copy]');
        if (!button) {
            return;
        }

        var value = button.getAttribute('data-copy') || '';
        var original = button.querySelector('[data-copy-label]');

        var confirm = function () {
            button.setAttribute('data-copied', 'true');
            if (original) {
                original.textContent = 'Copié';
            }

            window.setTimeout(function () {
                button.removeAttribute('data-copied');
                if (original) {
                    original.textContent = 'Copier';
                }
            }, 2000);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(value).then(confirm, function () {
                fallbackCopy(value, confirm);
            });
        } else {
            fallbackCopy(value, confirm);
        }
    });

    function fallbackCopy(value, done) {
        var field = document.createElement('textarea');
        field.value = value;
        field.setAttribute('readonly', 'readonly');
        field.style.position = 'absolute';
        field.style.left = '-9999px';
        document.body.appendChild(field);
        field.select();

        try {
            document.execCommand('copy');
            done();
        } catch (error) {
            /* Rien à faire : la valeur reste sélectionnable manuellement. */
        }

        document.body.removeChild(field);
    }

    /* ----------------------------------- Champs conditionnels (formulaire)
     * Un commentaire n'est demandé que lorsqu'il est pertinent.
     */
    function bindConditional(trigger, targetId, shouldShow) {
        var target = document.getElementById(targetId);
        if (!target) {
            return;
        }

        var update = function () {
            var visible = shouldShow();
            target.hidden = !visible;

            target.querySelectorAll('input, textarea, select').forEach(function (field) {
                if (field.dataset.conditionalRequired === 'true') {
                    field.required = visible;
                }
            });
        };

        trigger.forEach(function (element) {
            element.addEventListener('change', update);
        });

        update();
    }

    var dietInputs = Array.prototype.slice.call(document.querySelectorAll('input[name="diet"]'));
    if (dietInputs.length) {
        bindConditional(dietInputs, 'diet-comment-block', function () {
            var checked = document.querySelector('input[name="diet"]:checked');
            return !!checked && ['halal', 'casher', 'autre', 'vegan'].indexOf(checked.value) !== -1;
        });
    }

    var allergyToggle = document.getElementById('has_allergies');
    if (allergyToggle) {
        bindConditional([allergyToggle], 'allergies-block', function () {
            return allergyToggle.checked;
        });
    }

    var invoiceToggle = document.getElementById('needs_invoice');
    if (invoiceToggle) {
        bindConditional([invoiceToggle], 'invoice-block', function () {
            return invoiceToggle.checked;
        });
    }

    /* ------------------------------------------- Grille de présences
     * On pointe les absences : toutes les cases sont cochées par défaut.
     * Ces boutons ne font que manipuler les cases ; l'enregistrement reste
     * assuré par la soumission du formulaire.
     */
    document.querySelectorAll('[data-mark-all]').forEach(function (button) {
        button.addEventListener('click', function () {
            var scope = button.getAttribute('data-mark-all');
            var checked = button.getAttribute('data-state') !== 'absent';

            document
                .querySelectorAll('input.presence-box[data-slot="' + scope + '"]')
                .forEach(function (box) {
                    box.checked = checked;
                });

            button.setAttribute('data-state', checked ? 'present' : 'absent');
            announce(
                checked
                    ? 'Tous les participants sont marqués présents sur ce créneau.'
                    : 'Tous les participants sont marqués absents sur ce créneau.'
            );
        });
    });

    var announcer = document.getElementById('live-announcer');

    function announce(message) {
        if (announcer) {
            announcer.textContent = message;
        }
    }

    /* ------------------------------------- Confirmation d'action sensible */
    document.addEventListener('submit', function (event) {
        var form = event.target;
        var message = form.getAttribute('data-confirm');

        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });

    /* ------------------------------ Compteur de caractères sur textarea */
    document.querySelectorAll('textarea[maxlength]').forEach(function (field) {
        var max = parseInt(field.getAttribute('maxlength'), 10);
        var counter = document.createElement('p');
        counter.className = 'form-hint text-end';
        counter.setAttribute('aria-live', 'polite');

        var update = function () {
            counter.textContent = field.value.length + ' / ' + max + ' caractères';
        };

        field.parentNode.appendChild(counter);
        field.addEventListener('input', update);
        update();
    });

    /* ------------------- Évite un double envoi sur les formulaires longs */
    document.querySelectorAll('form[data-once]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var submit = form.querySelector('[type="submit"]');
            if (submit && !submit.disabled) {
                window.setTimeout(function () {
                    submit.disabled = true;
                    submit.setAttribute('aria-busy', 'true');
                }, 0);
            }
        });
    });
})();
