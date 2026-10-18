// Función para sumar los subtotales
function calcularTotal(productos) {
    return productos.reduce((total, producto) => total + Number(producto.subtotal), 0);
}

// Obtener id_pedido de la URL (parámetro ?pedido_id=NN)
const urlParams = new URLSearchParams(window.location.search);
const pedidoId = urlParams.get('pedido_id');

if (pedidoId) {
    // Obtener los productos desde el backend PHP usando calculadora_total.php?pedido_id=NN
    fetch('calculadora_total.php?pedido_id=' + encodeURIComponent(pedidoId))
        .then(response => response.json())
        .then(productos => {
            const total = calcularTotal(productos);
            document.getElementById('total-productos').innerText = "" + total;
            // Aquí puedes mostrar el total en el HTML si lo deseas
        })
        .catch(error => {
            console.error("Error al obtener los productos:", error);
        });
} else {
    // Si no hay pedido_id en la URL, mostrar 0
    document.getElementById('total-productos').innerText = "0";
}