document.addEventListener('DOMContentLoaded', function() {
    // Cerrar alertas automáticamente después de 5 segundos
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Validación de formularios
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Vista previa de imagen al subirla
    const imgInputs = document.querySelectorAll('.img-preview-input');
    
    imgInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const previewId = this.dataset.preview;
            const preview = document.getElementById(previewId);
            
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    });

    // Confirmar eliminación
    const deleteButtons = document.querySelectorAll('.confirm-delete');
    
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(event) {
            if (!confirm('¿Estás seguro de que deseas eliminar este elemento? Esta acción no se puede deshacer.')) {
                event.preventDefault();
            }
        });
    });

    // Añadir campos de experiencia laboral dinámicamente
    const addExpBtn = document.getElementById('add-experience');
    if (addExpBtn) {
        addExpBtn.addEventListener('click', function() {
            const container = document.getElementById('experiences-container');
            const index = container.children.length;
            
            const expDiv = document.createElement('div');
            expDiv.className = 'card mb-3';
            expDiv.innerHTML = `
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="exp_empresa_${index}" class="form-label">Empresa</label>
                            <input type="text" class="form-control" id="exp_empresa_${index}" name="experiencia[${index}][empresa]" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="exp_puesto_${index}" class="form-label">Puesto</label>
                            <input type="text" class="form-control" id="exp_puesto_${index}" name="experiencia[${index}][puesto]" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="exp_inicio_${index}" class="form-label">Fecha Inicio</label>
                            <input type="date" class="form-control" id="exp_inicio_${index}" name="experiencia[${index}][fecha_inicio]" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="exp_fin_${index}" class="form-label">Fecha Fin</label>
                            <input type="date" class="form-control" id="exp_fin_${index}" name="experiencia[${index}][fecha_fin]">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="exp_actual_${index}" name="experiencia[${index}][actual]">
                                <label class="form-check-label" for="exp_actual_${index}">Trabajo actual</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="exp_descripcion_${index}" class="form-label">Descripción</label>
                        <textarea class="form-control" id="exp_descripcion_${index}" name="experiencia[${index}][descripcion]" rows="3"></textarea>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm remove-exp">Eliminar</button>
                </div>
            `;
            
            container.appendChild(expDiv);
            
            // Añadir listener al botón eliminar
            const removeBtn = expDiv.querySelector('.remove-exp');
            removeBtn.addEventListener('click', function() {
                container.removeChild(expDiv);
            });
        });
    }
});