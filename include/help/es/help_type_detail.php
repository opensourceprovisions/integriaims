<h1>Tipos de ticket</h1>
<p>
	A un ticket se le puede asignar un tipo (por ejemplo incidencia, solicitud, reclamaciones, etc).
</p> 
<p>
	<?php print_image("images/help/Ticket_type_1.png", false, false); ?>
</p>
<p>
	Se puede definir que grupos tendrán disponible ese tipo de ticket. Por defecto está en blanco y es visible para todos los grupos.
</p>
<p>
	<?php print_image("images/help/tipoticket2.png", false, false); ?>
</p>
<p>
	Cada tipo de ticket puede tener asociados campos personalizados según convenga. Estos campos puede ser de varios tipos: texto, combo, área de texto, numérico o linkados. Si se elige el tipo combo o linkado, habrá que especificar los valores que tendrán éstos controles. A continuación, vemos un <b>ejemplo de creación de tipo combo:</b>
</p>
<p>
	<?php print_image("images/help/tipoticket3.png", false, false); ?>
</p>
<p>
	Ahora creamos un campo personalizado asociado al tipo anterior:
</p>
<p>
	<?php print_image("images/help/tipoticket4.png", false, false); ?>
</p>
<p>
	Los campos linkados están relacionados entre sí. Los vamos a explicar con un <b>ejemplo:</b>
	</br>
	TIPO,VEHICULO,MARCA,MODELO,MOTOR
	</br>
	Primero crearemos el campo TIPO. En este caso, no seleccionamos campo padre porque es el primero en nuestra jerarquía. Los valores irán separados por coma.
</p>
<p>
	<?php print_image("images/help/ticket9.png", false, false); ?>
</p>
<p>
	Después crearemos el campo MARCA. Habrá que seleccionar el campo padre TIPO. Después rellenaremos el campo con los valores separados por coma como en el caso anterior. Como este campo sí tiene un padre, habrá que asociar los valores. Para ello, pondremos delante el valor del campo padre separado de |
</p>
<p>
	<?php print_image("images/help/ticket10.png", false, false); ?>
</p>
<p>
	El siguiente campo que vamos a crear es MODELO. Se selecciona el padre MARCA y se ponen los valores.
</p>
<p>
	<?php print_image("images/help/ticket11.png", false, false); ?>
</p>
<p>
	Por último, creamos el campo MOTOR.
</p>
<p>
	<?php print_image("images/help/ticket12.png", false, false); ?>
</p>
<p>
	Los campos marcados con el check <b>“Mostrar en la vista general”</b> son campos que se pueden usar en búsquedas y que se muestran en la visualización de lista. En los campos de tipo textarea no se puede usar esta casilla.
	</br>
	</br>
	Los campos marcados como <b>“Campo Global”</b> aparecerán en todos los tipos de tickets. Serán campos comunes usados por todos los tipos de tickets existentes.
</p>
<h1>Vista general:</h1>
<p>
	Aquí se pueden ver todos los campos personalizados que tiene un tipo. Desde esta vista se pueden añadir campos, editar, borrar y ordenar su visualización en el ticket.
</p>
<p>
	<?php print_image("images/help/tipoticket5.png", false, false); ?>
</p>