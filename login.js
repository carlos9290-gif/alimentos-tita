///Detecta clics en los enlaces
document.querySelector('a[href="#inicio_sesion"]').addEventListener('click', function (e) {
    e.preventDefault();
    abrirModal('modal-login');
});

document.querySelector('a[href="#crear_cuenta"]').addEventListener('click', function (e) {
    e.preventDefault();
    abrirModal('modal-register');
});

// Funciones para abrir y cerrar modales
function abrirModal(id) {
    document.getElementById(id).classList.remove('hidden');
}

function cerrarModal(id) {
    document.getElementById(id).classList.add('hidden');
}