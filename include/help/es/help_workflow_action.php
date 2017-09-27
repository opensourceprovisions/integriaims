<h1>Acciones de las reglas de workflow</h1>
<p>
	A continuación, vemos las acciones que se ejecutarán si se cumplen las condiciones anteriores.
</p>
<p>
	<?php print_image("images/help/ticket43.png", false); ?>
</p>
<p>
	Las acciones están formadas por los siguientes campos:
</p>
<p>
	<?php print_image("images/help/ticket44.png", false, false); ?>
</p>
<p>
	Las acciones se definen según el campo Tipo de acción:
	</br>
	<ul>
		<li><b>Cambiar prioridad:</b> El nuevo valor de la prioridad se actualizará en el ticket.</li>
		<li><b>Cambiar propietario: </b> El nuevo valor del usuario se actualizará en el ticket.</li>
		<li><b>Cambiar grupo:</b> El nuevo valor del grupo se actualizará en el ticket.</li>
		<li><b>Cambiar estado:</b> El nuevo valor del estado se actualizará en el ticket..</li>
		<li><b>Enviar correo electrónico:</b>  El destinatario del email se almacenará en el campo Para, el asunto en Asunto y el cuerpo del email, en el campo Texto.</li>
		<li><b>Añadir WU:</b>El texto de la workunit se añadirá a la incidencia con el usuario especificado.</li>
		<li><b>Cambiar fecha de actualización: </b> Para modificar la fecha de cuándo se actualizó por última vez el ticket.</li>
		<li><b>Cambiar resolución:</b>actualiza el valor de la resolución de cierre del ticket.</li>
		<li><b>Ejecutar comando:</b>Permite ejecutar un comando personalizado, incluyendo macros para sustitución dinámica de variables, tales como ID del ticket, grupo, usuario, etc.</li>
		<li><b>Bloquear ticket:</b>no se podrán modificar los valores de los campos del ticket.</li>
		<li><b>Desbloquear ticket:</b>podrán volverse a modificar los valores de los campos del ticket.</li>
	</ul>
	</br>
	Al crear la primera acción, se crea una acción por defecto que es la modificación de la fecha de última actualización del ticket. Esta acción se puede eliminar manualmente en el listado de acciones.
</p>
<p>
	<b>Ejemplos de uso:</b>
	</br>
	<ul>
		<li>
			“Para cualquier incidencia del grupo Clientes, que lleve más de 10 horas sin actualizar, se envía un email al propietario del ticket con el texto “No se está contestando la incidencia (titulo) del grupo Clientes. Revisad el tema urgentemente”.
		</li>
		<li>
			“Para cualquier incidencia, que lleve más de 21 días abierta, con prioridad “Alta” se le cambia la prioridad a “Media” y se le añade la WU que diga “Repriorizada automáticamente por el sistema”.
		</li>
	</ul>
</p>
<p>
	En general las reglas de workflow se dispararán una sola vez, de forma que si establece una regla para cambiar por ejemplo, el usuario asignado a la incidencia cuando una incidencia tenga más de 30 días de vida, y el usuario asignado es X, pero luego manualmente alguien vuelve a poner ese usuario X, la regla no se volverá a disparar.
</p>
<p>
	La única excepción de este comportamiento es cuando la condición es el tiempo de actualización. Si establece una regla para que salte cuando el ticket lleva más de X tiempo sin actualizar, se creará automáticamente una acción por defecto para “actualizar el ticket”. Esto hará que no salte continuamente la condición. Pasado ese X tiempo, el sistema podrá ejecutar de nuevo la misma regla de Workflow. Esta es la excepción, ya que para ninguna otra condición (Prioridad, Propietario, Estado, Creación ó Grupo), se podrá volver a ejecutar una regla.
</p>
<p>
	<b>Caso típico de uso para este tipo de condición:</b>
	</br>
	<ul>
		<li>
			“Necesita enviar un email de aviso a un coordinador, cuando una incidencia de prioridad muy alta y de un grupo determinado lleva más de 5 días sin actualizaciones.“
		</li>
	</ul>
	</br>
	Simplemente tiene que rellenar en la condición “Coincidir todos los campos”, el grupo específico y la prioridad muy alta, solo para incidencias asignadas. En “Fecha de actualización” escogeremos una semana.
</p>
<p>
	<?php print_image("images/help/ticket45.png", false, false); ?>
</p>
<p>
	Al añadir la acción de enviar un mail, se creará automáticamente la acción de actualizar el ticket, que dejaremos tal cual, para actualizar el ticket y evitar que siga saltando la regla.
</p>
<p>
	<?php print_image("images/help/ticket46.png", false); ?>
</p>
