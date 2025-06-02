window.onload = function() {
    // Referencias a los elementos del formulario
    const form = document.querySelector('.register--form');
    const usuarioInput = document.querySelector('input[name="usuario"]');
    const correoInput = document.querySelector('input[name="correo"]');
    const contrasenaInput = document.getElementById('register-input-pass');
    const submitButton = document.querySelector('.register--button');

    // Cancelar el envío del formulario para validar primero
    submitButton.addEventListener('click', function(event) {
        event.preventDefault();
        
        // Limpiar errores anteriores
        clearErrors();
        
        let isValid = true;
        
        // Validación de usuario
        if (usuarioInput.value.trim() === "") {
            showError(usuarioInput, "El usuario es obligatorio");
            isValid = false;
        } else if (usuarioInput.value.trim().length < 4 || usuarioInput.value.trim().length > 10) {
            showError(usuarioInput, "El usuario debe tener entre 4 y 10 caracteres");
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

        // Validación de contraseña
        if (contrasenaInput.value.trim() === "") {
            showError(contrasenaInput, "La contraseña es obligatoria");
            isValid = false;
        } else if (contrasenaInput.value.trim().length < 4 || contrasenaInput.value.trim().length > 10) {
            showError(contrasenaInput, "La contraseña debe tener entre 4 y 10 caracteres");
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
        const errorElement = document.createElement('span');
        errorElement.className = 'error-message';
        errorElement.textContent = message;
        errorElement.style.color = 'red';
        errorElement.style.display = 'block';
        errorElement.style.fontSize = '0.8rem';
        errorElement.style.marginTop = '5px';
        
        // Resaltar el campo con error
        input.style.borderColor = 'red';
        
        // Insertar el mensaje después del campo correspondiente
        const inputContainer = input.closest('.register-box');
        if (inputContainer) {
            // Verificar si ya existe un mensaje de error
            const existingError = inputContainer.nextElementSibling;
            if (existingError && existingError.classList.contains('error-message')) {
                existingError.textContent = message;
            } else {
                inputContainer.parentNode.insertBefore(errorElement, inputContainer.nextSibling);
            }
        }
        
        // Eliminar el error cuando el usuario comienza a escribir
        input.addEventListener('input', function clearErrorOnType() {
            input.style.borderColor = '';
            if (errorElement.parentNode) {
                errorElement.parentNode.removeChild(errorElement);
            }
            input.removeEventListener('input', clearErrorOnType);
        });
    }
    
    // Función para limpiar todos los errores
    function clearErrors() {
        // Limpiar estilos de los campos
        usuarioInput.style.borderColor = '';
        correoInput.style.borderColor = '';
        contrasenaInput.style.borderColor = '';
        
        // Eliminar todos los mensajes de error
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(msg => msg.remove());
    }
    
    // Función para validar formato de email
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Función para mostrar/ocultar contraseña (similar a la del login)
    const showHiddenPass = (registerPass, registerEye) => {
        const input = document.getElementById(registerPass),
              iconEye = document.getElementById(registerEye)
     
        iconEye.addEventListener('click', () => {
           // Cambiar de contraseña a texto
           if(input.type === 'password'){
              input.type = 'text'
     
              // Cambiamos el Icono
              iconEye.classList.add('ri-eye-line')
              iconEye.classList.remove('ri-eye-off-line')
           } else{
              // Cambiamos a Contraseña
              input.type = 'password'
     
              // Cambiamos Icono
              iconEye.classList.remove('ri-eye-line')
              iconEye.classList.add('ri-eye-off-line')
           }
        })
    }
     
    showHiddenPass('register-input-pass', 'register--eye')
};