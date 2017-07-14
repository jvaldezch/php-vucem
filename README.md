# php-vucem
## Librerías PHP para generar EDOCUMENTS y COVE.
Estás librerías pueden ser integradas facilmente en un proyecto Zend Framework 1.12 y permiten comunicarse con la Ventanilla Digital Mexicana de Comercio Exterior (VUCEM) vía Servicios Web para generar EDocuments a partir de archivos .PDF y COVE a partir de facturas, de igual forma permite el consumo de pedimentos.

...
$vucem = new Vucem_Xml(true);
$vucem->xmlCove($data);
$vucem->set_dir(__DIR__);
...
