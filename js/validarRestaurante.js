window.onload = function() {
    // Referencias a los elementos del formulario
    const form = document.querySelector('.register--form');
    const nombreInput = document.querySelector('input[name="nombre"]');
    const tipoInput = document.querySelector('input[name="tipo"]');
    const ubicacionInput = document.querySelector('input[name="ubicacion"]');
    const descripcionInput = document.querySelector('input[name="descripcion"]');
    const correoInput = document.querySelector('input[name="correo"]');
    const termsCheckbox = document.getElementById('terms');
    const submitButton = document.querySelector('.register--button');

    // Cancelar el envío del formulario para validar primero
    submitButton.addEventListener('click', function(event) {
        event.preventDefault();
        
        // Limpiar errores anteriores
        clearErrors();
        
        let isValid = true;
        
        // Validación de nombre del restaurante
        if (nombreInput.value.trim() === "") {
            showError(nombreInput, "El nombre del restaurante es obligatorio");
            isValid = false;
        } else if (nombreInput.value.trim().length < 3 || nombreInput.value.trim().length > 50) {
            showError(nombreInput, "El nombre debe tener entre 3 y 50 caracteres");
            isValid = false;
        }

        // Validación de tipo de comida
        if (tipoInput.value.trim() === "") {
            showError(tipoInput, "El tipo de comida es obligatorio");
            isValid = false;
        } else if (tipoInput.value.trim().length < 3 || tipoInput.value.trim().length > 30) {
            showError(tipoInput, "El tipo de comida debe tener entre 3 y 30 caracteres");
            isValid = false;
        }

        // Validación de ubicación
        if (ubicacionInput.value.trim() === "") {
            showError(ubicacionInput, "La ubicación es obligatoria");
            isValid = false;
        } else if (ubicacionInput.value.trim().length < 5 || ubicacionInput.value.trim().length > 100) {
            showError(ubicacionInput, "La ubicación debe tener entre 5 y 100 caracteres");
            isValid = false;
        }

        // Validación de descripción
        if (descripcionInput.value.trim() === "") {
            showError(descripcionInput, "La descripción es obligatoria");
            isValid = false;
        } else if (descripcionInput.value.trim().length < 10 || descripcionInput.value.trim().length > 500) {
            showError(descripcionInput, "La descripción debe tener entre 10 y 500 caracteres");
            isValid = false;
        }

        // Validación de correo electrónico
        if (correoInput.value.trim() === "") {
            showError(correoInput, "El correo electrónico es obligatorio");
            isValid = false;
        } else if (!validateEmail(correoInput.value.trim())) {
            showError(correoInput, "El correo electrónico no es válido");
            isValid = false;
        }

        // Validación de términos y condiciones
        if (!termsCheckbox.checked) {
            showError(termsCheckbox, "Debes aceptar los términos y condiciones");
            isValid = false;
        }

        // Si todo es válido, enviar el formulario
        if (isValid) {
            form.submit();
        }
    });
    
    // Función para mostrar errores debajo del campo correspondiente
    function showError(input, message) {
        // Crear elemento de error
        const errorElement = document.createElement('div');
        errorElement.className = 'error-text';
        errorElement.textContent = message;
        
        // Resaltar el campo con error
        if (input.style) {
            input.style.borderColor = '#ff3333';
        }
        
        // Insertar el mensaje debajo del campo correspondiente
        if (input === termsCheckbox) {
            // Caso especial para el checkbox de términos
            const termsContainer = document.querySelector('.terms-container');
            termsContainer.appendChild(errorElement);
        } else {
            const inputContainer = input.closest('.register-box');
            if (inputContainer) {
                // Crear contenedor para el mensaje de error si no existe
                let errorContainer = inputContainer.querySelector('.error-container');
                if (!errorContainer) {
                    errorContainer = document.createElement('div');
                    errorContainer.className = 'error-container';
                    inputContainer.appendChild(errorContainer);
                }
                
                // Limpiar errores anteriores en este campo
                errorContainer.innerHTML = '';
                errorContainer.appendChild(errorElement);
            }
        }
        
        // Eliminar el error cuando el usuario comienza a escribir/modificar
        if (input !== termsCheckbox) {
            input.addEventListener('input', function clearErrorOnType() {
                if (input.style) {
                    input.style.borderColor = '';
                }
                const errorContainer = this.closest('.register-box')?.querySelector('.error-container');
                if (errorContainer) {
                    errorContainer.remove();
                }
                input.removeEventListener('input', clearErrorOnType);
            });
        } else {
            termsCheckbox.addEventListener('change', function clearErrorOnCheck() {
                const errorElement = this.parentNode.querySelector('.error-text');
                if (errorElement) {
                    errorElement.remove();
                }
                termsCheckbox.removeEventListener('change', clearErrorOnCheck);
            });
        }
    }
    
    // Función para limpiar todos los errores
    function clearErrors() {
        // Limpiar estilos de los campos
        const inputs = [nombreInput, tipoInput, ubicacionInput, descripcionInput, correoInput];
        inputs.forEach(input => {
            if (input.style) {
                input.style.borderColor = '';
            }
        });
        
        // Eliminar todos los contenedores de error
        const errorContainers = document.querySelectorAll('.error-container');
        errorContainers.forEach(container => container.remove());
        
        // Eliminar mensajes de error de términos
        const termsErrors = document.querySelectorAll('.terms-container .error-text');
        termsErrors.forEach(error => error.remove());
    }
    
    // Función para validar formato de email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
};