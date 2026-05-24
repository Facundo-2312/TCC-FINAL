// Obtener el contenedor de pedidos
const pedidosContainer = document.getElementById('pedidosContainer');

// Variable para almacenar el número de pedidos cargados hasta ahora
let numeroDePedidosCargados = 0;

// Lista para almacenar los IDs de los pedidos ya mostrados
const pedidosMostrados = [];

// Función para cargar más pedidos desde el servidor
function cargarMasPedidos() {
  const xhr = new XMLHttpRequest();
  xhr.open('GET', `Obtener_pedidos_caja.php?desde=${numeroDePedidosCargados}`, true);
  xhr.onload = function () {
    if (xhr.status >= 200 && xhr.status < 300) {
      // La solicitud fue exitosa
      const nuevosPedidos = JSON.parse(xhr.responseText);

      // Si no hay más pedidos disponibles, detener la carga automática
      if (nuevosPedidos.length === 0) {
        return;
      }

      // Filtrar los nuevos pedidos que aún no se han mostrado
      const nuevosPedidosFiltrados = nuevosPedidos.filter(pedido => !pedidosMostrados.includes(pedido.id));

      // Agregar los nuevos pedidos al contenedor
      nuevosPedidosFiltrados.forEach((pedido) => {
        const pedidoCard = document.createElement('div');
        pedidoCard.classList.add('pedido-card');
        pedidoCard.classList.add('item');
        pedidoCard.setAttribute('id', 'pedido_id'+pedido.id);
       
        
        pedidoCard.innerHTML += '<span class="product_list"><b>Mesa: ' + pedido.Mesa + '</b></span><br>';
        
        let productos = pedido.productos;
        var totalPrecio = 0;
        productos.forEach( (producto) => {
         
          pedidoCard.innerHTML += '<b>x'+producto.Cantidad+' '+producto.Nombre+' $'+producto.Precio+' </b><br>';
          totalPrecio = (parseInt(producto.Precio) * parseInt(producto.Cantidad)) + parseInt(totalPrecio);
        });
        pedidoCard.innerHTML += '<b>Total: '+totalPrecio+'</b>';


        // acá esta la opcion para el tipo de pago
        const input = document.createElement('input');
        input.setAttribute('type', 'text');
        input.setAttribute('placeholder', 'Tipo de pago');
        input.setAttribute('name', 'TipoPago'); // Establecer el atributo 'name' con el valor 'TipoPago'
        pedidoCard.appendChild(input);

        //boton  de facturar

        const boton = document.createElement('button');
        boton.textContent = 'Facturar';
        boton.addEventListener('click', () => {
          const pedidoCard = boton.parentElement;
          let TipoPago=input.value;
  
          // Elimina el pedido del DOM
          pedidoCard.remove();
          
          // Aca se obtiene el id del pedido cuando se factyra
          const pedidoId = pedidoCard.getAttribute('id').replace('pedido_id', '');
        
          // hacemos un AJAX para actualizar el estado del pedido a 4
          const xhrActualizarPedido = new XMLHttpRequest();
          xhrActualizarPedido.open('POST', 'Actualizar_estado_pedido.php', true);
        
          xhrActualizarPedido.onload = function () {
            if (xhrActualizarPedido.status >= 200 && xhrActualizarPedido.status < 300) {

              // lo de arriba señaliza que se actualizo ien "ok"
              
            } else {
              console.error('Error al actualizar el estado del pedido');
            }
          };
        
         // Envía el ID del pedido y el metodo de pago a actualizar
      
         pedido ={
          'pedidoId': pedidoId,
          'metodo': TipoPago,
          'total': totalPrecio
        } ;
        let datosJSON = JSON.stringify(pedido);
        xhrActualizarPedido.send(datosJSON);
        });

      

        

        pedidoCard.appendChild(boton);
        
      
       
       pedidosContainer.insertAdjacentElement("beforeend", pedidoCard);


        // Agregar el ID del pedido a la lista de pedidos mostrados
        pedidosMostrados.push(pedido.id);
      });

      // Actualizar la variable del número de pedidos cargados
      numeroDePedidosCargados += nuevosPedidos.length;
    } else {
      // Hubo un error al cargar los pedidos
      console.error('Error al cargar pedidos');
    }
  };

  xhr.send();
}

// Cargar los primeros pedidos al cargar la página
cargarMasPedidos();

// Llamar automáticamente a la función cargarMasPedidos cada 5 segundos
window.addEventListener("load", function() {
    setInterval(cargarMasPedidos, 3000); // 100 milisegundos = 5 segundos
});






