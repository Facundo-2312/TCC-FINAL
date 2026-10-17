UPDATE productos
SET img = CONCAT(LEFT(img, LENGTH(img) - 4), '.jpg')
WHERE img LIKE 'img/catalogo/%.svg';
