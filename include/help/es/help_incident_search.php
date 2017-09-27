<h1>Dashboard Vista de búsqueda</h1>
<p>
	Las búsquedas de tickets es la herramienta básica de control de tickets.
	</br>
	</br>
	Se puede usar la vista de búsqueda básica para encontrar los tickets que queremos, o acceder directamente al número de ticket en el menú de la izquierda.<b> Las búsquedas por defecto muestran todos los tickets no cerrados </b> y no solucionados. Las búsquedas son un listado y una información estadística básica sobre los resultados de esta búsqueda. Se pueden ver, en pantalla completa, listas para imprimir utilizando el botón de generar vista de informe HTML, en la solapa de “Estadísticas”.
</p>
<p>
	<?php print_image("images/help/ticket21.png", false, false); ?>
</p>
<p>
	La búsqueda avanzada es similar a la búsqueda básica, pero añade diversos controles de filtrado más. Cualquier búsqueda se puede guardar como búsqueda personalizada, de forma que con el combo de selección de búsquedas personalizadas se puede acceder a cualquier búsqueda previa guardada. Las búsquedas personalizadas son diferentes para cada usuario.
</p>
<p>
	<?php print_image("images/help/ticket22.png", false, false); ?>
</p>
<p>
	Pulsando sobre un ticket se puede acceder a todos los detalles del mismo. Esto activará las pestañas superiores de la sección de tickets y podremos visualizar su detalle, ver el inventario de objetos asociados a el ticket, revisar los cambios, añadir unidades de trabajo, etc. El entorno está basado en AJAX de forma que no hay que «refrescar» la página. Podemos igualmente volver a la página de búsquedas y acceder a otro ticket.
	</br>
	</br>
	Todas las columnas de la vista de búsquedas de tickets son auto-ordenables al pinchar en el título: pueden ordenarse por fecha, título, horas de trabajo asignadas, grupo, estado, etc.
	</br>
	</br>
	Veamos la información que muestra cada fila de la lista de tickets:
	</br>
	</br>
	<?php print_image("images/help/ticket23.png", false, false); ?>
	</br>
	</br>
	<ul>
		<li><b>ID:</b> La primera columna hace referencia al código numérico del ticket, se puede utilizar para acceder directamente a el ticket en cuestión.</li>
		<li><b>SLA:</b> símbolo de exclamación para hacer referencia a que el ticket no cumple la SLA.</li>
		<li><b>Ticket:</b> Es el título del ticket. Debajo aparece el tipo de ticket, y entre corchetes los campos personalizados del tipo de ticket marcados para ser mostrados en la vista principal.</li>
		<li><b>Grupo:</b> Grupo al que pertenece el ticket. Debajo aparece la empresa (si existe esa información) del usuario que creó el ticket.</li>
		<li><b>Estado / Resolución:</b> El estado (cerrada, asignada, pendiente de cerrar, nuevo, solucionado y sin confirmar) y la resolución (Solucionado, Incompleto, “a mí me funciona”, expirado, etc.).</li>
		<li><b>Prioridad:</b> Color en función de la criticidad del ticket. Opcionalmente puede aparecer como en la anterior captura un icono como un bocadillo, debajo del color. Esto indica que el ticket tiene una UT por parte del creador original del ticket, lo que significa que “deberíamos contestar” o que “la pelota está en nuestro tejado”.</li>
		<li><b>Actualizado / Creado:</b> Indica cuándo se actualizó el último ticket y cuándo se creó. Si aparece un nombre de usuario es quien creó la última Workunit (UT) en el ticket.</li>
		<li><b>Creador:</b> Muestra el creador del ticket.</li>
		<li><b>Propietario:</b> Muestra el nombre del usuario asignado a el ticket, el dueño (owner) y único usuario que puede cerrarla (exceptuando a usuarios con más privilegios -administradores o managers-).</li>
	</ul>
</p>
