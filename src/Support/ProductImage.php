<?php

namespace App\Support;

class ProductImage
{
    public static function catalogPath($name)
    {
        $name = mb_strtolower(trim((string) $name), 'UTF-8');
        $name = strtr($name, array('á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'));
        $name = preg_replace('/\s+/', ' ', $name);
        $images = array(
            'pizza' => 'pizza-muzzarella.jpg',
            'pizza muzzarella' => 'pizza-muzzarella.jpg',
            'pasta al pesto' => 'pasta-pesto.jpg',
            'pasta bolognesa' => 'pasta-bolognesa.jpg',
            'lasagna de carne' => 'lasagna-carne.jpg',
            'milanesa completa' => 'milanesa-completa.jpg',
            'milanesa napolitana' => 'milanesa-completa.jpg',
            'chivito al plato' => 'chivito-plato.jpg',
            'bife de lomo' => 'bife-lomo.jpg',
            'pollo a la parrilla' => 'pollo-parrilla.jpg',
            'pescado del dia' => 'pescado-dia.jpg',
            'risotto de pollo' => 'risotto-pollo.jpg',
            'hamburguesa' => 'hamburguesa-clasica.jpg',
            'coca cola' => 'coca-cola-600.jpg',
            'coca-cola 600 ml' => 'coca-cola-600.jpg',
            'coca cola zero' => 'coca-cola-zero-600.jpg',
            'coca-cola zero 600 ml' => 'coca-cola-zero-600.jpg',
            'sprite' => 'sprite-600.jpg',
            'sprite 600 ml' => 'sprite-600.jpg',
            'fanta naranja' => 'fanta-naranja-600.jpg',
            'fanta naranja 600 ml' => 'fanta-naranja-600.jpg',
            'agua' => 'agua-mineral-500.jpg',
            'agua mineral 500 ml' => 'agua-mineral-500.jpg',
            'agua con gas 500 ml' => 'agua-gas-500.jpg',
            'limonada natural' => 'limonada-natural.jpg',
            'jugo de naranja' => 'jugo-naranja.jpg',
            'cafe espresso' => 'cafe-espresso.jpg',
            'cafe con leche' => 'cafe-leche.jpg',
            'papas fritas' => 'papas-fritas.jpg',
            'papas rusticas' => 'papas-rusticas.jpg',
            'pure de papas' => 'pure-papas.jpg',
            'pure de calabaza' => 'pure-calabaza.jpg',
            'arroz blanco' => 'arroz-blanco.jpg',
            'verduras salteadas' => 'verduras-salteadas.jpg',
            'ensalada verde' => 'ensalada-verde.jpg',
            'ensalada mixta' => 'ensalada-mixta.jpg',
            'empanadas' => 'empanadas.jpg',
            'bastones de mozzarella' => 'bastones-mozzarella.jpg',
            'tiramisu' => 'tiramisu.jpg',
            'torta de chocolate' => 'torta-chocolate.jpg',
            'mousse de chocolate' => 'mousse-chocolate.jpg',
            'helado' => 'helado.jpg',
            'flan casero' => 'flan-casero.jpg',
            'copa de dulce de leche' => 'copa-dulce-leche.jpg',
            'chocotorta' => 'chocotorta.jpg',
            'cheesecake' => 'cheesecake.jpg',
            'brownie con helado' => 'brownie-helado.jpg',
            'ensalada de frutas' => 'ensalada-frutas.jpg'
        );
        if (!isset($images[$name])) {
            return null;
        }
        $path = 'MVCsix1.0/img/catalogo/' . $images[$name];
        if (!is_file(__DIR__ . '/../../' . $path)) {
            throw new \RuntimeException('Falta la imagen del catalogo: ' . $path);
        }
        return $path;
    }
}
