document.addEventListener('DOMContentLoaded', function() {
    // Script para las estrellas de valoración
    const stars = document.querySelectorAll('.star');
    const valoracionInput = document.getElementById('valoracion-input');
    const valoracionForm = document.getElementById('valoracion-form');
    let selectedRating = 0;
    
    if (stars.length > 0 && valoracionInput && valoracionForm) {
        stars.forEach(star => {
            star.addEventListener('click', function() {
                selectedRating = parseInt(this.getAttribute('data-value'));
                valoracionInput.value = selectedRating;
                stars.forEach(s => {
                    s.classList.remove('selected');
                    if (parseInt(s.getAttribute('data-value')) <= selectedRating) {
                        s.classList.add('selected');
                    }
                });
            });
            star.addEventListener('mouseover', function() {
                const value = parseInt(this.getAttribute('data-value'));
                stars.forEach(s => {
                    if (parseInt(s.getAttribute('data-value')) <= value) {
                        s.style.color = '#f1c40f';
                    } else {
                        s.style.color = '#ddd';
                    }
                });
            });
            star.addEventListener('mouseout', function() {
                stars.forEach(s => {
                    if (!selectedRating || parseInt(s.getAttribute('data-value')) > selectedRating) {
                        if (s.classList.contains('selected')) {
                            s.style.color = '#f1c40f';
                        } else {
                            s.style.color = '#ddd';
                        }
                    }
                });
            });
        });
        // Validar formulario antes de enviar
        valoracionForm.addEventListener('submit', function(e) {
            if (selectedRating === 0) {
                e.preventDefault();
                alert('Por favor selecciona una valoración con las estrellas');
            }
        });
    }

    // Manejar el modal de historial
    const modal = document.getElementById("historyModal");
    const btn = document.getElementById("historyBtn");
    const span = document.getElementsByClassName("close")[0];

    if (btn) {
        btn.onclick = function() {
            modal.style.display = "block";
        }
    }

    if (span) {
        span.onclick = function() {
            modal.style.display = "none";
        }
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
});