const searchInput = document.getElementById('searchInput');
const productsContainer = document.getElementById('productsContainer');
const orderList = document.getElementById('orderList');
const orderItemsCount = document.getElementById('orderItemsCount');
const orderTotalValue = document.getElementById('orderTotalValue');
const currencySelect = document.getElementById('currencySelect');
const exchangeRateHint = document.getElementById('exchangeRateHint');
var orderArray = [];

const currencyLocales = {
  UYU: 'es-UY',
  BRL: 'pt-BR'
};

const UYU_PER_BRL = 9;

function getCurrentExchangeRate() {
  return currentCurrency === 'BRL' ? UYU_PER_BRL : 1;
}

let currentCurrency = currencySelect ? currencySelect.value : 'UYU';

function getCurrencyFormatter() {
  return new Intl.NumberFormat(currencyLocales[currentCurrency] || 'es-UY', {
    style: 'currency',
    currency: currentCurrency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 2
  });
}

function parseProductPrice(product) {
  const parsed = Number(product && product.Precio);
  if (Number.isNaN(parsed)) {
    return 0;
  }
  return parsed;
}

function convertFromUyu(valueUyu) {
  const amount = Number(valueUyu) || 0;
  if (currentCurrency === 'BRL') {
    return amount / UYU_PER_BRL;
  }
  return amount;
}

function formatPrice(value) {
  return getCurrencyFormatter().format(convertFromUyu(value));
}

function updateExchangeRateHint() {
  if (!exchangeRateHint) {
    return;
  }
  exchangeRateHint.textContent = 'Cotización';
}

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
  nameElement.classList.add('product-name');
  nameElement.textContent = product.Nombre;

  const priceElement = document.createElement('small');
  priceElement.classList.add('product-price');
  priceElement.textContent = formatPrice(parseProductPrice(product));

  const imgElement = document.createElement('img');
  imgElement.alt = product.Nombre;
  applyImageWithFallback(imgElement, getPreferredImagePath(product));

  productDiv.appendChild(nameElement);
  productDiv.appendChild(priceElement);
  productDiv.appendChild(imgElement);

  productDiv.addEventListener('click', () => {
    const index = orderArray.findIndex(item => Number(item.IDProducto) === Number(product.IDProducto));
    if (index !== -1) {
      orderArray[index].quantity += 1;
    } else {
      orderArray.push({
        ...product,
        Precio: parseProductPrice(product),
        quantity: 1,
        sinIngredientes: '',
        extraIngredientes: ''
      });
    }
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

function refreshProductsGrid() {
  const hasSearch = searchInput.value.trim() !== '';
  if (hasSearch) {
    filterProducts();
    return;
  }
  displayAllProducts();
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
  orderList.innerHTML = '';

  if (!Array.isArray(a) || a.length === 0) {
    const emptyItem = document.createElement('li');
    emptyItem.classList.add('pedido-empty');
    emptyItem.textContent = 'No hay productos seleccionados';
    orderList.appendChild(emptyItem);
    orderItemsCount.textContent = '0 productos';
    orderTotalValue.textContent = formatPrice(0);
    return;
  }

  let total = 0;
  let quantitySum = 0;

  a.forEach((item) => {
    const orderItem = document.createElement('li');
    orderItem.classList.add('pedido');

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.textContent = 'x';
    removeButton.addEventListener('click', () => {
      orderArray = orderArray.filter(order => Number(order.IDProducto) !== Number(item.IDProducto));
      showOrder(orderArray);
    });

    const productInfo = document.createElement('div');
    productInfo.classList.add('pedido-info');

    const productName = document.createElement('span');
    productName.classList.add('pedido-name');
    productName.textContent = item.Nombre;

    const productUnitPrice = document.createElement('small');
    productUnitPrice.classList.add('pedido-unit-price');
    productUnitPrice.textContent = 'Unitario: ' + formatPrice(item.Precio);

    productInfo.appendChild(productName);
    productInfo.appendChild(productUnitPrice);

    const quantityInput = document.createElement('input');
    quantityInput.type = 'number';
    quantityInput.min = '1';
    quantityInput.step = '1';
    quantityInput.value = item.quantity;
    quantityInput.addEventListener('change', () => {
      const quantity = Math.max(1, parseInt(quantityInput.value, 10) || 1);
      quantityInput.value = String(quantity);
      const index = orderArray.findIndex(order => Number(order.IDProducto) === Number(item.IDProducto));
      if (index !== -1) {
        orderArray[index].quantity = quantity;
      }
      showOrder(orderArray);
    });

    const subtotal = Number(item.Precio) * Number(item.quantity);
    total += subtotal;
    quantitySum += Number(item.quantity);

    const customBlock = document.createElement('div');
    customBlock.classList.add('pedido-custom');

    const inputSin = document.createElement('input');
    inputSin.type = 'text';
    inputSin.placeholder = 'Quitar ingredientes (ej: cebolla, pepino)';
    inputSin.value = item.sinIngredientes || '';
    inputSin.addEventListener('input', () => {
      const index = orderArray.findIndex(order => Number(order.IDProducto) === Number(item.IDProducto));
      if (index !== -1) {
        orderArray[index].sinIngredientes = inputSin.value.trim();
      }
    });

    const inputExtra = document.createElement('input');
    inputExtra.type = 'text';
    inputExtra.placeholder = 'Agregar ingredientes (ej: doble queso, bacon)';
    inputExtra.value = item.extraIngredientes || '';
    inputExtra.addEventListener('input', () => {
      const index = orderArray.findIndex(order => Number(order.IDProducto) === Number(item.IDProducto));
      if (index !== -1) {
        orderArray[index].extraIngredientes = inputExtra.value.trim();
      }
    });

    customBlock.appendChild(inputSin);
    customBlock.appendChild(inputExtra);

    const subtotalElement = document.createElement('strong');
    subtotalElement.classList.add('pedido-subtotal');
    subtotalElement.textContent = formatPrice(subtotal);

    orderItem.appendChild(removeButton);
    orderItem.appendChild(productInfo);
    orderItem.appendChild(quantityInput);
    orderItem.appendChild(subtotalElement);
    orderItem.appendChild(customBlock);

    orderList.appendChild(orderItem);
  });

  orderItemsCount.textContent = quantitySum + ' productos';
  orderTotalValue.textContent = formatPrice(total);
}

searchInput.addEventListener('keyup', filterProducts);

if (currencySelect) {
  currencySelect.addEventListener('change', () => {
    currentCurrency = currencySelect.value;
    updateExchangeRateHint();
    refreshProductsGrid();
    showOrder(orderArray);
  });
}

// Mostrar todos los productos al cargar la página
displayAllProducts();
updateExchangeRateHint();
showOrder(orderArray);

function enviar(){
  if (orderArray.length === 0) {
    const respuesta = document.getElementById('res');
    respuesta.innerHTML = 'Debes seleccionar al menos un producto.';
    respuesta.style.display = 'block';
    setTimeout(function() {
      respuesta.style.display = 'none';
    }, 3000);
    return;
  }

  let valueObs = document.getElementById("observaciones").value;
  let Mesa = document.getElementById("Mesa").value;

  if (!Mesa.trim()) {
    const respuesta = document.getElementById('res');
    respuesta.innerHTML = 'Ingresa un numero de mesa.';
    respuesta.style.display = 'block';
    setTimeout(function() {
      respuesta.style.display = 'none';
    }, 3000);
    return;
  }

  pedido = {
    items : orderArray,
    obs : valueObs,
    CI: cedula,
    Mesa: Mesa,
    moneda: currentCurrency,
    cotizacion: getCurrentExchangeRate()
   
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

