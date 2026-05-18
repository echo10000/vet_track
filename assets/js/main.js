// assets/js/main.js

document.addEventListener('DOMContentLoaded', function () {
    const loader = document.getElementById('pageLoader');
    const speciesSelect = document.getElementById('species');
    const speciesOtherGroup = document.getElementById('speciesOtherGroup');
    const speciesOtherInput = document.getElementById('species_other');

    if (speciesSelect && speciesOtherGroup) {
        const syncOtherSpecies = function () {
            const isOther = speciesSelect.value === 'other';
            speciesOtherGroup.hidden = !isOther;

            if (!isOther && speciesOtherInput) {
                speciesOtherInput.value = '';
            }
        };

        speciesSelect.addEventListener('change', syncOtherSpecies);
        syncOtherSpecies();
    }

    const confirmModalElement = document.getElementById('confirmActionModal');
    const confirmTitle = document.getElementById('confirmActionTitle');
    const confirmMessage = document.getElementById('confirmActionMessage');
    const confirmSubmit = document.getElementById('confirmActionSubmit');
    let pendingConfirmForm = null;

    if (confirmModalElement && confirmSubmit && window.bootstrap) {
        const confirmModal = new window.bootstrap.Modal(confirmModalElement);

        document.querySelectorAll('.js-confirm-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.confirmed === 'true') {
                    return;
                }

                event.preventDefault();
                pendingConfirmForm = form;

                if (confirmTitle) {
                    confirmTitle.textContent = form.dataset.confirmTitle || 'Confirm action';
                }

                if (confirmMessage) {
                    confirmMessage.textContent = form.dataset.confirmMessage || 'Please confirm before continuing.';
                }

                confirmSubmit.textContent = form.dataset.confirmButton || 'Confirm';
                confirmModal.show();
            });
        });

        confirmSubmit.addEventListener('click', function () {
            if (!pendingConfirmForm) {
                return;
            }

            pendingConfirmForm.dataset.confirmed = 'true';
            confirmModal.hide();
            pendingConfirmForm.requestSubmit();
        });

        confirmModalElement.addEventListener('hidden.bs.modal', function () {
            if (pendingConfirmForm && pendingConfirmForm.dataset.confirmed !== 'true') {
                pendingConfirmForm = null;
            }
        });
    }

    if (!loader) {
        return;
    }

    window.addEventListener('beforeunload', function () {
        loader.classList.remove('is-complete');
        loader.classList.add('is-active');
    });

    requestAnimationFrame(function () {
        loader.classList.add('is-complete');
    });
});
