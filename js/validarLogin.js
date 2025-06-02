window.onload = function() {
    // Referencias a los elementos del formulario
    const form = document.querySelector('.login--form');
    const usuarioInput = document.getElementById('login-input-text');
    const contrasenaInput = document.getElementById('login-input-pass');
    const submitButton = document.querySelector('.login--button');

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
        const inputContainer = input.closest('.login-box');
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
        contrasenaInput.style.borderColor = '';
        
        // Eliminar todos los mensajes de error
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(msg => msg.remove());
    }

    const showHiddenPass = (loginPass, loginEye) =>{
        const input = document.getElementById(loginPass),
              iconEye = document.getElementById(loginEye)
     
        iconEye.addEventListener('click', () =>{
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
     
     showHiddenPass('login-input-pass','login--eye')

};