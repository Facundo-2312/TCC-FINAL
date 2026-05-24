// Obtener el contenedor de pedidos
const pedidosContainer = document.getElementById('pedidosContainer');

// Variable para almacenar el número de pedidos cargados hasta ahora
let numeroDePedidosCargados = 0;

// Lista para almacenar los IDs de los pedidos ya mostrados
const pedidosMostrados = [];

// Función para cargar más pedidos desde el servidor
function cargarMasPedidos() {
  const xhr = new XMLHttpRequest();
  xhr.open('GET', `ObtenerPedidosJSON.php`, true);

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
        console.log(pedido);
        const pedidoCard = document.createElement('div');
        pedidoCard.classList.add('pedido-card');
        pedidoCard.classList.add('item');
        pedidoCard.setAttribute('id', 'pedido_id'+pedido.id);

     
        
   
        // pedidoCard.innerHTML = ' <span class="product_list"><b> Pedido: ' + pedido.id + '<br></span>';
      

        pedidoCard.innerHTML += '<span class="product_list"><b>Mesa: ' + pedido.Mesa + '</b></span><br>';

        let productos = pedido.productos;
        productos.forEach( (producto) => {
          pedidoCard.innerHTML += '<span class ="product_list"><b>-'+producto.Cantidad+' '+producto.Nombre+'</b></span><br>';
         
        });

         
         pedidoCard.innerHTML += '<span class="descripcion"><i>'+pedido.descripcion +'</i><br></span>';
    

         pedidoCard.addEventListener('click', () => {
          actualizarPedido(pedido);
        });
        if(pedido.estado == "Preparando"){
          pedidoCard.style.backgroundColor = '#74f582';
          // pedidoCard.addEventListener('click', () => {
          //   actualizarPedido2(pedido);
          // });
        }
        if(pedido.estado != "Entregado"){
          //pedidoCard.style.backgroundColor = 'red';
          pedidosContainer.insertAdjacentElement("beforeend", pedidoCard);
        }else{
          pedidoCard.style.display = 'none';
        }

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



function actualizarPedido(pedido){
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'InterfazActualizarPedido.php', true);

  xhr.onload = function () {
  if (xhr.status >= 200 && xhr.status < 300) {
      const card_element = document.getElementById('pedido_id'+pedido.id)
      console.log(card_element);
      if(pedido.estado == "Pendiente"){
        card_element.style.backgroundColor = '#74f582';
        pedido.estado = "Preparando";
      }else{
        card_element.remove();
      }

  } else {
      // Hubo un error al cargar los pedidos
      console.error('Error al cargar pedidos');
    }
  };
// Convertir los datos a formato JSON
let datosJSON = JSON.stringify(pedido);
  xhr.send(datosJSON);
}



