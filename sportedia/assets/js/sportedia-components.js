/**
 * Sportedia UI Components JavaScript Library
 */
(function() {
    'use strict';

    window.SportediaUI = {
        init: function() {
            this.bindDropdowns();
            this.bindModals();
            this.bindEscapeKey();
        },

        bindDropdowns: function() {
            document.addEventListener('click', function(e) {
                const trigger = e.target.closest('[data-sportedia-toggle="dropdown"]');
                if (trigger) {
                    e.preventDefault();
                    e.stopPropagation();
                    const dropdown = trigger.nextElementSibling;
                    if (dropdown && dropdown.classList.contains('sportedia-dropdown-menu')) {
                        const isOpen = dropdown.classList.contains('show');
                        SportediaUI.closeAllDropdowns();
                        if (!isOpen) {
                            dropdown.classList.add('show');
                        }
                    }
                    return;
                }

                if (!e.target.closest('.sportedia-dropdown-menu')) {
                    SportediaUI.closeAllDropdowns();
                }
            });
        },

        closeAllDropdowns: function() {
            document.querySelectorAll('.sportedia-dropdown-menu.show').forEach(function(menu) {
                menu.classList.remove('show');
            });
        },

        bindModals: function() {
            document.addEventListener('click', function(e) {
                if (e.target.matches('[data-sportedia-dismiss="modal"]') || e.target.closest('[data-sportedia-dismiss="modal"]')) {
                    const modal = e.target.closest('.sportedia-modal-overlay');
                    if (modal) {
                        SportediaUI.closeModal(modal.id);
                    }
                }
            });
        },

        openModal: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
        },

        closeModal: function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('show');
                document.body.style.overflow = '';
            }
        },

        bindEscapeKey: function() {
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    SportediaUI.closeAllDropdowns();
                    document.querySelectorAll('.sportedia-modal-overlay.show').forEach(function(modal) {
                        SportediaUI.closeModal(modal.id);
                    });
                }
            });
        },

        resetFilters: function(formId) {
            const form = document.getElementById(formId);
            if (form) {
                form.querySelectorAll('input[type="text"], select').forEach(function(input) {
                    input.value = '';
                });
                form.submit();
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function() {
        SportediaUI.init();
    });
})();
