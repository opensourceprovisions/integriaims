<h1>Creación de un tipo de objeto de inventario</h1>
<p>
	Los tipos de objeto de inventario sirven para personalizar los diferentes elementos que se van a utilizar en inventario. Puede gestionar los tipos de objetos pulsando en la opción <b>Tipos de objetos</b>.
</p>
<p>
	Explicaremos el funcionamiento de los tipos de objetos con un ejemplo, vamos a crear un tipo de objeto de inventario llamado Software que tendrá los campos asociados Version y Description.
</p>
<p>
	<?php print_image("images/help/inventory1.png", false, false); ?>
</p>
<p>
	Un tipo de objeto de inventario tiene asociado un nombre, un icono, el valor mínimo que debe tener en stock y una descripción. Además podremos definir si este tipo aparecerá o no como raíz en la vista de árbol del inventario. Por cada tipo, se pueden añadir campos personalizados.
</p>
<p>
	<?php print_image("images/help/inventory2.png", false, false); ?>
</p>
<p>
	Estos campos pueden tener valor único que sirva como identificador y se pueden marcar para ser heredados por otros objetos. Otra característica es que se pueden marcar para ser mostrados en la búsqueda. De esta forma, la visualización de inventario en la búsqueda también es flexible.
</p>
<p>
	Los campos personalizados pueden ser de tipo numérico, texto, combo o bien externo.
</p>
<p>
	<?php print_image("images/help/inventory3.png", false, false); ?>
</p>
<p>
	Los campos de tipo externo hacen referencia a una tabla externa en la base de datos y pueden relacionarse con otras tablas. Cuando se crean, se debe detallar el nombre de la tabla y el campo identificador de la misma. En el caso de estar relacionada con otra tabla, también habrá que especificar el nombre de la tabla padre y el nombre del campo donde se va a almacenar el valor del identificador de dicho padre.
</p>
<p>
	<?php print_image("images/help/inventory4.png", false, false); ?>
</p>
<p>
	A la hora de seleccionar el valor de estos campos aparecerá una ventana modal mostrando toda la información de la tabla externa.
</p>
<p>
	<?php print_image("images/help/inventory5.png", false, false); ?>
</p>
<p><i>
	Para añadir una tabla externa y usarla posteriormente en los objetos de inventario 
	sólo tiene que añadir la tabla que desee con los datos correspondientes dentro 
	de la base de datos de Integria. El mantenimiento de esta tabla se realiza de 
	forma manual operando con ella por medio de sentencias SQL, o bien, utilizando 
	el editor de tablas externas que proporciona Integria IMS.
</i></p>