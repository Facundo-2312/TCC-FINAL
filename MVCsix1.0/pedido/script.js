const searchInput = document.getElementById('searchInput');
const productsContainer = document.getElementById('productsContainer');
const orderList = document.getElementById('orderList');
var orderArray = [];

function buildImageCandidates(rawPath) {
  const raw = String(rawPath || '').trim();
  if (!raw) {
    return [];
  }

  if (/^(https?:)?\/\//i.test(raw) || raw.indexOf('data:image/') === 0) {
    return [raw];
  }

  const normalized = raw.replace(/\\/g, '/').replace(/^\/+/, '');
  const cleaned = normalized.replace(/^(\.\.\/)+/, '');
  const filename = cleaned.split('/').pop();
  const candidates = [];

  candidates.push('../' + cleaned);

  if (filename) {
    candidates.push('../files/' + filename);
    candidates.push('../img/' + filename);
    candidates.push('../../img/' + filename);
  }

  return candidates.filter((value, index, arr) => value && arr.indexOf(value) === index);
}

function getPreferredImagePath(product) {
  if (product && product.ImgResolved) {
    return product.ImgResolved;
  }
  return product ? product.Img : '';
}

function applyImageWithFallback(imgElement, rawPath) {
  const candidates = buildImageCandidates(rawPath);
  if (candidates.length === 0) {
    imgElement.style.display = 'none';
    return;
  }

  let index = 0;
  const tryNext = () => {
    if (index >= candidates.length) {
      imgElement.style.display = 'none';
      return;
    }

    imgElement.src = candidates[index];
    index += 1;
  };

  imgElement.onerror = tryNext;
  tryNext();
}

function createProductCard(product) {
  const productDiv = document.createElement('div');
  productDiv.classList.add('product');

  const nameElement = document.createElement('div');
  nameElement.textContent = product.Nombre;

  const imgElement = document.createElement('img');
  imgElement.alt = product.Nombre;
  applyImageWithFallback(imgElement, getPreferredImagePath(product));

  productDiv.appendChild(nameElement);
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

  return productDiv;
}

// Función para mostrar todos los productos
function displayAllProducts() {
  productsContainer.innerHTML = "";

  products.forEach(product => {
    productsContainer.appendChild(createProductCard(product));
  });
}

// Función para filtrar los productos según el término de búsqueda
function filterProducts() {
  const searchTerm = searchInput.value.toLowerCase();

  productsContainer.innerHTML = "";

  products.filter(product => product.Nombre.toLowerCase().includes(searchTerm))
    .forEach(product => {
      productsContainer.appendChild(createProductCard(product));
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

