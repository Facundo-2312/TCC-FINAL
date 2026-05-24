const searchInput = document.getElementById('searchInput');
const productsContainer = document.getElementById('productsContainer');
const orderList = document.getElementById('orderList');
var orderArray = [];

// Función para mostrar todos los productos
function displayAllProducts() {
  productsContainer.innerHTML = "";
  
  products.forEach(product => {
    const productDiv = document.createElement('div');
    productDiv.classList.add('product');
    productDiv.textContent = product.Nombre;
    // Crear el elemento img
    const imgElement = document.createElement('img');
    // Configurar el atributo src con la URL de la imagen
    imgElement.src = '../' + product.Img;
    imgElement.style.width = '90px';
    // Agregar la imagen al div del producto
    productDiv.appendChild(imgElement);
    
    productDiv.addEventListener('click', () => {
      const orderItem = document.createElement('li');
      orderItem.classList.add('pedido');
      


      const productName = document.createElement('span');
      productName.textContent = product.Nombre;
      
      const removeButton = document.createElement('button');
      removeButton.textContent = 'x';
      removeButton.addEventListener('click', () => {
        orderItem.remove();
        const index = orderArray.findIndex(item => item.Nombre === product.Nombre);
        if (index !== -1) {
          orderArray.splice(index, 1);
        }
        showOrder(orderArray);
      });
      
      const quantityInput = document.createElement('input');
      quantityInput.type = 'number';
      quantityInput.value = 1;
      quantityInput.addEventListener('change', () => {
        const quantity = parseInt(quantityInput.value);
        const index = orderArray.findIndex(item => item.Nombre === product.Nombre);
        if (index !== -1) {
          orderArray[index].quantity = quantity;
        }
        showOrder(orderArray);
      });
      
      orderItem.appendChild(removeButton);
      orderItem.appendChild(productName);
      orderItem.appendChild(quantityInput);
      
      orderList.appendChild(orderItem);
      orderArray.push({ ...product, quantity: 1 });
      showOrder(orderArray);
    });
    
    productsContainer.appendChild(productDiv);
  });
}

// Función para filtrar los productos según el término de búsqueda
function filterProducts() {
  const searchTerm = searchInput.value.toLowerCase();

  productsContainer.innerHTML = "";

  products.filter(product => product.Nombre.toLowerCase().includes(searchTerm))
    .forEach(product => {
      const productDiv = document.createElement('div');
      productDiv.classList.add('product');
      productDiv.textContent = product.Nombre;
      //add imagen a la div 
      productDiv.style.backgroundImage = 'url(' + '../' + product.Img + ')';
      productDiv.style.backgroundSize = 'cover';
      productDiv.style.backgroundPosition = 'center';

      // Crear el elemento img
    const imgElement = document.createElement('img');
    // Configurar el atributo src con la URL de la imagen
    imgElement.src = '../' + product.Img;
    imgElement.style.width = '40px';
    // Agregar la imagen al div del producto
    productDiv.appendChild(imgElement);

      productDiv.addEventListener('click', () => {
        const orderItem = document.createElement('li');
        orderItem.classList.add('pedido');

        const productName = document.createElement('span');
        productName.textContent = product.Nombre;

        const removeButton = document.createElement('button');
        removeButton.textContent = 'x';
        removeButton.addEventListener('click', () => {
          orderItem.remove();
          const index = orderArray.findIndex(item => item.Nombre === product.Nombre);
          if (index !== -1) {
            orderArray.splice(index, 1);
          }
          showOrder(orderArray);
        });

        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.value = 1;
        quantityInput.addEventListener('change', () => {
          const quantity = parseInt(quantityInput.value);
          const index = orderArray.findIndex(item => item.Nombre === product.Nombre);
          if (index !== -1) {
            orderArray[index].quantity = quantity;
          }
          showOrder(orderArray);
        });

        orderItem.appendChild(removeButton);
        orderItem.appendChild(productName);
        orderItem.appendChild(quantityInput);

        orderList.appendChild(orderItem);
        orderArray.push({ ...product, quantity: 1 });
        showOrder(orderArray);
      });

      productsContainer.appendChild(productDiv);
    });
}


function showOrder(a){
  console.log(a);
}

searchInput.addEventListener('keyup', filterProducts);

// Mostrar todos los productos al cargar la página
displayAllProducts();

function enviar(){
  let valueObs = document.getElementById("observaciones").value;
  let Mesa = document.getElementById("Mesa").value;
  pedido = {
    items : orderArray,
    obs : valueObs,
    CI: cedula,
    Mesa: Mesa
   
  } 

  console.log(pedido);
  // Convertir los datos a formato JSON
  let datosJSON = JSON.stringify(pedido);

  // Crear una solicitud HTTP
  var xhr = new XMLHttpRequest();
  var url = '../GuardarPedido.php';

  xhr.open('POST', url, true);
  xhr.setRequestHeader('Content-type', 'application/json');

  // Enviar los datos al backend
  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4 && xhr.status === 200) {
        // Aquí puedes realizar acciones después de recibir la respuesta del backend
        var response = JSON.parse(xhr.responseText);
        let respuesta = document.getElementById('res');
        respuesta.innerHTML = response;
        respuesta.style.display = "block";
        setTimeout(function() {
          respuesta.style.display = 'none';
          location.reload();
        }, 5000);

    }
  };

  xhr.send(datosJSON);

}

