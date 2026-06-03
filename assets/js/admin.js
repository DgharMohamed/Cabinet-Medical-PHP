// Initialisation des événements une fois que le DOM est chargé.
// Configure les onglets de service, le planning, et les créneaux horaires.
document.addEventListener('DOMContentLoaded', () => {
    const serviceTabContainer = document.querySelector('.service-tabs');
    if (serviceTabContainer) {
        initDashboardTabs();
    }

    const scheduleDaySelect = document.getElementById('day_of_week');
    if (scheduleDaySelect) {
        initScheduleTabsAndSlots();
    }

    const createAppointmentDateInput = document.getElementById('appointment_date');
    if (createAppointmentDateInput) {
        initCreateAppointmentSlots();
    }
});

// Initialise les onglets du tableau de bord (Dashboard).
// Restaure le dernier onglet actif à partir du localStorage s'il existe.
function initDashboardTabs() {
    const storedActiveTab = localStorage.getItem('activeServiceTab');
    if (storedActiveTab) {
        const matchingTab = document.querySelector(`.service-tabs .tab-btn[data-service="${storedActiveTab.replace(/"/g, '\\"')}"]`);
        if (matchingTab) {
            switchServiceTab(storedActiveTab);
        } else {
            switchServiceTab('all');
        }
    } else {
        switchServiceTab('all');
    }
}

// Bascule l'affichage vers un onglet de service spécifique.
// Met à jour le localStorage pour mémoriser le choix de l'utilisateur.
// @param {string} targetServiceName - Le nom du service (ex: 'Cardiologie') ou 'all' pour tout afficher.
function switchServiceTab(targetServiceName) {
    const tabButtons = document.querySelectorAll('.service-tabs .tab-btn');
    tabButtons.forEach(btn => btn.classList.remove('active'));

    // Activer le bouton de l'onglet cliqué
    const activeTabButton = document.querySelector(`.service-tabs .tab-btn[data-service="${targetServiceName.replace(/"/g, '\\"')}"]`);
    if (activeTabButton) {
        activeTabButton.classList.add('active');
    }

    // Afficher ou masquer les cartes de service selon l'onglet choisi
    const serviceCards = document.querySelectorAll('.service-card');
    serviceCards.forEach(card => {
        if (targetServiceName === 'all' || card.getAttribute('data-service') === targetServiceName) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });

    localStorage.setItem('activeServiceTab', targetServiceName);
}

// Initialise les événements liés aux onglets des jours (Planning) et aux créneaux horaires.
function initScheduleTabsAndSlots() {
    const dayOfWeekSelect = document.getElementById('day_of_week');
    const timeSlotDropdown = document.getElementById('time_slot');

    if (dayOfWeekSelect && timeSlotDropdown) {
        dayOfWeekSelect.addEventListener('change', () => {
            updateTimeSlotsDropdown(dayOfWeekSelect.value, timeSlotDropdown);
        });
        updateTimeSlotsDropdown(dayOfWeekSelect.value, timeSlotDropdown);
    }

    const activeDay = localStorage.getItem('selected_schedule_day') || 'Monday';
    switchDayTab(activeDay);
}

// Met à jour les options de la liste déroulante des créneaux horaires 
// en fonction du jour de la semaine sélectionné.
// @param {string} selectedDay - Le jour sélectionné (ex: 'Saturday').
// @param {HTMLElement} dropdownElement - L'élément select contenant les créneaux.
function updateTimeSlotsDropdown(selectedDay, dropdownElement) {
    if (!dropdownElement) return;
    dropdownElement.innerHTML = '';

    const timeSlotOptions = (selectedDay === 'Saturday')
        ? ['09:00 - 11:00', '11:00 - 13:00']
        : ['09:00 - 11:00', '11:00 - 13:00', '14:00 - 16:00', '16:00 - 18:00'];

    timeSlotOptions.forEach(optionText => {
        const optionElement = document.createElement('option');
        optionElement.value = optionText.replace(/\s/g, '');
        optionElement.textContent = optionText;
        dropdownElement.appendChild(optionElement);
    });
}

// Alterne l'affichage des créneaux dans la vue emploi du temps selon le jour cliqué.
// @param {string} selectedDay - Le jour de la semaine (ex: 'Monday').
function switchDayTab(selectedDay) {
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
        if (btn.getAttribute('data-day') === selectedDay) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    localStorage.setItem('selected_schedule_day', selectedDay);

    const dayOfWeekSelect = document.getElementById('day_of_week');
    if (dayOfWeekSelect) {
        dayOfWeekSelect.value = selectedDay;
        const changeEvent = new Event('change');
        dayOfWeekSelect.dispatchEvent(changeEvent);
    }

    const scheduleRows = document.querySelectorAll('.slot-row');
    let visibleRowCount = 0;
    scheduleRows.forEach(row => {
        if (row.getAttribute('data-day-of-week') === selectedDay) {
            row.style.display = 'table-row';
            visibleRowCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const emptyDayRow = document.getElementById('empty-day-row');
    if (emptyDayRow) {
        if (visibleRowCount === 0) {
            emptyDayRow.style.display = 'table-row';
        } else {
            emptyDayRow.style.display = 'none';
        }
    }
}

// Met à jour la capacité maximale de patients pour un créneau donné via une requête AJAX.
// Affiche une animation visuelle (surbrillance) en cas de succès.
// @param {number|string} currentSlotId - L'ID unique du créneau.
// @param {number|string} newMaxPatients - La nouvelle capacité maximale de patients.
function updateMaxPatients(currentSlotId, newMaxPatients) {
    // Récupérer le jeton CSRF pour sécuriser la requête
    const csrfTokenField = document.querySelector('input[name="csrf_token"]');
    const csrfTokenValue = csrfTokenField ? csrfTokenField.value : '';

    // Préparer les données du formulaire pour la requête AJAX
    const requestFormData = new FormData();
    requestFormData.append('slot_id', currentSlotId);
    requestFormData.append('max_patients', newMaxPatients);
    requestFormData.append('csrf_token', csrfTokenValue);

    // Envoyer la requête asynchrone au serveur
    fetch('update-max-patients.php', {
        method: 'POST',
        body: requestFormData
    })
    .then(response => response.json())
    .then(responseData => {
        if (responseData.success) {
            const maxPatientsSelect = document.getElementById('max-patients-' + currentSlotId);
            if (maxPatientsSelect) {
                const originalBackgroundColor = maxPatientsSelect.style.background;
                maxPatientsSelect.style.background = '#dcfce7';
                setTimeout(() => {
                    maxPatientsSelect.style.background = originalBackgroundColor;
                }, 500);
            }
        } else {
            alert('Error: ' + responseData.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating.');
    });
}

// Initialise le chargement dynamique des créneaux horaires disponibles 
// lorsqu'une date est choisie lors de la création d'un rendez-vous par l'administrateur.
function initCreateAppointmentSlots() {
    const appointmentDateField = document.getElementById('appointment_date');
    const timeSlotDropdown = document.getElementById('time_slot_id');

    if (!appointmentDateField || !timeSlotDropdown) return;

    const adminLanguage = document.documentElement.getAttribute('lang') || 'fr';

    appointmentDateField.addEventListener('change', () => {
        const date = appointmentDateField.value;

        timeSlotDropdown.innerHTML = '<option value="">--</option>';

        if (!date) return;

        // Désactiver le menu déroulant pendant le chargement
        timeSlotDropdown.disabled = true;

        // Requête AJAX pour récupérer les créneaux disponibles à cette date
        const slotAjaxRequest = new XMLHttpRequest();
        slotAjaxRequest.open('GET', `../traitement/get-slots.php?date=${encodeURIComponent(date)}`, true);
        slotAjaxRequest.onload = () => {
            timeSlotDropdown.disabled = false;
            if (slotAjaxRequest.status === 200) {
                try {
                    const slotResponseData = JSON.parse(slotAjaxRequest.responseText);

                    if (slotResponseData.error) {
                        const errorMessage = adminLanguage === 'ar' ? slotResponseData.message.ar : slotResponseData.message.fr;
                        timeSlotDropdown.innerHTML = `<option value="">${errorMessage}</option>`;
                    } else if (slotResponseData.slots && slotResponseData.slots.length > 0) {
                        let dropdownOptionsHtml = '<option value="">--</option>';
                        slotResponseData.slots.forEach(currentSlotData => {
                            const remainingSpots = parseInt(currentSlotData.max_patients, 10) - parseInt(currentSlotData.current_bookings, 10);
                            const isSlotFull = remainingSpots <= 0;
                            let slotOptionLabel = '';

                            if (adminLanguage === 'ar') {
                                slotOptionLabel = isSlotFull ? ' (ممتلئ)' : ` (متاح: ${remainingSpots}/${currentSlotData.max_patients})`;
                            } else {
                                slotOptionLabel = isSlotFull ? ' (Complet)' : ` (Disponible: ${remainingSpots}/${currentSlotData.max_patients})`;
                            }

                            const slotDisabledAttribute = isSlotFull ? 'disabled' : '';
                            dropdownOptionsHtml += `<option value="${currentSlotData.id}" ${slotDisabledAttribute}>`;
                            dropdownOptionsHtml += `${currentSlotData.start_time.substring(0, 5)} - ${currentSlotData.end_time.substring(0, 5)}${slotOptionLabel}`;
                            dropdownOptionsHtml += '</option>';
                        });
                        timeSlotDropdown.innerHTML = dropdownOptionsHtml;
                    } else {
                        const noSlotsMessage = adminLanguage === 'ar' ? 'لا توجد مواعيد متاحة لهذا اليوم' : 'Aucun créneau disponible pour ce jour';
                        timeSlotDropdown.innerHTML = `<option value="">${noSlotsMessage}</option>`;
                    }
                } catch (parseError) {
                    console.error('Error parsing JSON response', parseError);
                    timeSlotDropdown.innerHTML = `<option value="">${adminLanguage === 'ar' ? 'حدث خطأ في تحميل البيانات' : 'Erreur de lecture des données'}</option>`;
                }
            } else {
                const errorMessage = adminLanguage === 'ar' ? 'حدث خطأ في تحميل المواعيد' : 'Erreur de chargement des créneaux';
                timeSlotDropdown.innerHTML = `<option value="">${errorMessage}</option>`;
            }
        };
        slotAjaxRequest.onerror = () => {
            timeSlotDropdown.disabled = false;
            const errorMessage = adminLanguage === 'ar' ? 'حدث خطأ في تحميل المواعيد' : 'Erreur de chargement des créneaux';
            timeSlotDropdown.innerHTML = `<option value="">${errorMessage}</option>`;
        };
        slotAjaxRequest.send();
    });
}

// Demande une confirmation avant de supprimer un élément générique (ex: rendez-vous, créneau).
// @param {number|string} recordId - L'ID de l'enregistrement à supprimer.
// @param {string} customMessage - Message optionnel à afficher dans la boîte de dialogue.
function confirmDelete(recordId, customMessage = '') {
    // Déterminer la langue pour afficher le bon message de confirmation
    const adminLanguage = document.documentElement.getAttribute('lang') || 'fr';
    const defaultConfirmMessage = adminLanguage === 'ar' ? 'هل أنت متأكد من الحذف؟' : 'Confirmer la suppression ?';
    const finalConfirmMessage = customMessage || defaultConfirmMessage;
    
    // Si l'utilisateur confirme, soumettre le formulaire caché de suppression
    if (confirm(finalConfirmMessage)) {
        const deletionForm = document.getElementById('deleteForm');
        const deletionIdField = document.getElementById('deleteIdInput');
        if (deletionForm && deletionIdField) {
            deletionIdField.value = recordId;
            deletionForm.submit();
        }
    }
}

// Demande une confirmation avant de supprimer une exception (ex: jour de congé/fermeture).
// @param {number|string} recordId - L'ID de l'exception à supprimer.
// @param {string} customMessage - Message optionnel.
function confirmDeleteException(recordId, customMessage = '') {
    const adminLanguage = document.documentElement.getAttribute('lang') || 'fr';
    const defaultConfirmMessage = adminLanguage === 'ar' ? 'هل أنت متأكد من الحذف؟' : 'Confirmer la suppression ?';
    const finalConfirmMessage = customMessage || defaultConfirmMessage;
    if (confirm(finalConfirmMessage)) {
        const exceptionDeletionForm = document.getElementById('deleteExcForm');
        const exceptionDeletionIdField = document.getElementById('deleteExcIdInput');
        if (exceptionDeletionForm && exceptionDeletionIdField) {
            exceptionDeletionIdField.value = recordId;
            exceptionDeletionForm.submit();
        }
    }
}

// Soumet un formulaire caché pour changer le statut d'un rendez-vous.
// @param {number|string} recordId - L'ID du rendez-vous.
// @param {string} newStatus - Le nouveau statut (ex: 'confirmed', 'cancelled').
function submitStatusChange(recordId, newStatus) {
    const statusChangeForm = document.getElementById('statusForm');
    const statusChangeIdField = document.getElementById('statusIdInput');
    const statusChangeValueField = document.getElementById('statusValueInput');
    if (statusChangeForm && statusChangeIdField && statusChangeValueField) {
        statusChangeIdField.value = recordId;
        statusChangeValueField.value = newStatus;
        statusChangeForm.submit();
    }
}
