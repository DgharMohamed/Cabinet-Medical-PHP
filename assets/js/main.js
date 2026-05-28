// Initialisation principale de l'interface utilisateur.
// Déclenchée une fois que le modèle objet du document (DOM) est entièrement chargé.
document.addEventListener('DOMContentLoaded', () => {
    const language = document.documentElement.getAttribute('lang') || 'fr';

    initHeaderScroll();
    initMobileMenu();
    initScrollAnimations();
    initFloatingLabels();

    const appointmentForm = document.getElementById('appointmentForm');
    if (appointmentForm) {
        initAppointmentForm(appointmentForm, language);
    }
});

// Gère les effets liés au défilement (scroll) de la page :
// - Changement d'apparence de l'en-tête (Header).
// - Mise à jour de la barre de progression de lecture.
// - Affichage/Masquage du bouton "Retour en haut".
function initHeaderScroll() {
    const header = document.getElementById('header');
    const scrollProgress = document.getElementById('scrollProgress');
    const backToTop = document.getElementById('backToTop');

    if (!header && !scrollProgress && !backToTop) return;

    let animationFramePending = false;

    window.addEventListener('scroll', () => {
        // Éviter de surcharger le navigateur en vérifiant si une frame est déjà en attente
        if (!animationFramePending) {
            window.requestAnimationFrame(() => {
                const currentScrollPosition = window.scrollY;

                // Gérer l'apparence de l'en-tête (ajout d'une ombre/fond après 60px)
                if (header) {
                    if (currentScrollPosition > 60) {
                        header.classList.add('scrolled');
                    } else {
                        header.classList.remove('scrolled');
                    }
                }

                // Calculer et mettre à jour la barre de progression de défilement
                if (scrollProgress) {
                    const documentScrollableHeight = document.documentElement.scrollHeight - window.innerHeight;
                    const scrollProgressPercent = documentScrollableHeight > 0 ? (currentScrollPosition / documentScrollableHeight) * 100 : 0;
                    scrollProgress.style.width = `${scrollProgressPercent}%`;
                }

                // Afficher le bouton "Retour en haut" seulement après 500px de défilement
                if (backToTop) {
                    if (currentScrollPosition > 500) {
                        backToTop.classList.add('visible');
                    } else {
                        backToTop.classList.remove('visible');
                    }
                }

                animationFramePending = false;
            });
            animationFramePending = true;
        }
    });

    if (backToTop) {
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
}

// Initialise le comportement du menu de navigation sur mobile (Menu Hamburger).
// Gère l'ouverture, la fermeture, et le blocage du défilement du corps de la page.
function initMobileMenu() {
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobileMenu');

    if (!hamburger || !mobileMenu) return;

    const closeMobileMenu = () => {
        hamburger.classList.remove('active');
        mobileMenu.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('no-scroll');
    };

    // Basculer l'état du menu lors du clic sur le bouton hamburger
    hamburger.addEventListener('click', () => {
        const isOpen = mobileMenu.classList.toggle('open');
        hamburger.classList.toggle('active');
        hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        // Empêcher le défilement de l'arrière-plan quand le menu est ouvert
        document.body.classList.toggle('no-scroll', isOpen);
    });

    // Fermer automatiquement le menu si un lien est cliqué
    const mobileLinks = mobileMenu.querySelectorAll('.mobile-link, .btn');
    mobileLinks.forEach(link => {
        link.addEventListener('click', closeMobileMenu);
    });
}

// Configure l'observateur d'intersection (Intersection Observer) pour déclencher
// des animations CSS (révélations) lorsque les éléments deviennent visibles à l'écran.
function initScrollAnimations() {
    const intersectionObserver = new IntersectionObserver((observerEntries) => {
        observerEntries.forEach(observerEntry => {
            if (observerEntry.isIntersecting) {
                observerEntry.target.classList.add('in');

                const counterElement = observerEntry.target.querySelector('[data-count]');
                if (counterElement && counterElement.textContent === '0') {
                    animateCounter(counterElement);
                }
                intersectionObserver.unobserve(observerEntry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -60px 0px'
    });

    const revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    revealElements.forEach(element => {
        intersectionObserver.observe(element);
    });
}

// Anime un compteur numérique de 0 jusqu'à sa valeur cible.
// Utilisé pour les statistiques animées sur la page d'accueil.
// @param {HTMLElement} element - L'élément HTML contenant l'attribut `data-count`.
function animateCounter(element) {
    const rawCountValue = element.getAttribute('data-count');
    const targetNumber = parseInt(rawCountValue, 10);
    if (isNaN(targetNumber)) {
        element.textContent = rawCountValue;
        return;
    }

    const countSuffix = rawCountValue.replace(/^[0-9]+/, '');
    let currentCount = 0;
    const countIncrement = targetNumber / 35;

    const countInterval = setInterval(() => {
        currentCount += countIncrement;
        if (currentCount >= targetNumber) {
            currentCount = targetNumber;
            clearInterval(countInterval);
        }
        element.textContent = Math.floor(currentCount) + countSuffix;
    }, 30);
}

// Gère l'effet visuel des étiquettes flottantes (floating labels) dans les formulaires.
// Ajoute ou supprime une classe selon que le champ select a une valeur ou non.
function initFloatingLabels() {
    const selectElements = document.querySelectorAll('select.form-input');
    selectElements.forEach(select => {
        if (select.value !== '') {
            select.classList.add('has-value');
        }

        select.addEventListener('change', () => {
            if (select.value !== '') {
                select.classList.add('has-value');
            } else {
                select.classList.remove('has-value');
            }
        });
    });
}

// Initialise la logique du formulaire de prise de rendez-vous côté patient :
// - Validation côté client (téléphone, date).
// - Chargement AJAX des créneaux horaires disponibles en fonction de la date.
// @param {HTMLFormElement} form - L'élément formulaire de rendez-vous.
// @param {string} lang - La langue actuelle ('ar' ou 'fr') pour les messages d'alerte.
function initAppointmentForm(form, lang) {
    const phoneInput = form.querySelector('[name="phone"]');
    const dateInput = document.getElementById('appointmentDate');
    const slotsContainer = document.getElementById('time-slots-container');

    form.addEventListener('submit', (event) => {
        const phone = phoneInput.value.trim();
        const date = dateInput.value;

        // Nettoyer le numéro de téléphone (retirer espaces et tirets) pour la validation
        const cleanedPhoneNumber = phone.replace(/[\s\-\+]/g, '');

        // Vérifier que le numéro contient au moins 10 chiffres valides
        if (cleanedPhoneNumber.length < 10 || !/^\d+$/.test(cleanedPhoneNumber)) {
            event.preventDefault();
            alert(lang === 'ar'
                ? 'رقم الهاتف غير صالح. يجب أن يحتوي على 10 أرقام على الأقل.'
                : 'Numéro de téléphone invalide. Il doit contenir au moins 10 chiffres.'
            );
            phoneInput.focus();
            return;
        }

        if (date) {
            const today = new Date().toISOString().split('T')[0];
            if (date < today) {
                event.preventDefault();
                alert(lang === 'ar'
                    ? 'لا يمكنك اختيار تاريخ في الماضي.'
                    : 'Vous ne pouvez pas choisir une date passée.'
                );
                dateInput.focus();
                return;
            }
        }

        const selectedSlotInput = form.querySelector('[name="time_slot_id"]:checked');
        if (!selectedSlotInput) {
            event.preventDefault();
            alert(lang === 'ar'
                ? 'الرجاء اختيار وقت الموعد.'
                : 'Veuillez choisir un créneau horaire.'
            );
            return;
        }
    });

    if (dateInput && slotsContainer) {
        dateInput.addEventListener('change', () => {
            const date = dateInput.value;
            if (!date) return;

            // Afficher un message de chargement temporaire
            slotsContainer.innerHTML = '<p style="color:#999;font-size:13px;padding:8px 0;">' +
                (lang === 'ar' ? 'جاري تحميل المواعيد المتاحة...' : 'Chargement des créneaux...') +
                '</p>';

            // Lancer la requête AJAX pour récupérer les créneaux disponibles
            const ajaxRequest = new XMLHttpRequest();
            ajaxRequest.open('GET', `traitement/get-slots.php?date=${encodeURIComponent(date)}`, true);
            ajaxRequest.onload = () => {
                if (ajaxRequest.status === 200) {
                    try {
                        const responseData = JSON.parse(ajaxRequest.responseText);

                        if (responseData.error) {
                            const errorMessage = lang === 'ar' ? responseData.message.ar : responseData.message.fr;
                            slotsContainer.innerHTML = `<p style="color:#dc2626;font-size:13px;padding:8px 0;">${errorMessage}</p>`;
                        } else if (responseData.slots && responseData.slots.length > 0) {
                            let slotsHtml = '';
                            responseData.slots.forEach(slotData => {
                                const availableSpots = parseInt(slotData.max_patients, 10) - parseInt(slotData.current_bookings, 10);
                                const isDisabled = availableSpots <= 0 ? 'disabled' : '';
                                const slotBadgeClass = availableSpots > 0 ? 'slot-available' : 'slot-full';
                                const availabilityLabel = lang === 'ar' ? 'متبقي' : 'disponible';

                                slotsHtml += `<label class="time-slot-card ${slotBadgeClass}" ${isDisabled ? 'style="opacity:0.5;cursor:not-allowed;"' : ''}>`;
                                slotsHtml += `<input type="radio" name="time_slot_id" value="${slotData.id}" ${isDisabled}>`;
                                slotsHtml += `<span class="slot-time">${slotData.start_time.substring(0, 5)} - ${slotData.end_time.substring(0, 5)}</span>`;
                                slotsHtml += `<span class="slot-badge">${availableSpots}/${slotData.max_patients} ${availabilityLabel}</span>`;
                                slotsHtml += `</label>`;
                            });
                            slotsContainer.innerHTML = slotsHtml;
                        } else {
                            slotsContainer.innerHTML = '<p style="color:#dc2626;font-size:13px;padding:8px 0;">' +
                                (lang === 'ar' ? 'لا توجد مواعيد متاحة لهذا اليوم' : 'Aucun créneau disponible pour ce jour') +
                                '</p>';
                        }
                    } catch (parseError) {
                        console.error('Error parsing JSON response', parseError);
                        slotsContainer.innerHTML = `<p style="color:#dc2626;font-size:13px;padding:8px 0;">${lang === 'ar' ? 'حدث خطأ في تحميل البيانات' : 'Erreur de lecture des données'}</p>`;
                    }
                } else {
                    slotsContainer.innerHTML = '<p style="color:#dc2626;font-size:13px;padding:8px 0;">' +
                        (lang === 'ar' ? 'حدث خطأ في تحميل المواعيد' : 'Erreur de chargement des créneaux') +
                        '</p>';
                }
            };
            ajaxRequest.onerror = () => {
                slotsContainer.innerHTML = '<p style="color:#dc2626;font-size:13px;padding:8px 0;">' +
                    (lang === 'ar' ? 'حدث خطأ في تحميل المواعيد' : 'Erreur de chargement des créneaux') +
                    '</p>';
            };
            ajaxRequest.send();
        });
    }
}
