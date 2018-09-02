# Conekta para webtvsolutions

## Descripción ##

Integración del Conekta como medio de pago externo para [webtvsolutions](https://webtvsolutions.com)

## Tabla de contenido

* [Requisitos](#requisitos)
* [Instalación](#instalación)
* [preguntas frecuentes](#preguntas-frecuentes)

## Requisitos ##

* PHP >= 7.0

## Instalación ##

1. [Descarga la integración](https://github.com/saulmoralespa/webtvsolutions-conekta/archive/master.zip)
2. Descomprimir .zip y editar el archivo config.php correspondiente con su cuenta de conekta y su tienda de webtvsolutions
3. Suba los archivos de la integración en su servidor
4. Confiigure en su tienda de webtvsolutions la url del procesador, este hace referencia a la url donde instalo la integración

## Preguntas frecuentes ##

### ¿ Países en los cuales esta disponible su uso ? ###

Actualmente solo para México, de donde es el medio de pago

### ¿ Como pruebo su funcionamiento ? ###

Use credenciales de prueba dados por conekta, edite el archivo config.php de la integración estableciendo el entorno y demás

### ¿ Qué más debo tener en cuenta, que no me hayas dicho ? ####

* Donde instale la integración use ssl
* La clave firma que se estable en la configuración de la tienda debe coincidir con la del archivo config.php
* La url de la tienda puede variar, puede siempre editar en el archivo config.php 