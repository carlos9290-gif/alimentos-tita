// Código JavaScript para manejar el carrito de compras
document.addEventListener("DOMContentLoaded", () => {
    // Elementos del DOM (asegúrate de que existan en tu HTML)
    const carritoBody = document.querySelector("#lista-carrito tbody");
    const listaProductos = document.querySelectorAll(".agregar-carrito");
    const vaciarCarritoBtn = document.querySelector("#vaciar-carrito");
    const finalizarCompraBtn = document.querySelector("#finalizar-compra");

    let articulosCarrito = JSON.parse(sessionStorage.getItem("carrito")) || [];

    // Agregar listeners a los botones "Agregar al carrito"
    listaProductos.forEach(boton => {
        boton.addEventListener("click", (e) => {
            e.preventDefault();
            const botonActual = e.currentTarget; // el <a> con clase agregar-carrito
            const producto = botonActual.closest(".product");
            if (!producto) {
                console.warn("No se encontró el contenedor .product para este botón");
                return;
            }
            leerDatosProducto(producto, botonActual);
        });
    });

    // Vaciar carrito (si existe el botón)
    if (vaciarCarritoBtn) {
        vaciarCarritoBtn.addEventListener("click", (e) => {
            e.preventDefault();
            articulosCarrito = [];
            limpiarHTML();
            mostrarCarrito();
            sessionStorage.removeItem("carrito");
        });
    }

    // Delegación para eliminar un producto desde la tabla del carrito (si existe tbody)
    if (carritoBody) {
        carritoBody.addEventListener("click", (e) => {
            if (e.target.classList.contains("borrar-producto")) {
                e.preventDefault();
                const id = e.target.getAttribute("data-id");
                articulosCarrito = articulosCarrito.filter(item => item.id !== id);
                mostrarCarrito();
            }
        });
    }

    // Finalizar compra
    if (finalizarCompraBtn) {
        finalizarCompraBtn.addEventListener("click", (e) => {
            e.preventDefault();
            if (articulosCarrito.length === 0) {
                alert("Tu carrito está vacío.");
                return;
            }
            sessionStorage.setItem("carrito", JSON.stringify(articulosCarrito));
            window.location.href = "./finalizarCompra.html";
        });
    }

    // Leer datos del producto
    function leerDatosProducto(productoEl, botonEl) {
        const id = botonEl.getAttribute("data-id") || Date.now().toString();
        const imagen = productoEl.querySelector("img") ? productoEl.querySelector("img").src : "";
        const tituloEl = productoEl.querySelector(".card-title");
        const titulo = tituloEl ? tituloEl.textContent.trim() : "Sin título";
        const precioTexto = productoEl.querySelector(".precio") ? productoEl.querySelector(".precio").textContent : "0";
        const precio = parseFloat(precioTexto.replace(/[^\d]/g, "")) || 0;
        const cantidadInput = productoEl.querySelector(".cantidad-producto");
        const cantidad = cantidadInput ? parseInt(cantidadInput.value, 10) : 1;

        const infoProducto = { id, imagen, titulo, precio, cantidad };

        const existe = articulosCarrito.some(item => item.id === infoProducto.id);
        if (existe) {
            articulosCarrito = articulosCarrito.map(item => {
                if (item.id === infoProducto.id) {
                    item.cantidad += infoProducto.cantidad;
                }
                return item;
            });
        } else {
            articulosCarrito.push(infoProducto);
        }

        mostrarCarrito();
    }

    // Mostrar carrito
    function mostrarCarrito() {
        limpiarHTML();
        if (!carritoBody) return;

        let total = 0;
        articulosCarrito.forEach(item => {
            const fila = document.createElement("tr");
            fila.innerHTML = `
        <td><img src="${item.imagen}" width="50" alt="${item.titulo}"></td>
        <td>${item.titulo}</td>
        <td>$${(item.precio * item.cantidad).toLocaleString()}</td>
        <td><a href="#" class="borrar-producto" data-id="${item.id}">X</a></td>
    `;
            carritoBody.appendChild(fila);
            total += item.precio * item.cantidad;
        });

        if (total > 0) {
            const filaTotal = document.createElement("tr");
            filaTotal.innerHTML = `
        <td colspan="2"><strong>Total</strong></td>
        <td colspan="2"><strong>$${total.toLocaleString()}</strong></td>
    `;
            carritoBody.appendChild(filaTotal);
        }

        // Guardar estado en sessionStorage para mantenerlo en recargas
        sessionStorage.setItem("carrito", JSON.stringify(articulosCarrito));
    }

    // Limpiar HTML del carrito
    function limpiarHTML() {
        if (!carritoBody) return;
        while (carritoBody.firstChild) {
            carritoBody.removeChild(carritoBody.firstChild);
        }
    }

    // Al cargar la página, mostrar carrito si hay algo guardado
    mostrarCarrito();
});










/*
// Esta es el código que le da la funcionalidad al carrito de compras
document.addEventListener("DOMContentLoaded", () => {
    let btnAgregar = document.querySelector('#agregar-carrito');
    btnAgregar.addEventListener('click', function (e) {
        e.preventDefault();
        alert('Producto agregado al carrito');
    });

    console.log("Documento cargado y listo");
    const carrito = document.querySelector("#lista-carrito tbody");
    const listaProductos = document.querySelectorAll(".agregar-carrito");
    const vaciarCarritoBtn = document.querySelector("#vaciar-carrito");

    let articulosCarrito = [];

    // Delegamos evento click en cada botón de "Agregar al carrito"
    listaProductos.forEach(boton => {
        boton.addEventListener("click", (e) => {
            console.log("Botón de agregar al carrito clickeado");
            e.preventDefault();
            const producto = e.target.closest(".product");
            leerDatosProducto(producto);
        });
    });

    // Vaciar carrito
    vaciarCarritoBtn.addEventListener("click", (e) => {
        e.preventDefault();
        articulosCarrito = [];
        limpiarHTML();
        mostrarCarrito();
        //actualizarPildora();
    });

    // Leer los datos del producto seleccionado
    function leerDatosProducto(producto) {
        const infoProducto = {
            id: producto.querySelector(".agregar-carrito").getAttribute("data-id"),
            imagen: producto.querySelector("img").src,
            titulo: producto.querySelector("h3").textContent,
            precio: parseFloat(producto.querySelector(".precio").textContent.replace(/[^\d]/g, "")),
            cantidad: parseInt(producto.querySelector(".cantidad-producto").value)
        };

        const existe = articulosCarrito.some(item => item.id === infoProducto.id);
        if (existe) {
            articulosCarrito = articulosCarrito.map(item => {
                if (item.id === infoProducto.id) {
                    item.cantidad += infoProducto.cantidad;
                }
                return item;
            });
        } else {
            articulosCarrito = [...articulosCarrito, infoProducto];
        }

        mostrarCarrito();
    }

    // Mostrar carrito en el HTML
    function mostrarCarrito() {
        limpiarHTML();

        let total = 0;

        articulosCarrito.forEach(item => {
            const fila = document.createElement("tr");
            fila.innerHTML = `
                <td><img src="${item.imagen}" width="50"></td>
                <td>${item.titulo}</td>
                <td>$${(item.precio * item.cantidad).toLocaleString()}</td>
                <td><a href="#" class="borrar-producto" data-id="${item.id}">X</a></td>
            `;
            carrito.appendChild(fila);

            total += item.precio * item.cantidad;
        });

        if (total > 0) {
            const filaTotal = document.createElement("tr");
            filaTotal.innerHTML = `
                <td colspan="2"><strong>Total</strong></td>
                <td colspan="2"><strong>$${total.toLocaleString()}</strong></td>
            `;
            carrito.appendChild(filaTotal);
        }

        document.querySelectorAll(".borrar-producto").forEach(boton => {
            boton.addEventListener("click", (e) => {
                e.preventDefault();
                const productoId = e.target.getAttribute("data-id");
                articulosCarrito = articulosCarrito.filter(item => item.id !== productoId);
                mostrarCarrito();
            });
        });
    }

    // Limpiar HTML del carrito
    function limpiarHTML() {
        while (carrito.firstChild) {
            carrito.removeChild(carrito.firstChild);
        }
    }

    //Lógica para finalizar compra
    const finalizarCompraBtn = document.querySelector("#finalizar-compra");
    finalizarCompraBtn.addEventListener("click", (e) => {
        e.preventDefault();

        if (articulosCarrito.length === 0) {
            alert("Tu carrito está vacío.");
            return;
        }

        // Guardar carrito en sessionStorage
        sessionStorage.setItem("carrito", JSON.stringify(articulosCarrito));

        // Redirigir a checkout.html
        window.location.href = "./finalizarCompra.html";
    });
});
*/