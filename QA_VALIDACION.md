# RESTAURANTE-UY: informe de validacion y despliegue

## Alcance y conclusion

Validacion realizada en la rama `qa/auditoria-defensa`, fusionada despues en
`main` con autorizacion del responsable. Sitio: <https://restauranteuy.infinityfree.me/>.

Se corrigieron y desplegaron los problemas reproducibles descritos abajo.
Los cinco roles fueron evaluados localmente; en el hosting se comprobaron los
cuatro roles internos y el acceso real de un cliente invitado.
El asistente (antes `chatbot.*`) ya funciona en el hosting tras renombrar sus
archivos publicos. El ciclo comercial completo en produccion sigue pendiente;
ver la seccion "Prueba controlada en produccion".
Las pruebas destructivas de pedidos, pagos, empleados y reservas
se ejecutaron sobre `proyectomagnus_qa`, no sobre los datos publicados.

Entornos comprobados:

- Local: PHP 8.0, MySQL/MariaDB de XAMPP, servidor QA en el puerto 8800.
- Hosting: PHP **8.4.27**, comprobado mediante ejecucion en el servidor.
- Pruebas locales de stock con triggers desactivados para reproducir InfinityFree.
- Navegador integrado: escritorio 1366 x 768 y viewport movil 390 x 844.
  Esto no equivale a probar dispositivos fisicos ni motores de navegador distintos.

## Causa raiz de los errores de base de datos

La base publicada habia perdido `AUTO_INCREMENT` en seis tablas:
`roles`, `usuarios`, `productos`, `pedidos`, `pagos` y `movimientos_caja`.
Las nuevas altas intentaban utilizar el identificador 0 y fallaban con
`Duplicate entry '0' for key 'PRIMARY'`. El registro del servidor mostraba
tambien el fallo al crear un cliente invitado.

Se restauraron los atributos faltantes, conservando los tipos de columna y
todos los identificadores existentes. Se utilizo `NO_AUTO_VALUE_ON_ZERO`
durante la reparacion para conservar el usuario preexistente con ID 0.
La comprobacion posterior encontro las **13 columnas de identidad** con
`AUTO_INCREMENT`. Es un cambio persistente del esquema, no una correccion
temporal de la sesion ni dependiente de encender la computadora.

La reparacion reproducible esta en
[SQL/reparar_autoincrement_infinityfree.sql](SQL/reparar_autoincrement_infinityfree.sql);
solo corresponde al esquema de instalacion documentado y requiere respaldo previo.
Su prueba ejecuta la reparacion dos veces, conserva IDs 0 y 7 y verifica una nueva alta.

## Resumen cuantitativo

| Conjunto | Ejecutado | Aprobado | Fallido |
|---|---:|---:|---:|
| Harness HTTP local: autenticacion | 17 resultados | 17 | 0 |
| Harness HTTP local: pedidos/cocina/caja/mesas | 41 resultados | 41 | 0 |
| Harness HTTP local: productos | 12 resultados | 12 | 0 |
| Harness HTTP local: empleados | 6 resultados | 6 | 0 |
| Tests del repositorio, ultima ejecucion | 9 invocaciones | 9 | 0 |
| Harness final de hosting | 5 resultados agrupados | 4 | 1 |

Los **76 resultados HTTP locales** incluyen un resultado agregado para una matriz
de **31 rutas x 6 perfiles = 186 comprobaciones**. No deben sumarse esos 186
como si fueran resultados adicionales independientes del total 76.
Las nueve invocaciones del repositorio son seis tests PHP de dominio, dos modos
de `env_url_test.php` y el test JavaScript.

En el hosting, el resultado agrupado fallido correspondia a recursos estaticos:
`chatbot.js` devolvia HTTP 403 (resuelto despues renombrando a `asistente.js`). El harness tambien informo tres URLs escapadas
incorrectamente extraidas de HTML; se contrastaron con el DOM real del navegador:
logo, CSS de login y CSS del panel devolvieron **HTTP 200 y el tipo correcto**.
Se conserva el registro original, sin ocultar esos falsos positivos.

Comprobaciones adicionales, no sumadas a los resultados agrupados:

- 22 accesos a paginas publicas e internas sin errores ni avisos PHP visibles.
- 41 imagenes de productos: HTTP 200 y contenido de imagen; ninguna rota en el DOM.
- 61 archivos desplegados: SHA-256 identico al archivo local, cero diferencias.
- 13 columnas de identidad correctas; el ID 0 preexistente se conserva.
- Alta real de un invitado y carga del catalogo; acceso a empleados rechazado.
- HTTP redirige a HTTPS; XAMPP local conserva su funcionamiento por HTTP.
- 21 archivos PHP nuevos/modificados pasan `php -l`; `git diff --check` pasa.
- Herramienta temporal retirada: su URL devuelve HTTP 404.
- Respaldos SQL y de archivos protegidos: acceso publico devuelve HTTP 403.

## Matriz funcional y correcciones

| Modulo | Funcionalidad | Resultado | Evidencia | Problema | Severidad | Archivo | Correccion | Nueva prueba |
|---|---|---|---|---|---|---|---|---|
| Base de datos | Altas y claves primarias | Aprobado | Esquema real, alta invitado, test repetido | Seis tablas sin AUTO_INCREMENT | Alta | [SQL de reparacion](SQL/reparar_autoincrement_infinityfree.sql) | Restaurar atributos sin renumerar | 13 columnas correctas, nueva alta valida |
| Autenticacion | Login de cinco roles y redireccion | Aprobado local; acceso interno e invitado comprobados en hosting | Harness HTTP y navegador | Roles/URLs corregidos en commits anteriores | Alta | [interfazlog.php](interfazlog.php), [Env.php](src/Support/Env.php) | Mantener configuracion compatible con hosting y contexto | 17 resultados locales aprobados |
| Sesiones | ctx, logout y sesiones independientes | Aprobado local | Sesiones HTTP separadas y dos contextos en una misma cookie | Riesgo de mezcla de identidad | Alta | [Auth.php](src/Support/Auth.php) | Validacion del comportamiento existente | Logout de un contexto no cierra el otro |
| Permisos | Acceso permitido y denegado | Aprobado local | Matriz 31 x 6 | Acceso cruzado entre roles | Alta | [app_bootstrap.php](app_bootstrap.php) | Verificar permisos declarados y respuesta real | 186 comprobaciones sin fallos |
| Empleados | Crear, editar, eliminar y volver | Aprobado local; pagina de alta aprobada en hosting | Harness CRUD aislado y HTTP 200 | ID mal colocado, mayusculas de ruta y borrado GET | Alta | [EmpleadoI.php](EmpleadoI.php), [Actualizar.php](Actualizar.php), [Eliminar.php](Eliminar.php), [crear.php](crear.php) | ID antes de ctx, escape, POST+CSRF y nombre compatible con Linux | 6 resultados aprobados |
| Productos | CRUD, imagen personalizada y borrado con historial | Aprobado local | Harness y test de integridad | Foto de catalogo ocultaba la subida; borrado fallaba con ventas | Alta | [ProductoRepository.php](src/Repositories/ProductoRepository.php), [ProductoService.php](src/Services/ProductoService.php), [delete.php](MVCsix1.0/delete.php), [ProductImage.php](src/Support/ProductImage.php) | Prioridad a archivo subido; desactivar si tiene detalle de pedidos | 12 resultados y regresion aprobados |
| Catalogo | Productos e imagenes correspondientes | Aprobado publicado | 41 recursos HTTP 200, revision visual y hashes | Fotos intercambiadas y catalogo incompleto | Alta | [catalogo](MVCsix1.0/img/catalogo/), [cliente.php](cliente.php), [productos_consulta.php](productos_consulta.php) | 41 JPEG verificados, creditos y aviso de fotos ilustrativas | Cero imagenes rotas |
| Pedidos | Cliente/mozo, cantidades, stock y total de BD | Aprobado local | Harness HTTP con consulta de BD | Riesgos de cantidades/stock/importes inconsistentes | Alta | [pedidos](MVCsix1.0/pedido/), [cliente.php](cliente.php) | Verificar circuito real existente | Incluido en 41 resultados aprobados |
| Cocina | Recepcion, notas, estados y conservacion del pedido | Aprobado local; pantalla publicada carga | Harness y test cocina | Evitar transiciones invalidas o acciones accidentales | Alta | [Cocina2.php](Cocina2.php), [CambiarEstado.php](CambiarEstado.php) | Acciones protegidas por POST+CSRF | Test cocina aprobado |
| Caja/mesas | Cobro sin duplicar y liberacion correcta | Aprobado local; pantallas publicadas cargan | Harness y regresion de integridad | Una mesa podia liberarse con otro pedido impago | Alta | [PagoRepository.php](src/Repositories/PagoRepository.php), [PagoService.php](src/Services/PagoService.php) | Bloquear mesa y consultar pedidos impagos, sin corte de 12 horas | Pedido de dos dias impide liberar; ultimo pago libera |
| Cobro cliente | Solicitud, confirmacion y actualizacion automatica | Aprobado local | Test PHP y JavaScript | No confundir solicitud con pago confirmado | Alta | [solicitudes_cobro_test.php](database/tests/solicitudes_cobro_test.php), [cliente_cobro_test.js](database/tests/cliente_cobro_test.js) | Validacion del circuito existente | Tests aprobados, pago duplicado rechazado |
| Reservas | Capacidad, solapamiento, cancelacion e historial | Aprobado local; paginas publicadas cargan | Test con tablas temporales | Evitar reservas incompatibles y estados incorrectos | Alta | [reservas_test.php](database/tests/reservas_test.php) | Validacion del circuito existente | Test aprobado |
| Despliegue | Enlaces, mayusculas, PHP y HTTPS | Aprobado en rutas comprobadas | 22 paginas y navegador | Ruta Crear/crear y HTTP sin redireccion | Alta | [.htaccess](.htaccess), [crear.php](crear.php), [DEPLOY_INFINITYFREE.md](DEPLOY_INFINITYFREE.md) | Ruta minuscula y HTTPS salvo entorno local | Sin avisos PHP; redireccion valida |
| Asistente | Script y servicio en InfinityFree | Aprobado en hosting | Navegador real con sesion de administrador: `asistente.js` HTTP 200; preguntas "Cual es el horario?" y "Recaudado de hoy" respondidas por el servidor | InfinityFree responde 403 a toda URL que contenga `chat` (filtro contra chats en vivo) | Media | [asistente.js](asistente.js), [asistente_api.php](asistente_api.php), [Principal.php](Principal.php) | Renombrado de `chatbot.*` a `asistente.*`, solucion indicada por el proveedor; el asistente es un FAQ sin sondeo, no un chat en vivo. Archivos antiguos eliminados del hosting | Sin 403; respuestas correctas |
| Produccion | Ciclo completo pedido-cocina-cobro-reserva | No probado | No se crearon operaciones comerciales de prueba | Riesgo residual especifico del entorno publicado | Alta | Circuito completo | Prueba autorizada; ver "Prueba controlada en produccion" | Pendiente |
| Produccion | Editar/eliminar empleado | No probado | La tabla publicada tiene cero empleados | No existe registro sobre el que probar sin crear datos | Media | [EmpleadoI.php](EmpleadoI.php) | CRUD validado en base aislada | Pendiente en hosting |
| Compatibilidad | Dispositivos fisicos y otros navegadores | No probado | Solo navegador integrado y viewport movil | Sin cobertura de motores/dispositivos adicionales | Media | Interfaz completa | Verificacion manual adicional | Pendiente |
| Contacto | Enlaces sociales y datos definitivos del pie | No probado/configuracion pendiente | Existen valores de ejemplo | No hay datos reales confirmados para sustituirlos | Baja | Pie de las paginas | Completar con informacion del restaurante | Pendiente |

La matriz de permisos contrasta la respuesta HTTP con los roles declarados en
el codigo. No constituye por si sola una auditoria independiente de todas las
reglas de negocio o una certificacion de seguridad.

## Inventario de rutas principales

Todas las rutas de esta tabla se incluyeron en la matriz local. "Hosting" indica
solo lectura/carga, no ejecucion de todos sus formularios.

| Ruta | Local | Hosting |
|---|---|---|
| [Principal.php](Principal.php) | Aprobado | Aprobado |
| [inicio_rol.php](inicio_rol.php) | Aprobado | Aprobado |
| [EmpleadoI.php](EmpleadoI.php) | Aprobado | Aprobado |
| [InterfazE.php](InterfazE.php) | Aprobado | Aprobado |
| [productos.php](productos.php) | Aprobado | No incluido en corrida final |
| [informes.php](informes.php) | Aprobado | Aprobado |
| [pedidos.php](pedidos.php) | Aprobado | No incluido en corrida final |
| [InterfazObtenerPedidos.php](InterfazObtenerPedidos.php) | Aprobado | No incluido en corrida final |
| [cocina.php](cocina.php) | Aprobado | No incluido en corrida final |
| [Cocina2.php](Cocina2.php) | Aprobado | Aprobado |
| [CAJA.php](CAJA.php) | Aprobado | Aprobado |
| [mesas.php](mesas.php) | Aprobado | Aprobado |
| [mesas_estado.php](mesas_estado.php) | Aprobado | No incluido en corrida final |
| [mesas_historial.php](mesas_historial.php) | Aprobado | No incluido en corrida final |
| [centro_reservas.php](centro_reservas.php) | Aprobado | Aprobado |
| [dashboard_ocupacion.php](dashboard_ocupacion.php) | Aprobado | Aprobado |
| [reportes_reservas.php](reportes_reservas.php) | Aprobado | Aprobado |
| [notificaciones.php](notificaciones.php) | Aprobado | No incluido en corrida final |
| [exportar_reservas.php](exportar_reservas.php) | Aprobado | No incluido en corrida final |
| [cliente.php](cliente.php) | Aprobado | Aprobado, invitado real |
| [pedidos_cliente_estado.php](pedidos_cliente_estado.php) | Aprobado | No incluido en corrida final |
| [productos_consulta.php](productos_consulta.php) | Aprobado | Aprobado |
| [ObtenerPedidosJSON.php](ObtenerPedidosJSON.php) | Aprobado | No incluido en corrida final |
| [Obtener_pedidos_caja.php](Obtener_pedidos_caja.php) | Aprobado | No incluido en corrida final |
| [index.php de productos](MVCsix1.0/index.php) | Aprobado | Aprobado |
| [create.php de productos](MVCsix1.0/create.php) | Aprobado | No incluido en corrida final |
| [ListaPedido.php](MVCsix1.0/ListaPedido.php) | Aprobado | No incluido en corrida final |
| [pedido.php](MVCsix1.0/pedido/pedido.php) | Aprobado | Aprobado |
| [crear.php](crear.php) | Aprobado | Aprobado |
| [registro_cliente.php](registro_cliente.php) | Aprobado | Aprobado, lectura |
| [Login.php](Login.php) | Aprobado | Aprobado |

El inventario es de rutas principales, no de todos los archivos PHP del repositorio.
Los endpoints de acciones se comprobaron mediante los escenarios funcionales
locales, no enviando GET para intentar modificarlos.

## Catalogo, despliegue y preservacion de datos

- Catalogo publicado: **4 -> 41 productos**. Se conservaron los precios, stock
  y estado de los productos preexistentes; tres nombres se normalizaron para
  asociarlos al catalogo. La hamburguesa existente se conservo.
- 40 entradas canonicas mas la hamburguesa; 41 fotografias correspondientes al
  tipo de producto, revisadas visualmente. Envases/presentaciones son ilustrativos.
- Licencias y autoria en [fuentes.json](MVCsix1.0/img/catalogo/fuentes.json)
  y [creditos.html](MVCsix1.0/img/catalogo/creditos.html). Las fotos subidas
  por el administrador tienen prioridad.
- 18 archivos PHP, 41 JPEG y dos archivos de creditos: 61 hashes coincidentes
  entre local y hosting. La regla HTTPS se verifico por su respuesta HTTP.
- Usuarios: 5 -> 6 por una prueba explicita de acceso invitado identificada
  como validacion QA. No se crearon pedidos, pagos ni reservas de demostracion.
- Estado final publicado: 41 productos; cero empleados, pedidos, detalles,
  pagos y reservas. No se borraron operaciones comerciales existentes.
- Se retiraron la herramienta de mantenimiento, el manifiesto temporal y los
  diez fragmentos de imagenes: 13 archivos temporales en total.
- El servidor QA temporal se detuvo al finalizar.

### Respaldos comprobados en el hosting

Se conservan dentro de `storage/logs`, protegido del acceso HTTP publico.
Los siguientes nombres son de archivos del servidor, no descargas locales:

| Respaldo | Bytes | SHA-256 |
|---|---:|---|
| BD anterior: `respaldo_pre_catalogo_20261025.sql` | 13794 | `23697ffcd555292542c20280903ac86bf924fe21487367e1b03f6e2e066793c4` |
| BD corregida: `respaldo_corregido_20261025.sql` | 25602 | `74549190ca872a85c5a936e84f0c95fa3bd2c4e06457c317b402bc894831a667` |
| 59 archivos anteriores: `respaldo_archivos_pre_qa_20261025.log` | 7433985 | `2d6b4f46917290fdd803355c04da14774617ca879de56d5e68a872e228ad6454` |

El archivo `.log` es un contenedor JSON comprimido con gzip, con contenido
base64 y hashes por archivo; no es un SQL ni un ZIP. Ademas se conservo la
configuracion anterior de `.htaccess` en `htaccess_pre_qa_20261025.log`.
No se incluye ningun respaldo con datos privados en Git.
No se completo una descarga local del respaldo del hosting; conviene obtener
una copia externa mediante el Administrador de archivos.
Las copias intentadas fuera de `htdocs` no se consideran respaldos validos.

## Reproduccion de los tests del repositorio

Usar una base QA con el esquema actualizado, nunca apuntar los harness de
operaciones a produccion. Estos tests PHP usan tablas temporales de conexion:

```powershell
$env:APP_DB_NAME='proyectomagnus_qa'
$env:APP_DB_STOCK_TRIGGERS='0'
C:\xampp\php\php.exe database\tests\cocina_test.php
C:\xampp\php\php.exe database\tests\reservas_test.php
C:\xampp\php\php.exe database\tests\solicitudes_cobro_test.php
C:\xampp\php\php.exe -d extension=gd database\tests\product_images_test.php
C:\xampp\php\php.exe database\tests\integridad_productos_pagos_test.php
C:\xampp\php\php.exe database\tests\autoincrement_test.php
Remove-Item Env:APP_DB_NAME
C:\xampp\php\php.exe database\tests\env_url_test.php
C:\xampp\php\php.exe -d disable_functions=putenv database\tests\env_url_test.php
node database\tests\cliente_cobro_test.js
git diff --check
```

El test JS se ejecuto con el runtime Node incluido en VS Code porque `node`
no estaba en PATH. Los harness HTTP y sus registros se conservaron como
evidencia privada de la sesion, fuera del repositorio: `final_t_auth.txt`,
`final_t_orders.txt`, `final_t_products.txt`, `final_t_employees.txt` y
`final_hosting.txt`. No se versionan cuentas, contrasenas ni cookies del harness.
Los tests nuevos de integridad y AUTO_INCREMENT quedan versionados.

## Riesgos y recomendaciones antes de la defensa

1. No quedan errores criticos conocidos en los circuitos locales ejercitados.
   Esto no descarta errores fuera de la cobertura declarada.
2. El asistente funciona en InfinityFree desde que sus archivos se llaman
   `asistente.*`. No uses `chat` en nombres de archivos, carpetas ni URLs: el
   proveedor responde 403 a cualquier ruta que lo contenga.
3. Antes de la defensa, realizar una venta supervisada en el sitio publicado,
   recorriendo cliente/mozo, cocina, caja y liberacion de mesa, con autorizacion
   y registro de los datos que se generen. La prueba local no sustituye esa prueba.
4. Probar el sitio en el telefono real y navegador que utilizara la orientadora.
5. Completar datos de contacto y enlaces sociales reales; revisar precios y stock
   de las nuevas entradas antes de utilizarlas como oferta comercial.
6. Descargar una copia externa de los respaldos: el almacenamiento en el mismo
   hosting no protege frente a perdida de la cuenta o del servidor.
7. Mantener las atribuciones de imagenes y no reimportar un SQL antiguo sobre
   la base reparada. Consultar [DEPLOY_INFINITYFREE.md](DEPLOY_INFINITYFREE.md)
   para diagnostico y validacion posterior a una importacion.

## Prueba controlada en produccion

Estado: **pendiente**. El responsable autorizo una venta de prueba en el sitio
publicado, conservando los registros identificados como prueba QA. No se
ejecuto porque el navegador integrado dejo de estar conectado; no se crearon
pedidos ni pagos en produccion. Recorrido previsto: pedido con un producto
economico en una mesa libre (mozo/administrador), cambio de estado en cocina,
cobro en caja, liberacion de la mesa y control de "Recaudado de hoy".

## Fusion en `main` y despliegue automatico

`qa/auditoria-defensa` se fusiono con `origin/main` (incluye PR #5) y `main`
se actualizo por avance rapido. Tras la fusion se corrigio de nuevo el enlace
"Agregar Funcionario" de [EmpleadoI.php](EmpleadoI.php) a `crear.php` (el
archivo `Crear.php` no existe y el hosting distingue mayusculas). El workflow
`CI` de GitHub fue aprobado. El workflow "Deploy PHP to InfinityFree (FTPS)"
usa el entorno protegido `infinityfree` y queda en espera hasta que el
responsable apruebe el despliegue en GitHub Actions.
